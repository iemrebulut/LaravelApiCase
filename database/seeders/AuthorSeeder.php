<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = Storage::disk('local')->get('json/authors.json');
        $authors = json_decode($json, true);

        foreach ($authors as $author) {
            Author::query()->updateOrCreate([
                'name' => $author['name']
            ]);
        }
    }
}
