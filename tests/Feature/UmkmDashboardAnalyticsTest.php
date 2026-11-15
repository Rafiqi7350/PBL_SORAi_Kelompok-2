<?php

namespace Tests\Feature;

use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UmkmDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_umkm_account_dashboard_shows_zero_metrics(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pemilik Baru UMKM',
            'email' => 'umkmbaru@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'business_name' => 'Dapur Kuliner Baru',
            'owner_name' => 'Pemilik Baru',
            'phone' => '081234567890',
            'address' => 'Jl. Kuliner No. 1, Malang',
        ]);

        $response = $this->actingAs($user)->get('/umkm/katalog?tab=dashboard');

        $response->assertStatus(200);

        $response->assertViewHas('totalPengeluaranBulanIni', 0.0);
        $response->assertViewHas('activeOrdersCount', 0);
        $response->assertViewHas('pendingValidationCount', 0);
        $response->assertViewHas('totalDiterimaKg', 0.0);
        $response->assertViewHas('onTimeDeliveryRate', 0.0);
        $response->assertViewHas('activeContractsCount', 0);
        $response->assertViewHas('pendingContractsCount', 0);
        $response->assertViewHas('totalContractsCount', 0);
        $response->assertViewHas('targetBulanDepan', 0.0);

        $response->assertSee('Rp 0');
        $response->assertSee('0 Transaksi');
        $response->assertSee('0 kg');
        $response->assertSee('Belum Ada Riwayat Transaksi');
        $response->assertDontSee('Rp 12.220.000');
        $response->assertDontSee('Hemat 12% via Harga Grosir');
        $response->assertDontSee('2 Transaksi');
    }

    public function test_dashboard_analytics_api_endpoint_returns_zero_for_new_umkm(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pemilik Baru UMKM 2',
            'email' => 'umkmbaru2@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'business_name' => 'Dapur Segar Baru',
            'owner_name' => 'Pemilik Baru 2',
            'phone' => '081234567891',
            'address' => 'Jl. Segar No. 2, Malang',
        ]);

        $response = $this->actingAs($user)->getJson('/umkm/dashboard/analytics');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'summary_cards' => [
                    'total_pengeluaran' => [
                        'amount' => 0,
                        'formatted' => 'Rp 0',
                    ],
                    'pesanan_aktif' => [
                        'total' => 0,
                        'need_confirmation' => 0,
                    ],
                    'total_diterima' => [
                        'quantity_kg' => 0,
                        'formatted' => '0 kg',
                    ],
                    'on_time_delivery' => [
                        'score_pct' => 0,
                        'formatted' => '0.0%',
                    ],
                ],
            ],
        ]);
    }
}
