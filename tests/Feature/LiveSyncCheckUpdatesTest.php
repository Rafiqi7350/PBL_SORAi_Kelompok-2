<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveSyncCheckUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_check_updates_returns_valid_fingerprint(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pemasok Test Sync',
            'email' => 'suppliersync@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_name' => 'Supplier Sync Test',
            'owner_name' => 'Owner Sync',
            'phone' => '081234567890',
            'warehouse_address' => 'Gudang Sync',
        ]);

        $response = $this->actingAs($user)->getJson(route('supplier.check-updates'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updated',
            'fingerprint',
        ]);
        $response->assertJson([
            'updated' => true,
        ]);
        $this->assertNotEmpty($response->json('fingerprint'));
    }

    public function test_admin_check_updates_returns_valid_fingerprint(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super Admin Sync',
            'email' => 'adminsync@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.check-updates'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updated',
            'fingerprint',
        ]);
        $response->assertJson([
            'updated' => true,
        ]);
        $this->assertNotEmpty($response->json('fingerprint'));
    }

    public function test_umkm_check_updates_returns_valid_fingerprint(): void
    {
        $umkmUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'UMKM Sync Test',
            'email' => 'umkmsync@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $umkmUser->id,
            'business_name' => 'Dapur UMKM Sync',
            'owner_name' => 'Pemilik Dapur',
            'phone' => '081234567891',
            'address' => 'Jl. Dapur No. 1',
        ]);

        $response = $this->actingAs($umkmUser)->getJson(route('umkm.check-updates'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updated',
            'fingerprint',
        ]);
        $response->assertJson([
            'updated' => true,
        ]);
        $this->assertNotEmpty($response->json('fingerprint'));
    }
}
