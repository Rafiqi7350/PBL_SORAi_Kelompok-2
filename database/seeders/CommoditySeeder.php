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
                'name' => 'Daging Ayam Broiler Segar',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'government_ref_price' => 36000.00,
            ],
            [
                'name' => 'Telur Ayam Ras Premium',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'government_ref_price' => 28500.00,
            ],
            [
                'name' => 'Daging Sapi Lokal Super',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'government_ref_price' => 135000.00,
            ],
            [
                'name' => 'Bawang Merah Probolinggo',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'government_ref_price' => 30000.00,
            ],
            [
                'name' => 'Bawang Putih Honan',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'government_ref_price' => 38000.00,
            ],
            [
                'name' => 'Cabai Merah Keriting',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'government_ref_price' => 45000.00,
            ],
            [
                'name' => 'Beras Medium SPHP',
                'category' => 'Sembako',
                'unit' => 'kg',
                'government_ref_price' => 12500.00,
            ],
            [
                'name' => 'Minyak Goreng Sawit Kita',
                'category' => 'Sembako',
                'unit' => 'Liter',
                'government_ref_price' => 15700.00,
            ],
            [
                'name' => 'Ikan Gurame Segar',
                'category' => 'Perikanan & Seafood',
                'unit' => 'kg',
                'government_ref_price' => 42000.00,
            ],
            [
                'name' => 'Tomat Merah Segar',
                'category' => 'Sayuran',
                'unit' => 'kg',
                'government_ref_price' => 14000.00,
            ],
        ];

        foreach ($commodities as $data) {
            Commodity::firstOrCreate(
                ['name' => $data['name']],
                [
                    'id' => (string) Str::uuid(),
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                    'government_ref_price' => $data['government_ref_price'],
                ]
            );
        }
    }
}
