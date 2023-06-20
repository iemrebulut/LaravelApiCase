<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CampaignController extends BaseController
{
    public function __construct()
    {
        Cache::forget('campaigns');
        Cache::remember('campaigns', now()->addMinute(3), function () {
            $campaigns = Campaign::with('campaignConditions')->withCount('campaignConditions')->get();
            if($campaigns->isEmpty())
                return null;
            return $campaigns;
        });
    }

    public function campaignCheck($data) {
        if(Cache::has('campaigns')) {

            $campaigns = Cache::get('campaigns');

            foreach ($campaigns as $campaign) {
                $productIds = true; // Kampanya şartı yoksa

                if($campaigns->campaign_conditions_count > 0) {
                    $productIds = array();
                    foreach ($data as $row) {
                        $productCount = Product::query();
                        $productCount->where('id', $row['product_id']);
                        foreach ($campaign->campaignConditions as $campaignCondition) {
                            // Koşullar ile ürünleri eşleştir
                            $productCount->where($campaignCondition['key'], $campaignCondition['value']);
                        }
                    }
                    if($productCount->count() > 0) {
                        // Kampanya ile eşleşen ürünler;
                        $productIds[] = $row['product_id'];
                    }
                }

                if($productIds) {
                    $allowCampaigns[$campaign->id] = $productIds;
                }
            }
            return $allowCampaigns;
        }
        return false;
    }

    public function campaignControl($campaignsCheck, $cartData) {
        $campaignArray = array();
        foreach ($campaignsCheck as $campaignId => $campaignToProductIds) {
            $campaignDetail = Campaign::where('id', $campaignId)->first();

            $campaignTotalPrice = 0; // Hangi kampanyanın müşteri için uygun olduğunu hesaplayabilmek için
            $xAlYOdeCampaignProductCount = 0;
            $productTotalPrice = 0;
            $okProducts = array();
            $campaignDetailArray = array();
            $cartDataProducts = $cartData['products'];
            $xAlYOdeLimit = $campaignDetail['x_al_y_ode_limit'];

            if (is_array($campaignToProductIds)) {
                // Kampanya şartlarını sağlayan ürünlerin bilgilerini işle;
                foreach ($campaignsCheck[$campaignId] as $campaignToProduct) {
                    $xAlYOdeCampaignProductCount += $cartDataProducts[$campaignToProduct]['quantity']; // Kampanya dahil ürünlerin adet toplamı
                    $productTotalPrice += $cartDataProducts[$campaignToProduct]['product_total_price']; // Kampanyaya dahil ürünlerin fiyatlarının toplamı

                    $okProducts[$campaignToProduct] = array(
                        'list_price' => $cartDataProducts[$campaignToProduct]['list_price']
                    );
                }

                // Sepet tutarını hesaplamak için kampanya harici ürünleri de alıyoruz;
                $otherProducts = array_diff(array_column($cartDataProducts, 'product_id'), $campaignToProductIds);

                foreach ($otherProducts as $otherProduct) {
                    $otherProductCount = $cartDataProducts[$otherProduct]['quantity'];
                    $otherProductPrice = $otherProductCount * $cartDataProducts[$otherProduct]['list_price'];
                    $campaignTotalPrice += $otherProductPrice;
                    $campaignDetailArray[$otherProduct] = array(
                        'is_campaign' => 0,
                        //'product_id' => $otherProduct,
                        'campaign_quantity' => $otherProductCount,
                        'campaign_price' => $otherProductPrice
                    );
                }
            } else {
                // Kampanya şartı yok, sepetteki tüm ürünlerin bilgilerini işle
                foreach ($cartDataProducts as $row) {
                    $xAlYOdeCampaignProductCount += $row['quantity'];
                    $productTotalPrice += $row['product_total_price'];

                    $okProducts[$row['product_id']] = array(
                        'list_price' => $row['list_price']
                    );
                }
            }

            if ($campaignDetail['is_x_al_y_ode_campaign'] and ($xAlYOdeLimit <= $xAlYOdeCampaignProductCount)) {
                // X Al Y Öde kampanyası işlemleri;

                // Ucuzdan pahalıya doğru ürünleri sırala;
                // X al y öde kampanyası sonucunda düşecek adet en ucuz üründen düşürülmesi için;
                uasort($okProducts, function ($a, $b) {
                    return $a['list_price'] <=> $b['list_price'];
                });

                foreach ($okProducts as $okProductId => $okProductValue) {
                    // Ürünlerin arasındaki en uygun fiyatlı üründen x al y öde miktarı kadar adet düşür;
                    $campaignCount = (key($okProducts) == $okProductId) ? ($cartDataProducts[$okProductId]['quantity'] - $campaignDetail['x_al_y_ode_free']) : $cartDataProducts[$okProductId]['quantity'] - $kalanCampaignCount;

                    // Kampanyada düşecek adet sonrası sepetteki ürün adeti yeterli değilse diğer üründen düşülmek üzere kalan hesaplanıyor.
                    $kalanCampaignCount = 0;
                    if ($campaignCount < 0) {
                        $kalanCampaignCount = abs($campaignCount);
                        $campaignCount = 0;
                    }

                    $campaignPrice = ($campaignCount * $cartDataProducts[$okProductId]['list_price']);
                    $campaignTotalPrice += $campaignPrice;
                    $campaignDetailArray[$okProductId] = array(
                        'is_campaign' => true,
                        //'product_id' => $okProductId,
                        'campaign_quantity' => $campaignCount,
                        'campaign_price' => $campaignPrice
                    );
                }
            } else if ($campaignDetail['is_price_limit_campaign'] and ($campaignDetail['price_min_limit'] <= $productTotalPrice)) {
                // Yüzde indirimli kampanya için şartlar sağlandı.

                foreach ($okProducts as $okProductId => $okProductListPrice) {
                    $campaignCount = $cartDataProducts[$okProductId]['quantity'];
                    $campaignPrice = (($campaignCount * $cartDataProducts[$okProductId]['list_price']) - ($campaignCount * $cartDataProducts[$okProductId]['list_price'] * $campaignDetail['percent']));
                    $campaignTotalPrice += $campaignPrice;
                    $campaignDetailArray[$okProductId] = array(
                        'is_campaign' => true,
                        //'product_id' => $okProductId,
                        'campaign_quantity' => $campaignCount,
                        'campaign_price' => $campaignPrice
                    );
                }

            } else {

                foreach ($okProducts as $okProductId => $okProductValue) {
                    // Ürünlerin arasındaki en uygun fiyatlı üründen x al y öde miktarı kadar adet düşür;
                    $campaignCount = $cartDataProducts[$okProductId]['quantity'];
                    $campaignPrice = ($campaignCount * $cartDataProducts[$okProductId]['list_price']);

                    $campaignTotalPrice += $campaignPrice;
                    $campaignDetailArray[$okProductId] = array(
                        'is_campaign' => 0,
                        //'product_id' => $okProductId,
                        'campaign_quantity' => $campaignCount,
                        'campaign_price' => $campaignPrice
                    );
                }

            }

            // Kampanya bilgileri
            $campaignArray[] = array(
                'campaign_id' => $campaignDetail['id'],
                'campaign_detail' => $campaignDetail['detail'],
                'campaign_total_price' => $campaignTotalPrice,
                'products' => $campaignDetailArray
            );

        }

        // Müşteri için en uygun kampanyaya göre kampanyaları sıralama işlemi;
        usort($campaignArray, function ($a, $b) {
            return $a['campaign_total_price'] <=> $b['campaign_total_price'];
        });

        return $campaignArray[0];

    }
}
