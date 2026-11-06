<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\CommodityCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReview;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewTransactionMetricTest extends TestCase
{
    use RefreshDatabase;

    private User $umkmUser;

    private UmkmProfile $umkmProfile;

    private User $supplierUser;

    private SupplierProfile $supplierProfile;

    private Partnership $partnership;

    private Order $completedOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkmUser = User::create([
            'email' => 'umkm.review@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->umkmProfile = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Dapur Sambal Istimewa',
            'owner_name' => 'Siti Aisyah',
            'phone' => '081234567890',
            'address' => 'Jl. Kawi No. 25, Malang',
        ]);

        $this->supplierUser = User::create([
            'email' => 'supplier.metric@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $this->supplierProfile = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'commodity_id' => $category->id,
            'company_name' => 'CV Unggas Sejahtera',
            'owner_name' => 'H. Bambang',
            'phone' => '085712345678',
            'warehouse_address' => 'Jl. Peternakan Sejahtera No. 10',
            'max_capacity' => '8 Ton / Minggu',
            'delivery_area' => 'Malang Raya',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler Segar',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 35000,
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $this->supplierProfile->id,
            'commodity_id' => $commodity->id,
            'price' => 32000,
            'stock_capacity' => 2000,
            'stock_status' => 'Tersedia',
            'delivery_area' => 'Malang',
            'unit' => 'kg',
        ]);

        $this->partnership = Partnership::create([
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'submission_details' => 'Kontrak Ayam Segar',
            'status' => 'active',
        ]);

        $this->completedOrder = Order::create([
            'partnership_id' => $this->partnership->id,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->toDateString(),
            'total_amount' => 1600000,
            'order_status' => Order::STATUS_COMPLETED,
        ]);

        OrderItem::create([
            'order_id' => $this->completedOrder->id,
            'supplier_product_id' => $product->id,
            'quantity' => 50,
            'quantity_delivered' => 50,
            'quantity_remaining' => 0,
            'unit_price' => 32000,
            'subtotal' => 1600000,
            'fulfillment_item_status' => 'fulfilled',
        ]);

        Payment::create([
            'order_id' => $this->completedOrder->id,
            'shipment_batch' => 1,
            'invoice_number' => 'INV-METRIC-001',
            'amount' => 1600000,
            'payment_status' => 'PAID',
        ]);
    }

    // UMKM buka tab rating dan ulasan di katalog
    public function test_umkm_can_view_rating_tab_in_katalog(): void
    {
        $this->actingAs($this->umkmUser);

        $response = $this->get('/umkm/katalog?tab=pemesanan&subtab=rating');

        $response->assertStatus(200);
        $response->assertViewIs('umkm.katalog.index');
    }

    // UMKM kirim rating bintang dan testimoni untuk order selesai
    public function test_umkm_can_store_order_review(): void
    {
        $this->actingAs($this->umkmUser);

        $response = $this->postJson("/umkm/pemesanan/{$this->completedOrder->id}/ulasan", [
            'rating' => 5,
            'review' => 'Kualitas karkas ayam broiler sangat segar dan tiba tepat sebelum jam persiapan masak.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('order_reviews', [
            'order_id' => $this->completedOrder->id,
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'rating' => 5,
            'review' => 'Kualitas karkas ayam broiler sangat segar dan tiba tepat sebelum jam persiapan masak.',
        ]);
    }

    // Update ulasan yang sudah pernah dibuat
    public function test_umkm_can_update_existing_order_review(): void
    {
        $this->actingAs($this->umkmUser);

        OrderReview::create([
            'order_id' => $this->completedOrder->id,
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'rating' => 4,
            'review' => 'Pengiriman tepat waktu.',
        ]);

        $response = $this->postJson("/umkm/pemesanan/{$this->completedOrder->id}/ulasan", [
            'rating' => 5,
            'review' => 'Setelah dicek kembali, seluruh kualitas daging grade A sempurna!',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_reviews', [
            'order_id' => $this->completedOrder->id,
            'rating' => 5,
            'review' => 'Setelah dicek kembali, seluruh kualitas daging grade A sempurna!',
        ]);

        $this->assertEquals(1, OrderReview::where('order_id', $this->completedOrder->id)->count());
    }

    // Validasi nilai rating minimal 1 dan maksimal 5
    public function test_order_review_requires_valid_data(): void
    {
        $this->actingAs($this->umkmUser);

        $responseInvalidRating = $this->postJson("/umkm/pemesanan/{$this->completedOrder->id}/ulasan", [
            'rating' => 6,
            'review' => 'Rating tidak boleh lebih dari 5.',
        ]);
        $responseInvalidRating->assertStatus(422);
        $responseInvalidRating->assertJsonValidationErrors(['rating']);

        $responseEmpty = $this->postJson("/umkm/pemesanan/{$this->completedOrder->id}/ulasan", [
            'rating' => 0,
            'review' => '',
        ]);
        $responseEmpty->assertStatus(422);
        $responseEmpty->assertJsonValidationErrors(['rating', 'review']);
    }

    // Guest diarahkan ke login saat akses riwayat transaksi
    public function test_guest_is_redirected_when_accessing_riwayat_transaksi(): void
    {
        $response = $this->get('/supplier/riwayat-transaksi');

        $response->assertRedirect(route('login'));
    }

    // Pemasok buka riwayat transaksi pengadaan
    public function test_authenticated_supplier_can_access_riwayat_transaksi_page(): void
    {
        $this->actingAs($this->supplierUser);

        $response = $this->get('/supplier/riwayat-transaksi');

        $response->assertStatus(200);
        $response->assertViewIs('supplier.riwayat-transaksi.index');
        $response->assertViewHasAll([
            'transactions',
            'totalUangDidapat',
            'totalBelumDibayar',
            'countLunas',
            'countBelumDibayar',
        ]);
    }

    // Filter status transaksi paid dan unpaid
    public function test_supplier_can_filter_riwayat_transaksi_by_status(): void
    {
        $this->actingAs($this->supplierUser);

        $responsePaid = $this->get('/supplier/riwayat-transaksi?status=paid');
        $responsePaid->assertStatus(200);

        $responseUnpaid = $this->get('/supplier/riwayat-transaksi?status=unpaid');
        $responseUnpaid->assertStatus(200);
    }

    // Guest diredirect ke login jika buka dashboard pemasok
    public function test_guest_is_redirected_to_login_when_accessing_supplier_dashboard(): void
    {
        $response = $this->get('/supplier/dashboard');

        $response->assertRedirect(route('login'));
    }

    // Pemasok melihat ringkasan performa omset dan fulfillment rate
    public function test_authenticated_supplier_can_access_dashboard_and_view_metrics(): void
    {
        $this->actingAs($this->supplierUser);

        $response = $this->get('/supplier/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('supplier.dashboard.index');
        $response->assertViewHasAll([
            'totalOmset',
            'growthPercentage',
            'activeOrdersCount',
            'fulfillmentRate',
            'totalStokTerjual',
            'topCommodityName',
            'monthsTrend',
            'transactions',
        ]);
    }
}
