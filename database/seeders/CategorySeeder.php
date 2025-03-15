<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'noticias',
            'tecnología',
            'demo',
            'tutoriales',
            'eventos',
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
