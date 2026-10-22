<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\CommodityUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
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
                'business_name' => 'Dapur Sambal Malang',
                'owner_name' => 'Budi Santoso',
                'phone' => '081122334455',
                'address' => 'Jl. Merdeka Raya No. 12, Malang',
            ]
        );

        $supplier = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser->id],
            [
                'company_name' => 'Kelompok Tani Poultry Malang',
                'owner_name' => 'H. Tani Kurniawan',
                'phone' => '089988776655',
                'warehouse_address' => 'Pergudangan Tani Jaya, Blok A1, Malang',
                'max_capacity' => '10 Ton / Minggu',
                'delivery_area' => 'Jawa Timur & Jabodetabek',
                'nib_number' => '9120004561234',
            ]
        );

        $supplier2 = SupplierProfile::firstOrCreate(
            ['user_id' => $supplierUser2->id],
            [
                'company_name' => 'Distributor Rempah Nusantara',
                'owner_name' => 'Siti Rahayu',
                'phone' => '081234567890',
                'warehouse_address' => 'Jl. Raya Pertanian No. 5, Probolinggo',
                'max_capacity' => '5 Ton / Minggu',
                'delivery_area' => 'Jawa Timur & Jabodetabek',
                'nib_number' => '9120004567890',
            ]
        );

        // 5. Seed Supplier Products
        $commodityAyam = Commodity::firstOrCreate(
            ['name' => 'Daging Ayam Broiler Segar'],
            [
                'category' => 'Daging & Unggas',
                'unit' => 'kg',
                'government_ref_price' => 36000.00,
            ]
        );

        $commodityBawang = Commodity::firstOrCreate(
            ['name' => 'Bawang Merah Biru Probolinggo'],
            [
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'government_ref_price' => 30000.00,
            ]
        );

        $productAyam = SupplierProduct::firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'commodity_id' => $commodityAyam->id,
            ],
            [
                'price' => 34000.00,
                'stock_capacity' => '10 Ton / Minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '10 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Grade A Super',
            ]
        );

        $productBawang = SupplierProduct::firstOrCreate(
            [
                'supplier_id' => $supplier2->id,
                'commodity_id' => $commodityBawang->id,
            ],
            [
                'price' => 28000.00,
                'stock_capacity' => '5 Ton / Minggu',
                'stock_status' => 'Tersedia',
                'minimum_order' => '5 kg',
                'is_halal_certified' => true,
                'quality_grade' => 'Grade Super',
            ]
        );

        // 6. Partnerships
        $partnership1 = Partnership::firstOrCreate(
            [
                'umkm_id' => $umkm->id,
                'supplier_id' => $supplier->id,
            ],
            [
                'submission_details' => 'Pasokan Rutin Daging Ayam Broiler Segar',
                'status' => 'active',
            ]
        );

        $partnership2 = Partnership::firstOrCreate(
            [
                'umkm_id' => $umkm->id,
                'supplier_id' => $supplier2->id,
            ],
            [
                'submission_details' => 'Pasokan Rutin Bumbu & Rempah',
                'status' => 'active',
            ]
        );

        // 7. Seed 6-Month Realistic Orders (Mei s/d Oktober 2026)
        $historicalOrders = [
            // Bulan 1: Mei 2026 (Total 200 kg @ Rp 34.000 = Rp 6.800.000)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-05-15',
                'delivery_date' => '2026-05-16',
                'qty' => 200,
                'unit_price' => 34000,
                'total' => 6800000,
                'status' => Order::STATUS_COMPLETED,
            ],
            // Bulan 2: Juni 2026 (Total 270 kg @ Rp 34.000 = Rp 9.180.000)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-06-18',
                'delivery_date' => '2026-06-19',
                'qty' => 270,
                'unit_price' => 34000,
                'total' => 9180000,
                'status' => Order::STATUS_COMPLETED,
            ],
            // Bulan 3: Juli 2026 (Total 320 kg @ Rp 34.000 = Rp 10.880.000)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-07-20',
                'delivery_date' => '2026-07-21',
                'qty' => 320,
                'unit_price' => 34000,
                'total' => 10880000,
                'status' => Order::STATUS_COMPLETED,
            ],
            // Bulan 4: Agustus 2026 (Total 350 kg @ Rp 34.000 = Rp 11.900.000)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-08-16',
                'delivery_date' => '2026-08-17',
                'qty' => 350,
                'unit_price' => 34000,
                'total' => 11900000,
                'status' => Order::STATUS_COMPLETED,
            ],
            // Bulan 5: September 2026 (Total 390 kg)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-09-15',
                'delivery_date' => '2026-09-16',
                'qty' => 150,
                'unit_price' => 34000,
                'total' => 5100000,
                'status' => Order::STATUS_COMPLETED, // ORD-2026-004
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-09-22',
                'delivery_date' => '2026-09-23',
                'qty' => 50,
                'unit_price' => 28000,
                'total' => 1400000,
                'status' => Order::STATUS_SHIPPED, // ORD-2026-003
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-09-28',
                'delivery_date' => '2026-09-29',
                'qty' => 120,
                'unit_price' => 34000,
                'total' => 4080000,
                'status' => Order::STATUS_WAITING_BACKORDER_APPROVAL, // ORD-2026-002
                'remaining' => 20,
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-09-30',
                'delivery_date' => '2026-10-01',
                'qty' => 70,
                'unit_price' => 34000,
                'total' => 2380000,
                'status' => Order::STATUS_COMPLETED,
            ],
            // Bulan 6: Oktober 2026 (Aktif - Total 380 kg & Omset Selesai Rp 15.300.000)
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-10-01',
                'delivery_date' => '2026-10-02',
                'qty' => 100,
                'unit_price' => 34000,
                'total' => 3400000,
                'status' => Order::STATUS_CONFIRMED, // ORD-2026-001
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-10-08',
                'delivery_date' => '2026-10-09',
                'qty' => 150,
                'unit_price' => 34000,
                'total' => 5100000,
                'status' => Order::STATUS_COMPLETED,
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-10-15',
                'delivery_date' => '2026-10-16',
                'qty' => 130,
                'unit_price' => 34000,
                'total' => 4420000,
                'status' => Order::STATUS_COMPLETED,
            ],
            [
                'partnership' => $partnership1,
                'product' => $productAyam,
                'order_date' => '2026-10-20',
                'delivery_date' => '2026-10-21',
                'qty' => 170,
                'unit_price' => 34000,
                'total' => 5780000,
                'status' => Order::STATUS_COMPLETED,
            ],
        ];

        foreach ($historicalOrders as $data) {
            $order = Order::firstOrCreate(
                [
                    'partnership_id' => $data['partnership']->id,
                    'order_date' => $data['order_date'],
                    'total_amount' => $data['total'],
                ],
                [
                    'delivery_date' => $data['delivery_date'],
                    'order_status' => $data['status'],
                ]
            );

            OrderItem::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'supplier_product_id' => $data['product']->id,
                ],
                [
                    'quantity' => $data['qty'],
                    'quantity_delivered' => $data['qty'] - ($data['remaining'] ?? 0),
                    'quantity_remaining' => $data['remaining'] ?? 0,
                    'unit_price' => $data['unit_price'],
                    'subtotal' => $data['total'],
                    'fulfillment_item_status' => ($data['status'] === Order::STATUS_COMPLETED) ? 'fulfilled' : 'pending',
                ]
            );
        }
    }
}
