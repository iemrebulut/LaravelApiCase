<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CampaignsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = Storage::disk('local')->get('json/campaigns.json');
        $campaigns = json_decode($json, true);

        foreach ($campaigns as $campaign) {
            Campaign::query()->updateOrCreate([
                'detail' => $campaign['detail'],
                'is_price_limit_campaign' => $campaign['is_price_limit_campaign'],
                'is_x_al_y_ode_campaign' => $campaign['is_x_al_y_ode_campaign'],
                'price_min_limit' => $campaign['price_min_limit'],
                'percent' => $campaign['percent'],
                'x_al_y_ode_limit' => $campaign['x_al_y_ode_limit'],
                'x_al_y_ode_free' => $campaign['x_al_y_ode_free']
            ]);
        }
    }
}
