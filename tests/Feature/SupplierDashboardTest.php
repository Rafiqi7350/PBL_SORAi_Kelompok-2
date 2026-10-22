<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partnership;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_supplier_dashboard(): void
    {
        $response = $this->get(route('supplier.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_supplier_can_access_dashboard_and_view_metrics(): void
    {
        $supplierUser = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pemasok Mandiri Sejahtera',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'warehouse_address' => 'Pergudangan Modern Blok C',
            'max_capacity' => '10 Ton',
            'delivery_area' => 'Malang Raya',
            'nib_number' => '1234567890123',
        ]);

        $umkmUser = User::create([
            'email' => 'umkm_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $umkm = UmkmProfile::create([
            'user_id' => $umkmUser->id,
            'business_name' => 'Dapur Sambal Malang',
            'restaurant_name' => 'Dapur Sambal Malang',
            'owner_name' => 'Siti Nurhaliza',
            'phone' => '089876543210',
            'address' => 'Jl. Ijen No. 10, Malang',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler Segar',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 36000,
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'commodity_id' => $commodity->id,
            'price' => 34000,
            'stock_capacity' => '500 kg/minggu',
            'stock_status' => 'Tersedia',
            'minimum_order' => '10 kg',
            'quality_grade' => 'Grade A Super',
            'is_halal_certified' => true,
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'submission_details' => 'Kemitraan Pasokan Harian Ayam Broiler',
            'status' => 'active',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'total_amount' => 3400000,
            'order_status' => Order::STATUS_CONFIRMED,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'supplier_product_id' => $product->id,
            'quantity' => 100,
            'quantity_delivered' => 100,
            'quantity_remaining' => 0,
            'unit_price' => 34000,
            'subtotal' => 3400000,
            'fulfillment_item_status' => 'fulfilled',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('supplier.dashboard'));

        $response->assertOk();
        $response->assertViewIs('supplier.dashboard.index');
        $response->assertSee('TOTAL OMSET DITERIMA');
        $response->assertSee('PESANAN DIPROSES');
        $response->assertSee('TOTAL STOK TERJUAL');
        $response->assertSee('FULFILLMENT RATE');
        $response->assertSee('Grafik Tren Penjualan Bahan Baku (Kg/Bulan)');
        $response->assertSee('Target');
        $response->assertSee('Realisasi');
        $response->assertSee('Histori & Riwayat Transaksi Selesai', false);
        $response->assertSee('Database B2B SORAI', false);
        $response->assertSee('Dapur Sambal Malang');
        $response->assertSee('Menunggu Konfirmasi Barang');
    }
}
