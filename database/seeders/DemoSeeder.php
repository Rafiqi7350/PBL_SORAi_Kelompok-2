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
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $supplierUser2 = User::firstOrCreate(
            ['email' => 'supplier2@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
        );

        $umkmUser = User::firstOrCreate(
            ['email' => 'umkm@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'umkm', 'status' => 'active']
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
        $minyak = Commodity::where('name', 'like', '%Minyak Goreng%')->first();
        $sapiCom = Commodity::where('name', 'like', '%Daging Sapi%')->first();
        $bawangPutih = Commodity::where('name', 'like', '%Bawang Putih%')->first();
        $jahe = Commodity::where('name', 'like', '%Jahe%')->first();
        $tomat = Commodity::where('name', 'like', '%Tomat%')->first();
        $wortel = Commodity::where('name', 'like', '%Wortel%')->first();
        $gula = Commodity::where('name', 'like', '%Gula Pasir%')->first();

        // 5. Seed / Update Supplier Products with high quality clear images
        if ($ayam) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $ayam->id],
                [
                    'price' => 34000.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 500,
                    'stock_capacity' => '500 kg/minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
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
                    'price' => 28000.00,
                    'unit' => 'kg',
                    'weekly_capacity' => 1200,
                    'stock_capacity' => '1.200 kg/minggu',
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
                    'unit' => 'kg',
                    'stock_capacity' => '1.200 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Probolinggo',
                    'description' => 'Bawang merah biru Probolinggo kering, merah segar, ukuran seragam.',
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($cabai) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $cabai->id],
                [
                    'price' => 42000.00,
                    'unit' => 'kg',
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
                    'unit' => 'kg',
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

        if ($minyak) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $minyak->id],
                [
                    'price' => 15700.00,
                    'unit' => 'Liter',
                    'stock_capacity' => '800 Liter / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '20 Liter',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Refined Bleached Deodorized',
                    'description' => 'Minyak goreng sawit berkualitas, jernih, bebas kotoran, kemasan standar grosir.',
                    'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($sapiCom) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $sapiCom->id],
                [
                    'price' => 135000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '200 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Super Lokal',
                    'description' => 'Daging sapi lokal segar potong pagi, tanpa lemak berlebih, cocok untuk restoran.',
                    'image_url' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($bawangPutih) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $bawangPutih->id],
                [
                    'price' => 38000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '500 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Impor Honan',
                    'description' => 'Bawang putih Honan impor, umbi besar, aroma tajam, cocok untuk bumbu masakan profesional.',
                    'image_url' => 'https://images.unsplash.com/photo-1501420193069-ede7e68cbf8a?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($jahe) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $jahe->id],
                [
                    'price' => 25000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '400 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '5 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Jahe Emprit Segar',
                    'description' => 'Jahe segar bersih, aroma kuat, cocok untuk kebutuhan dapur restoran dan minuman tradisional.',
                    'image_url' => 'https://images.unsplash.com/photo-1615485500704-8e990f9900f7?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($tomat) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $tomat->id],
                [
                    'price' => 8000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '600 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Merah Segar',
                    'description' => 'Tomat segar merah matang pohon, tidak cacat, segar dan berair untuk sambal dan masakan.',
                    'image_url' => 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($wortel) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $wortel->id],
                [
                    'price' => 9000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '700 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '10 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Grade A Lokal',
                    'description' => 'Wortel segar lokal, warna oranye cerah, besar dan mulus, langsung dari kebun Lembang.',
                    'image_url' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }

        if ($gula) {
            SupplierProduct::updateOrCreate(
                ['supplier_id' => $supplier->id, 'commodity_id' => $gula->id],
                [
                    'price' => 14000.00,
                    'unit' => 'kg',
                    'stock_capacity' => '2.000 kg / minggu',
                    'stock_status' => 'Tersedia',
                    'minimum_order' => '25 kg',
                    'is_halal_certified' => true,
                    'quality_grade' => 'Gula Kristal Putih Premium',
                    'description' => 'Gula pasir kristal putih premium, bersih dan bebas kotoran, kemasan karung 50kg.',
                    'image_url' => 'https://images.unsplash.com/photo-1559742811-822873691df8?auto=format&fit=crop&q=80&w=600',
                ]
            );
        }
    }
}
