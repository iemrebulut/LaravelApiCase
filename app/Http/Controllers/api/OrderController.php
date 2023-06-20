<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Jobs\OrderJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OrderController extends BaseController
{
    public function __construct()
    {
        Cache::forget('products');
        Cache::remember('products', now()->addMinutes(3), function() {
            return Product::get();
        });
    }

    public function create(Request $request) {
        try {
            $requestData = $request->except('');

            $noProduct = array();
            $outOfStock = array();
            $cartData = array();
            $campaignController = new CampaignController();
            $sipNo = 'ORD-' . preg_replace("/[^0-9]/", "", uniqid());
            $user = Auth::user();

            foreach ($requestData as $requestDataRow) {
                // Does the product id from the request exist in the database
                if(Cache::get('products')->where('id', $requestDataRow['product_id'])->count() == 0 ) {$noProduct[] = $requestDataRow['product_id'];}
                // Stock Control
                if(Cache::get('products')->where('id', $requestDataRow['product_id'])->where('stock_quantity', '>=', $requestDataRow['quantity'])->count() == 0 ) {$outOfStock[] = $requestDataRow['product_id'];}
            }

            if (count($noProduct) > 0){
                $data = [
                    'no_find_product_ids' => $noProduct
                ];
                return $this->error('Couldn\'t find the product.', $data);
            } else if (count($outOfStock) > 0){
                $data = [
                    'out_of_stock_product_ids' => $outOfStock
                ];
                return $this->error('The product sold out.', $data);
            }

            $orderTotalPrice = 0;
            foreach ($requestData as $key => $row) {

                $productDetail = Cache::get('products')->where('id', $row['product_id'])->first();

                $campaignsCheck = $campaignController->campaignCheck($requestData);

                $returnProductDetail['products'][$productDetail['id']] = $productDetail;

                $orderTotalPrice += $productDetail['list_price']*$row['quantity'];

                $cartData['products'][$row['product_id']] = array(
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'list_price' => $productDetail['list_price'],
                    'product_total_price' => $productDetail['list_price']*$row['quantity']
                );
                $cartData['cart_detail']['order_total_price'] = $orderTotalPrice;
            }

            if($campaignsCheck) {
                $orderTotalPrice = 0;
                $campaignsControl = $campaignController->campaignControl($campaignsCheck, $cartData);
                foreach ($campaignsControl['products'] as $key => $value) {
                    $cartData['products'][$key]['is_campaign'] = $value['is_campaign'];
                    $cartData['products'][$key]['campaign_quantity'] = $value['campaign_quantity'];
                    $cartData['products'][$key]['campaign_price'] = $value['campaign_price'];
                    $orderTotalPrice += $value['campaign_price'];
                }
                $cartData['cart_detail']['order_total_price'] = $orderTotalPrice;
                $cartData['campaign']['campaign_id'] = $campaignsControl['campaign_id'];
                $cartData['campaign']['campaign_detail'] = $campaignsControl['campaign_detail'];
            }

            $campaignFee = 0;
            $allProductsFee = 0;
            foreach ($cartData['products'] as $cartDataProducts) {
                $allProductsFee += $cartDataProducts['product_total_price'];
                $campaignFee += (isset($cartDataProducts['campaign_price'])) ? $cartDataProducts['campaign_price'] : 0;
            }
            $discountFee = ($campaignFee > 0) ? $allProductsFee-$campaignFee : 0;

            $totalFee = $allProductsFee-$discountFee;
            $cargoFee = ($totalFee >= 50) ? 0 : 10;
            $totalFee += $cargoFee;
            $campaignJson = (isset($cartData['campaign'])) ? json_encode($cartData['campaign']) : null;
            $orderData = array(
                'sip_no' => $sipNo,
                'user_id' => $user->id,
                'all_products_fee' => $allProductsFee,
                'discount_fee' => $discountFee,
                'cargo_fee' => $cargoFee,
                'total_fee' => $totalFee,
                'products' => json_encode($cartData['products']),
                'campaign' => $campaignJson
            );

            $orderCreate = dispatch(new OrderJob($orderData));

            return $this->success('Order created successfully', ['sip_no' => $sipNo]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), []);
        }
    }

    public function detail($sipNo) {
        if(Order::where('sip_no', $sipNo)->count() == 0){return $this->error('Couldn\'t find the order.');}
        $orderDetail = Order::where('sip_no', $sipNo)->first();
        $data = array(
            'id' => $orderDetail['id'],
            'sip_no' => $orderDetail['sip_no'],
            'user_id' => $orderDetail['user_id'],
            'all_products_fee' => $orderDetail['all_products_fee'],
            'discount_fee' => $orderDetail['discount_fee'],
            'cargo_fee' => $orderDetail['cargo_fee'],
            'total_fee' => $orderDetail['total_fee'],
            'products' => json_decode($orderDetail['products'], true),
            'campaign' => json_decode($orderDetail['campaign'], true),
        );
        return $this->success('Success', $data);
    }
}
