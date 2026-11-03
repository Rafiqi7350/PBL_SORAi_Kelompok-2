<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RiwayatTransaksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_accessing_riwayat_transaksi(): void
    {
        $response = $this->get(route('supplier.riwayat-transaksi.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_supplier_can_access_riwayat_transaksi_page(): void
    {
        $user = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'CV Sumber Rejeki Tani',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'warehouse_address' => 'Pergudangan Sentra C-12, Malang',
            'max_capacity' => '5 Ton',
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
            'business_name' => 'Ayam Geprek Sambal Korek',
            'owner_name' => 'Ibu Rahmawati',
            'phone' => '0821-4455-6677',
            'address' => 'Jl. Soekarno Hatta No. 45, Malang',
        ]);

        $partnership = Partnership::create([
            'supplier_id' => $supplier->id,
            'umkm_id' => $umkm->id,
            'status' => 'active',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'total_amount' => 1500000,
            'order_status' => Order::STATUS_SHIPPED,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'invoice_number' => 'INV/TEST/B1',
            'amount' => 1500000,
            'payment_status' => 'PAID',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('supplier.riwayat-transaksi.index'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi Pemasok');
        $response->assertSee('Total Uang yang Didapat');
        $response->assertSee('Total Belum Dibayar');
        $response->assertSee('INV/TEST/B1');
    }

    public function test_supplier_can_filter_riwayat_transaksi_by_status(): void
    {
        $user = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'CV Sumber Rejeki Tani',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'warehouse_address' => 'Pergudangan Sentra C-12, Malang',
            'max_capacity' => '5 Ton',
            'delivery_area' => 'Malang Raya',
            'nib_number' => '1234567890123',
        ]);

        $response = $this->actingAs($user)->get(route('supplier.riwayat-transaksi.index', ['status' => 'paid']));

        $response->assertStatus(200);
        $response->assertViewHas('statusFilter', 'paid');
    }
}
