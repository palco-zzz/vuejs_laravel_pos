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
            ['nama' => 'Aneka Ovomaltine', 'icon' => '🍫'],
            ['nama' => 'Aneka Taro', 'icon' => '🟣'],
            ['nama' => 'Aneka Cappuchino', 'icon' => '☕'],
            ['nama' => 'Aneka Mangga', 'icon' => '🥭'],
            ['nama' => 'Cheese Crunchy', 'icon' => '🧀'],
            ['nama' => 'Aneka Choco Crunchy', 'icon' => '🍫'],
            ['nama' => 'Aneka Oreo', 'icon' => '🍪'],
            ['nama' => 'Aneka Pisang', 'icon' => '🍌'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
