<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierDashboardZeroStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_supplier_account_dashboard_shows_zero_metrics_and_empty_charts(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pemasok Baru Sorai',
            'email' => 'pemasokbaru@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_name' => 'Kelompok Tani Pemasok Baru',
            'owner_name' => 'Ketua Tani Baru',
            'phone' => '081298765432',
            'warehouse_address' => 'Jl. Kebun Makmur No. 9, Batu',
        ]);

        $response = $this->actingAs($user)->get('/supplier/dashboard');

        $response->assertStatus(200);

        // Pastikan variabel dikirim ke view murni bernilai 0
        $response->assertViewHas('totalOmset', 0.0);
        $response->assertViewHas('growthPercentage', 0.0);
        $response->assertViewHas('activeOrdersCount', 0);
        $response->assertViewHas('perluPenyesuaianCount', 0);
        $response->assertViewHas('totalStokTerjual', 0.0);
        $response->assertViewHas('fulfillmentRate', 0.0);
        $response->assertViewHas('topCommodityName', '-');

        // Pastikan view me-render angka 0 dan tidak ada angka dummy
        $response->assertSee('Rp 0');
        $response->assertSeeInOrder(['0', 'Pesanan']);
        $response->assertSeeInOrder(['0', 'kg']);
        $response->assertSee('0.0%');
        $response->assertSee('Belum Ada Riwayat Target &amp; Realisasi Penjualan', false);
        $response->assertSee('Belum ada data transaksi tercatat');
    }

    public function test_new_supplier_account_supply_planning_is_empty(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pemasok Baru Sorai 2',
            'email' => 'pemasokbaru2@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $supplier = SupplierProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_name' => 'Kelompok Tani Pemasok Baru 2',
            'owner_name' => 'Ketua Tani Baru 2',
            'phone' => '081298765433',
            'warehouse_address' => 'Jl. Kebun Makmur No. 10, Batu',
        ]);

        $response = $this->actingAs($user)->get('/supplier/supply-planning');

        $response->assertStatus(200);

        // Pastikan variabel peramalan dan inventori bernilai 0 / kosong
        $response->assertViewHas('totalProjectedDemand7Days', 0.0);
        $response->assertViewHas('criticalInventoryCount', 0);
        $response->assertViewHas('warningInventoryCount', 0);
        $response->assertViewHas('latestForecastRun', null);
        $this->assertCount(0, $response->viewData('forecastItems'));
        $this->assertCount(0, $response->viewData('inventoryItems'));
        $this->assertCount(0, $response->viewData('purchaseSuggestions'));
        $this->assertCount(0, $response->viewData('commodityAggregations'));

        // Pastikan tidak ada data tiruan yang dibuat di database
        $this->assertEquals(0, $supplier->inventoryItems()->count());
        $this->assertEquals(0, $supplier->forecastRuns()->count());
        $this->assertEquals(0, $supplier->purchaseSuggestions()->count());

        // Pastikan view menampilkan empty state
        $response->assertSee('Belum Ada Proyeksi Kebutuhan');
        $response->assertSee('Belum Ada Inventori Bahan Baku');
        $response->assertSee('Semua Stok Bahan Baku Aman');
        $response->assertSee('Belum Ada Komoditas untuk Disimulasikan');
        $response->assertSee('Belum ada estimasi kebutuhan terakumulasi dari UMKM mitra');
    }
}
