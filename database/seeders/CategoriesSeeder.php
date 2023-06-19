<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = Storage::disk('local')->get('json/categories.json');
        $categories = json_decode($json, true);

        foreach ($categories as $category) {
            Category::query()->updateOrCreate([
                'title' => $category['title']
            ]);
        }
    }
}
