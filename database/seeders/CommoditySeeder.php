<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommoditySeeder extends Seeder
{
    public function run(): void
    {
        $commodities = [
            [
                'name' => 'Peternakan (Ayam, Daging, Telur)',
                'category' => 'Daging & Unggas',
                'unit' => 'Kg',
            ],
            [
                'name' => 'Sayur & Buah Dapur',
                'category' => 'Sayuran',
                'unit' => 'Kg',
            ],
            [
                'name' => 'Bumbu & Rempah',
                'category' => 'Bumbu',
                'unit' => 'Kg',
            ],
            [
                'name' => 'Sembako & Minyak',
                'category' => 'Sembako',
                'unit' => 'Kg/Liter',
            ],
            [
                'name' => 'Perikanan & Seafood',
                'category' => 'Ikan',
                'unit' => 'Kg',
            ],
            [
                'name' => 'Lainnya',
                'category' => 'Umum',
                'unit' => 'Item',
            ],
        ];

        foreach ($commodities as $data) {
            Commodity::firstOrCreate(
                ['name' => $data['name']],
                [
                    'id' => (string) Str::uuid(),
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                ]
            );
        }
    }
}
