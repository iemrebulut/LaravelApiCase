<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = Storage::disk('local')->get('json/products.json');
        $products = json_decode($json, true);

        foreach ($products as $product) {
            Product::query()->updateOrCreate([
                'title' => $product['title'],
                'category_id' => $product['category_id'],
                'author_id' => $product['author_id'],
                'list_price' => $product['list_price'],
                'stock_quantity' => $product['stock_quantity'],
                'is_domestic' => $product['is_domestic']
            ]);
        }
    }
}
