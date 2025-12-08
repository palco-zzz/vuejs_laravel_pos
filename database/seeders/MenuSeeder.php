<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Category;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $ovomaltine = Category::where('nama', 'Aneka Ovomaltine')->first()->id;
        $taro = Category::where('nama', 'Aneka Taro')->first()->id;
        $cappuchino = Category::where('nama', 'Aneka Cappuchino')->first()->id;
        $mangga = Category::where('nama', 'Aneka Mangga')->first()->id;
        $cheeseCrunchy = Category::where('nama', 'Cheese Crunchy')->first()->id;
        $chocoCrunchy = Category::where('nama', 'Aneka Choco Crunchy')->first()->id;
        $oreo = Category::where('nama', 'Aneka Oreo')->first()->id;
        $pisang = Category::where('nama', 'Aneka Pisang')->first()->id;

        $menus = [
            // Aneka Ovomaltine
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Selay', 'harga' => 25000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Coklat', 'harga' => 28000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Keju', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Green Tea', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Oreo', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Taro', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Tiramisu', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Choco Crunchy', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Cappucino', 'harga' => 29000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Mangga', 'harga' => 30000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Hazelnuts', 'harga' => 31000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Chocomaltine', 'harga' => 31000, 'icon' => '🍫'],
            ['category_id' => $ovomaltine, 'nama' => 'Ovomaltine - Nutella', 'harga' => 37000, 'icon' => '🍫'],

            // Aneka Taro
            ['category_id' => $taro, 'nama' => 'Taro - Selay', 'harga' => 19000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Coklat', 'harga' => 20000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Keju', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Green Tea', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Oreo', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Taro', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Tiramisu', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Choco Crunchy', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Cappucino', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Mangga', 'harga' => 22000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Hazelnuts', 'harga' => 25000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Chocomaltine', 'harga' => 25000, 'icon' => '🟣'],
            ['category_id' => $taro, 'nama' => 'Taro - Nutella', 'harga' => 30000, 'icon' => '🟣'],

            // Aneka Cappuchino
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Selay', 'harga' => 19000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Coklat', 'harga' => 20000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Keju', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Green Tea', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Oreo', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Taro', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Tiramisu', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Choco Crunchy', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Cappucino', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Mangga', 'harga' => 22000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Hazelnuts', 'harga' => 25000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Chocomaltine', 'harga' => 25000, 'icon' => '☕'],
            ['category_id' => $cappuchino, 'nama' => 'Cappuchino - Nutella', 'harga' => 30000, 'icon' => '☕'],

            // Aneka Mangga
            ['category_id' => $mangga, 'nama' => 'Mangga - Selay', 'harga' => 19000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Coklat', 'harga' => 20000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Keju', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Green Tea', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Oreo', 'harga' => 22000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Taro', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Tiramisu', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Choco Crunchy', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Cappucino', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Mangga', 'harga' => 21000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Hazelnuts', 'harga' => 25000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Chocomaltine', 'harga' => 25000, 'icon' => '🥭'],
            ['category_id' => $mangga, 'nama' => 'Mangga - Nutella', 'harga' => 28000, 'icon' => '🥭'],

            // Cheese Crunchy
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Selay', 'harga' => 19000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Coklat', 'harga' => 19000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Coklat + Kacang', 'harga' => 19000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Green Tea', 'harga' => 20000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Tiramisu', 'harga' => 20000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Choco Crunchy', 'harga' => 20000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Coklat + Pisang', 'harga' => 20000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Oreo', 'harga' => 21000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Cheese Crunchy', 'harga' => 22000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Chocomaltine', 'harga' => 22000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Hazelnuts', 'harga' => 23000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Nutella', 'harga' => 28000, 'icon' => '🧀'],
            ['category_id' => $cheeseCrunchy, 'nama' => 'Cheese Crunchy - Ovomaltine', 'harga' => 28000, 'icon' => '🧀'],

            // Aneka Choco Crunchy
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Selay', 'harga' => 19000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Coklat', 'harga' => 19000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Coklat + Pisang', 'harga' => 21000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Keju', 'harga' => 22000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Green Tea', 'harga' => 22000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Oreo', 'harga' => 22000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Tiramisu', 'harga' => 22000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Coklat + Keju', 'harga' => 22000, 'icon' => '🍫'],
            ['category_id' => $chocoCrunchy, 'nama' => 'Choco Crunchy - Susu', 'harga' => 22000, 'icon' => '🍫'],

            // Aneka Oreo
            ['category_id' => $oreo, 'nama' => 'Oreo - Nanas', 'harga' => 19000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Strawberry', 'harga' => 19000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Blueberry', 'harga' => 19000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Melon', 'harga' => 19000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Coklat', 'harga' => 19000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Durian', 'harga' => 20000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Vanila', 'harga' => 20000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Kacang + Coklat', 'harga' => 20000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Oreo', 'harga' => 21000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Pisang + Coklat', 'harga' => 21000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Keju', 'harga' => 22000, 'icon' => '🍪'],
            ['category_id' => $oreo, 'nama' => 'Oreo - Green Tea', 'harga' => 22000, 'icon' => '🍪'],

            // Aneka Pisang
            ['category_id' => $pisang, 'nama' => 'Pisang - Nanas', 'harga' => 16000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Strawberry', 'harga' => 16000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Blueberry', 'harga' => 16000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Melon', 'harga' => 16000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Susu', 'harga' => 17000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat - Kacang', 'harga' => 17000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Durian', 'harga' => 18000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Vanila', 'harga' => 18000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat - Selay', 'harga' => 18000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat - Keju', 'harga' => 20000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang - Keju', 'harga' => 20000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat / Coklat + Kacang', 'harga' => 20000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat - Keju', 'harga' => 21000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat / Coklat + Keju', 'harga' => 21000, 'icon' => '🍌'],
            ['category_id' => $pisang, 'nama' => 'Pisang + Coklat + Keju (Campur)', 'harga' => 25000, 'icon' => '🍌'],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
