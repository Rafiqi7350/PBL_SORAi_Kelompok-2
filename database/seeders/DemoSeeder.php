<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Master Commodities
        $this->call(CommoditySeeder::class);

        // 2. Seed Master Categories
        $categories = [
            ['name' => 'Daging & Unggas', 'description' => 'Ayam, bebek, sapi, kambing, dan produk olahan ternak.'],
            ['name' => 'Bumbu & Rempah', 'description' => 'Bawang merah, bawang putih, cabai, jahe, kunyit, dll.'],
            ['name' => 'Sayuran', 'description' => 'Sayuran segar harian, tomat, wortel, kubis, dll.'],
            ['name' => 'Sembako', 'description' => 'Beras, minyak goreng, gula, tepung, dll.'],
        ];

        foreach ($categories as $cat) {
            CommodityCategory::firstOrCreate(
                ['name' => $cat['name']],
                [
                    'id' => (string) Str::uuid(),
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                ]
            );
        }

        // 3. Seed Master Units
        $units = [
            ['name' => 'kg', 'symbol' => 'kg', 'description' => 'Kilogram'],
            ['name' => 'liter', 'symbol' => 'L', 'description' => 'Liter'],
            ['name' => 'pcs', 'symbol' => 'pcs', 'description' => 'Pieces / Buah'],
            ['name' => 'dus', 'symbol' => 'dus', 'description' => 'Dus / Karton'],
        ];

        foreach ($units as $u) {
            CommodityUnit::firstOrCreate(
                ['name' => $u['name']],
                [
                    'id' => (string) Str::uuid(),
                    'symbol' => $u['symbol'],
                    'description' => $u['description'],
                ]
            );
        }

        // 4. Users & Profiles
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $supplier = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser->id],
            [
                'company_name' => 'Kelompok Tani Poultry ...',
                'owner_name' => 'H. Tani Kurniawan',
                'phone' => '089988776655',
                'warehouse_address' => 'Pergudangan Tani Jaya, Blok A1',
                'max_capacity' => '10 Ton / Minggu',
                'delivery_area' => 'Jabodetabek & Karawang',
                'nib_number' => '9120004561234',
            ]
        );

        // Fetch Commodities matching the UI screenshot
        $ayam = Commodity::where('name', 'Daging Ayam Broiler Segar')->first();
        $bawang = Commodity::where('name', 'Bawang Merah Biru Probolinggo')->first();
        $telur = Commodity::where('name', 'Telur Ayam Ras Grade A')->first();
        $cabai = Commodity::where('name', 'Cabai Rawit Merah Super')->first();

        // 5. Seed Supplier Products (exact data matching user screenshot)
        if ($ayam) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $ayam->id],
                [
                    'price' => 34000.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 500,
                    'stock_capacity' => '500 kg/minggu',
                    'stock_status' => 'ready',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&q=80&w=200',
                ]
            );
        }

        if ($bawang) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $bawang->id],
                [
                    'price' => 28000.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 1200,
                    'stock_capacity' => '1.200 kg/minggu',
                    'stock_status' => 'ready',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=200',
                ]
            );
        }

        if ($telur) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $telur->id],
                [
                    'price' => 26500.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 2000,
                    'stock_capacity' => '2.000 kg/minggu',
                    'stock_status' => 'ready',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&q=80&w=200',
                ]
            );
        }

        if ($cabai) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $cabai->id],
                [
                    'price' => 42000.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 300,
                    'stock_capacity' => '300 kg/minggu',
                    'stock_status' => 'limited',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1566378246598-5b11a0d486cc?auto=format&fit=crop&q=80&w=200',
                ]
            );
        }
    }
}
