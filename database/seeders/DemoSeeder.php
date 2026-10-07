<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
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
            ['name' => 'Perikanan & Seafood', 'description' => 'Ikan air tawar, ikan laut, udang, cumi.'],
            ['name' => 'Telur', 'description' => 'Telur ayam ras, telur puyuh, telur bebek.'],
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
            ['name' => 'kg', 'symbol' => 'kg', 'description' => 'Kilogram (standar berat grosir)'],
            ['name' => 'liter', 'symbol' => 'L', 'description' => 'Liter (standar volume minyak/cairan)'],
            ['name' => 'pcs', 'symbol' => 'pcs', 'description' => 'Pieces / Butir / Buah'],
            ['name' => 'dus', 'symbol' => 'dus', 'description' => 'Karton / Dus kemasan grosir'],
            ['name' => 'gram', 'symbol' => 'g', 'description' => 'Gram'],
            ['name' => 'ikat', 'symbol' => 'ikt', 'description' => 'Ikat (sayuran)'],
            ['name' => 'karung', 'symbol' => 'krg', 'description' => 'Karung 25kg/50kg'],
        ];

        foreach ($units as $u) {
            CommodityUnit::updateOrCreate(
                ['name' => $u['name']],
                [
                    'id' => (string) Str::uuid(),
                    'symbol' => $u['symbol'],
                    'description' => $u['description'],
                ]
            );
        }

        // 4. Users & Profiles
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'admin', 'status' => 'active']
        );

        $umkmUser = User::firstOrCreate(
            ['email' => 'umkm@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'umkm', 'status' => 'active']
        );

        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $supplierUser2 = User::firstOrCreate(
            ['email' => 'supplier2@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $umkm = UmkmProfile::firstOrCreate(
            ['user_id' => $umkmUser->id],
            [
                'business_name' => 'Resto Ayam & Bebek Bakar Budi',
                'owner_name' => 'Budi Santoso',
                'phone' => '081122334455',
                'address' => 'Jl. Merdeka Raya No. 12, Jakarta Selatan',
            ]
        );

        $supplier = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser->id],
            [
                'company_name' => 'Kelompok Tani Poultry Jaya',
                'owner_name' => 'H. Tani Kurniawan',
                'phone' => '089988776655',
                'warehouse_address' => 'Pergudangan Tani Jaya, Blok A1',
                'max_capacity' => '10 Ton / Minggu',
                'delivery_area' => 'Jabodetabek & Karawang',
                'nib_number' => '9120004561234',
            ]
        );

        $supplier2 = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser2->id],
            [
                'company_name' => 'CV Berkah Tani Nusantara',
                'owner_name' => 'Siti Rahayu',
                'phone' => '081234567890',
                'warehouse_address' => 'Jl. Raya Pertanian No. 5, Bogor',
                'max_capacity' => '5 Ton / Minggu',
                'delivery_area' => 'Jabodetabek',
                'nib_number' => '9120004567890',
            ]
        );

        // Fetch Commodities matching the UI screenshot & user request
        $ayam = Commodity::where('name', 'like', '%Daging Ayam%')->first();
        $bawang = Commodity::where('name', 'like', '%Bawang Merah%')->first();
        $telur = Commodity::where('name', 'like', '%Telur Ayam%')->first();
        $cabai = Commodity::where('name', 'like', '%Cabai%')->first();
        $beras = Commodity::where('name', 'like', '%Beras%')->first();

        // 5. Seed / Update Supplier Products with high quality clear images
        if ($ayam) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $ayam->id],
                [
                    'price' => 34500.00,
                    'stock_capacity' => '1.500 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '20 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Super',
                    'description' => 'Ayam potong segar harian potong subuh. Bersih tanpa jeroan, es batu pengawet alami.',
                    'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($telur) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $telur->id],
                [
                    'price' => 27000.00,
                    'stock_capacity' => '800 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Segar',
                    'description' => 'Telur ayam ras segar harian dari farm ternak bersertifikat.',
                    'image_url' => 'https://images.unsplash.com/photo-1518569656558-1f25e69d2d2d?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($bawang) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $bawang->id],
                [
                    'price' => 28000.00,
                    'stock_capacity' => '1.200 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($cabai) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier2->id, 'commodity_id' => $cabai->id],
                [
                    'price' => 42000.00,
                    'stock_capacity' => '300 kg / minggu',
                    'stock_status' => 'Stok Terbatas',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Lokal Petik Segar',
                    'description' => 'Cabai rawit merah segar petik pagi hari dari petani lokal Garut.',
                    'image_url' => 'https://images.unsplash.com/photo-1588252279915-f1b2ab5dc0b0?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($beras) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $beras->id],
                [
                    'price' => 12500.00,
                    'stock_capacity' => '1.500 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '25 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Premium IR64',
                    'description' => 'Beras putih medium kualitas premium, pulen dan wangi.',
                    'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }
    }
}
