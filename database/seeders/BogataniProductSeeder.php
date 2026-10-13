<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BogataniProductSeeder extends Seeder
{
    public function run(): void
    {
        $bogataniProfileId = '01a119af-9e52-72d1-8b9d-350d21f8d6c7';

        $products = [
            [
                'commodity_id' => '6d404f31-b570-4d50-b261-75d8e2ba80fa', // Daging Ayam Broiler Segar
                'price' => 34000,
                'stock_capacity' => '500 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Grade A Super',
                'description' => 'Ayam potong segar harian potong subuh. Bersih tanpa jeroan, es batu pengawet alami.',
                'image_url' => '/images/commodities/daging_ayam.png',
            ],
            [
                'commodity_id' => 'd91632ad-28fd-43e8-8290-b5b4666b2f69', // Telur Ayam Ras Premium
                'price' => 26500,
                'stock_capacity' => '2.000 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Grade A Segar',
                'description' => 'Telur ayam ras segar harian dari farm ternak bersertifikat.',
                'image_url' => '/images/commodities/telur_ayam.png',
            ],
            [
                'commodity_id' => 'b8fdfad4-342c-4d42-8304-f0ba7838e1e9', // Beras Medium SPHP
                'price' => 12500,
                'stock_capacity' => '1.500 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '25 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Premium IR64',
                'description' => 'Beras putih medium kualitas premium, pulen dan wangi.',
                'image_url' => '/images/commodities/beras_medium.png',
            ],
            [
                'commodity_id' => '92aea7a3-d8fd-4b1b-ac8c-d4b9fa87ec76', // Cabai Merah Keriting
                'price' => 42000,
                'stock_capacity' => '300 kg/minggu',
                'stock_status' => 'Stok Terbatas',
                'minimum_order' => '5 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Lokal Petik Segar',
                'description' => 'Cabai merah keriting segar petik pagi hari dari petani lokal.',
                'image_url' => '/images/commodities/cabai_rawit.png',
            ],
        ];

        foreach ($products as $product) {
            $existing = DB::table('supplier_products')
                ->where('supplier_id', $bogataniProfileId)
                ->where('commodity_id', $product['commodity_id'])
                ->exists();

            if (! $existing) {
                DB::table('supplier_products')->insert(array_merge($product, [
                    'id' => (string) Str::uuid(),
                    'supplier_id' => $bogataniProfileId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
