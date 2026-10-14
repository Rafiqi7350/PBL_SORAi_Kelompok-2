<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KatalogDemoSeeder extends Seeder
{
    /**
     * Seed 8 showcase supplier products for the demo supplier (Kelompok Tani Poultry Jaya)
     * with high-quality images so the Kelola Stok menu displays clearly.
     */
    public function run(): void
    {
        // Ensure demo supplier user exists
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@sorai.id'],
            ['password' => Hash::make('password'), 'role' => 'supplier', 'status' => 'active']
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

        /** @var array<int, array{name: string, category: string, unit: string, price: float, stock_capacity: string, stock_status: string, minimum_order: string, quality_grade: string, description: string, image_url: string}> $products */
        $products = [
            [
                'name' => 'Daging Ayam Broiler Segar',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'price' => 34000.00,
                'stock_capacity' => '500 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'quality_grade' => 'Grade A Super',
                'description' => 'Ayam potong segar harian potong subuh. Bersih tanpa jeroan, es batu pengawet alami.',
                'image_url' => '/images/commodities/daging_ayam.png',
            ],
            [
                'name' => 'Bawang Merah Biru Probolinggo',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'price' => 28000.00,
                'stock_capacity' => '1.200 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'quality_grade' => 'Grade A Segar',
                'description' => 'Bawang merah biru varietas Probolinggo, kering, berukuran sedang, aroma kuat.',
                'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Telur Ayam Ras Grade A',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'price' => 26500.00,
                'stock_capacity' => '2.000 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'quality_grade' => 'Grade A Segar',
                'description' => 'Telur ayam ras segar harian dari farm ternak bersertifikat.',
                'image_url' => '/images/commodities/telur_ayam.png',
            ],
            [
                'name' => 'Cabai Rawit Merah Super',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'price' => 42000.00,
                'stock_capacity' => '300 kg/minggu',
                'stock_status' => 'Stok Terbatas',
                'minimum_order' => '5 kg',
                'quality_grade' => 'Lokal Petik Segar',
                'description' => 'Cabai rawit merah segar petik pagi hari dari petani lokal Garut.',
                'image_url' => '/images/commodities/cabai_rawit.png',
            ],
            [
                'name' => 'Beras Medium SPHP',
                'category' => 'Sembako',
                'unit' => 'kg',
                'price' => 12500.00,
                'stock_capacity' => '1.500 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '25 kg',
                'quality_grade' => 'Premium IR64',
                'description' => 'Beras putih medium kualitas premium, pulen dan wangi.',
                'image_url' => '/images/commodities/beras_medium.png',
            ],
            [
                'name' => 'Daging Sapi Lokal Super',
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'price' => 135000.00,
                'stock_capacity' => '200 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '5 kg',
                'quality_grade' => 'Grade A',
                'description' => 'Daging sapi segar lokal, tanpa lemak berlebih, cocok untuk kuliner premium.',
                'image_url' => 'https://images.unsplash.com/photo-1546964124-0cce460f38ef?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Bawang Putih Honan',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'price' => 38000.00,
                'stock_capacity' => '800 kg/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'quality_grade' => 'Impor Honan Pilihan',
                'description' => 'Bawang putih Honan impor, siung besar, aroma tajam, kualitas terjamin.',
                'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Minyak Goreng Sawit Kita',
                'category' => 'Sembako',
                'unit' => 'Liter',
                'price' => 15700.00,
                'stock_capacity' => '2.000 liter/minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 liter',
                'quality_grade' => 'Standar Nasional',
                'description' => 'Minyak goreng sawit bersubsidi program pemerintah, jernih dan sehat.',
                'image_url' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&q=80&w=600',
            ],
        ];

        foreach ($products as $data) {
            // Find or create the commodity
            $commodity = Commodity::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                    'government_ref_price' => $data['price'],
                ]
            );

            // Update image on existing product or create new one
            SupplierProduct::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'commodity_id' => $commodity->id,
                ],
                [
                    'price' => $data['price'],
                    'stock_capacity' => $data['stock_capacity'],
                    'stock_status' => $data['stock_status'],
                    'minimum_order' => $data['minimum_order'],
                    'quality_grade' => $data['quality_grade'],
                    'description' => $data['description'],
                    'image_url' => $data['image_url'],
                    'is_halal_certified' => true,
                ]
            );
        }

        $this->command->info('✅ Berhasil seed 8 produk katalog demo dengan gambar lengkap!');
    }
}
