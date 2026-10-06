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
                    'description' => $cat['description']
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
            ['name' => 'karung', 'symbol-[#]' => 'krg', 'description' => 'Karung 25kg/50kg'],
        ];

        foreach ($units as $u) {
            CommodityUnit::firstOrCreate(
                ['name' => $u['name']],
                [
                    'id' => (string) Str::uuid(),
                    'symbol' => $u['symbol'] ?? $u['name'],
                    'description' => $u['description']
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
                'address' => 'Jl. Merdeka Raya No. 12, Jakarta Selatan'
            ]
        );

        $supplier = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser->id],
            [
                'company_name' => 'PT Tani Poultry & Ternak Makmur',
                'owner_name' => 'H. Tani Kurniawan',
                'phone' => '089988776655',
                'warehouse_address' => 'Kawasan Pergudangan Tani Jaya, Blok A1-A3, Tambun',
                'max_capacity' => '10 Ton / Minggu',
                'delivery_area' => 'Jabodetabek & Karawang',
                'nib_number' => '9120004561234'
            ]
        );

        $supplier2 = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser2->id],
            [
                'company_name' => 'CV Bumbu Nusantara Berkah',
                'owner_name' => 'Siti Rahmawati',
                'phone' => '085711223344',
                'warehouse_address' => 'Pasar Induk Kramat Jati Blok C No. 45',
                'max_capacity' => '5 Ton / Minggu',
                'delivery_area' => 'DKI Jakarta',
                'nib_number' => '8120007890123'
            ]
        );

        // Fetch Commodities
        $ayam = Commodity::where('name', 'Daging Ayam Broiler Segar')->first();
        $telur = Commodity::where('name', 'Telur Ayam Ras Premium')->first();
        $bawangMerah = Commodity::where('name', 'Bawang Merah Probolinggo')->first();
        $bawangPutih = Commodity::where('name', 'Bawang Putih Honan')->first();
        $cabai = Commodity::where('name', 'Cabai Merah Keriting')->first();
        $beras = Commodity::where('name', 'Beras Medium SPHP')->first();

        // 5. Products for Supplier 1 (PT Tani Poultry)
        if ($ayam) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $ayam->id],
                [
                    'price' => 34500.00,
                    'stock_capacity' => '1.500 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '20 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Super',
                    'description' => 'Ayam potong segar harian potong subuh. Bersih tanpa jeroan, es batu pengawet alami.',
                    'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }

        if ($telur) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $telur->id],
                [
                    'price' => 27000.00,
                    'stock_capacity' => '800 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Premium',
                    'description' => 'Telur ayam ras peternakan sendiri. Cangkang tebal, tidak retak, ukuran seragam.',
                    'image_url' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }

        if ($beras) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $beras->id],
                [
                    'price' => 12000.00,
                    'stock_capacity' => '3.000 kg / bulan',
                    'stock_status' => 'Stok Terbatas',
                    'minimum_order' => '50 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Super Head 5%',
                    'description' => 'Beras putih pulen tanpa pemutih dan tanpa pengawet. Kemasan karung 50kg.',
                    'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }

        // 6. Products for Supplier 2 (CV Bumbu Nusantara Berkah)
        if ($bawangMerah) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier2->id, 'commodity_id' => $bawangMerah->id],
                [
                    'price' => 28500.00,
                    'stock_capacity' => '1.200 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Super Biru Probolinggo',
                    'description' => 'Bawang merah Probolinggo kering olahan, siap masak, aroma tajam khas.',
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }

        if ($bawangPutih) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier2->id, 'commodity_id' => $bawangPutih->id],
                [
                    'price' => 36500.00,
                    'stock_capacity' => '600 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Honan Super A',
                    'description' => 'Bawang putih kating honan bersih padat.',
                    'image_url' => 'https://images.unsplash.com/photo-1540148426945-6cf22a6b2383?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }

        if ($cabai) {
            SupplierProduct::firstOrCreate(
                ['supplier_id' => $supplier2->id, 'commodity_id' => $cabai->id],
                [
                    'price' => 43000.00,
                    'stock_capacity' => '400 kg / minggu',
                    'stock_status' => 'Stok Terbatas',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Lokal Petik Segar',
                    'description' => 'Cabai merah keriting segar petik pagi hari dari petani lokal Garut.',
                    'image_url' => 'https://images.unsplash.com/photo-1566378246598-5b11a0d486cc?auto=format&fit=crop&q=80&w=600'
                ]
            );
        }
    }
}
