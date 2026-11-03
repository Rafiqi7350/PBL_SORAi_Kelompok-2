<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderReview;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createUmkmAndOrder(): array
    {
        $supplierUser = User::create([
            'email' => 'supplier_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'user_id' => $supplierUser->id,
            'company_name' => 'PT Pemasok Mitra Utama',
            'owner_name' => 'Hendra Setiawan',
            'phone' => '081234567890',
            'warehouse_address' => 'Gudang Utama Malang',
            'max_capacity' => '10 Ton',
            'delivery_area' => 'Jawa Timur',
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
            'business_name' => 'Dapur Kuliner Mantap',
            'owner_name' => 'Siti Nurhaliza',
            'phone' => '082199887766',
            'address' => 'Jl. Ijen No. 10, Malang',
        ]);

        $partnership = Partnership::create([
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        $order = Order::create([
            'partnership_id' => $partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'total_amount' => 1500000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'shipment_batch' => 1,
            'invoice_number' => 'INV/TEST/001',
            'amount' => 1500000,
            'payment_status' => 'PAID',
            'payment_method' => 'Bank Transfer Escrow',
            'paid_at' => now(),
        ]);

        return [$umkmUser, $umkm, $supplier, $order];
    }

    public function test_umkm_can_view_rating_tab_in_katalog(): void
    {
        [$umkmUser] = $this->createUmkmAndOrder();

        $response = $this->actingAs($umkmUser)
            ->get(route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'rating']));

        $response->assertOk();
        $response->assertSee('Rating & Ulasan Pemasok', false);
        $response->assertSee('PT Pemasok Mitra Utama');
    }

    public function test_umkm_can_store_order_review(): void
    {
        [$umkmUser, $umkm, $supplier, $order] = $this->createUmkmAndOrder();

        $response = $this->actingAs($umkmUser)
            ->postJson(route('umkm.pemesanan.ulasan', ['orderId' => $order->id]), [
                'rating' => 5,
                'review' => 'Kualitas bahan baku sangat segar dan pengiriman tepat waktu.',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('order_reviews', [
            'order_id' => $order->id,
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'rating' => 5,
            'review' => 'Kualitas bahan baku sangat segar dan pengiriman tepat waktu.',
        ]);
    }

    public function test_umkm_can_update_existing_order_review(): void
    {
        [$umkmUser, $umkm, $supplier, $order] = $this->createUmkmAndOrder();

        // Ulasan awal
        OrderReview::create([
            'order_id' => $order->id,
            'umkm_id' => $umkm->id,
            'supplier_id' => $supplier->id,
            'rating' => 4,
            'review' => 'Bahan baku bagus.',
        ]);

        // Update ulasan
        $response = $this->actingAs($umkmUser)
            ->post(route('umkm.pemesanan.ulasan', ['orderId' => $order->id]), [
                'rating' => 5,
                'review' => 'Setelah dicek kembali, seluruh pasokan sangat memuaskan.',
            ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'pemesanan', 'subtab' => 'rating']));

        $this->assertEquals(1, OrderReview::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('order_reviews', [
            'order_id' => $order->id,
            'rating' => 5,
            'review' => 'Setelah dicek kembali, seluruh pasokan sangat memuaskan.',
        ]);
    }

    public function test_order_review_requires_valid_data(): void
    {
        [$umkmUser, , , $order] = $this->createUmkmAndOrder();

        $response = $this->actingAs($umkmUser)
            ->postJson(route('umkm.pemesanan.ulasan', ['orderId' => $order->id]), [
                'rating' => 6, // invalid > 5
                'review' => '', // required
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rating', 'review']);
    }
}
