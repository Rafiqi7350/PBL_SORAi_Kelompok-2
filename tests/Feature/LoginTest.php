<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('MASUK KE');
        $response->assertSee('PORTAL SORAi');
    }

    public function test_umkm_user_can_login_and_is_redirected_to_umkm_catalog(): void
    {
        $user = User::create([
            'email' => 'umkm@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Dapur Sedap Rasa',
            'owner_name' => 'Pak Budi',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10, Malang',
        ]);

        $response = $this->post('/login', [
            'email' => 'umkm@dapur.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('umkm.katalog.index'));
        $response->assertSessionHas('success');
    }

    public function test_supplier_user_can_login_and_is_redirected_to_supplier_catalog(): void
    {
        $commodity = Commodity::create([
            'name' => 'Bawang Merah',
            'category' => 'Bumbu & Rempah',
            'unit' => 'kg',
        ]);

        $user = User::create([
            'email' => 'supplier@tani.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'commodity_id' => $commodity->id,
            'company_name' => 'Kelompok Tani Subur',
            'owner_name' => 'Pak Tani',
            'phone' => '081234567899',
            'warehouse_address' => 'Jl. Kebun No. 5, Batu',
            'max_capacity' => '1000 kg',
            'delivery_area' => 'Malang',
            'nib_number' => '9120003810',
        ]);

        $response = $this->post('/login', [
            'email' => 'supplier@tani.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('supplier.katalog.index'));
        $response->assertSessionHas('success');
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::create([
            'email' => 'user@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_login_with_unregistered_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'notfound@test.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'email' => 'user_logout@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_active_supplier_can_switch_to_umkm_via_login_screen(): void
    {
        $supplier = User::create([
            'email' => 'supplier_old@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $umkm = User::create([
            'email' => 'umkm_new@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $umkm->id,
            'business_name' => 'Dapur Baru',
            'owner_name' => 'Pemilik Baru',
            'phone' => '081234567890',
            'address' => 'Jl. Baru No. 1, Malang',
        ]);

        // Access login page while logged in as supplier
        $pageResponse = $this->actingAs($supplier)->get('/login');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Mode Pemasok Bahan Baku');
        $pageResponse->assertSee('supplier_old@test.com');

        // Submit login credentials for the UMKM account
        $loginResponse = $this->post('/login', [
            'email' => 'umkm_new@test.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($umkm);
        $loginResponse->assertRedirect(route('umkm.katalog.index'));
    }
}
