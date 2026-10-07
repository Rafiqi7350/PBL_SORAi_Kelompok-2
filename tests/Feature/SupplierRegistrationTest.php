<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register/supplier');

        $response->assertStatus(200);
        $response->assertSee('DAFTAR SEBAGAI');
        $response->assertSee('PEMASOK BAHAN BAKU');
        $response->assertSee('password_confirmation');
    }

    public function test_supplier_validation_fails_with_invalid_email_or_password_mismatch(): void
    {
        $response = $this->post('/register/supplier', [
            'email' => 'teks-bebas-bukan-email',
            'password' => 'password123',
            'password_confirmation' => 'mismatch999',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_new_supplier_can_register(): void
    {
        $commodity = Commodity::create([
            'name' => 'Cabai Rawit Merah',
            'category' => 'Bumbu & Rempah',
            'unit' => 'kg',
        ]);

        $testEmail = 'supplier_' . uniqid() . '@example.com';

        $response = $this->post('/register/supplier', [
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Kelompok Tani Makmur',
            'phone' => '081234567890',
            'commodity_id' => $commodity->id,
            'max_capacity' => '500 kg / Minggu',
            'delivery_area' => 'Malang Raya',
            'nib_number' => 'NIB-1234567890',
            'warehouse_address' => 'Jl. Kebun Raya No. 1, Batu',
        ]);

        $response->assertRedirect('/register/supplier');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => $testEmail,
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('supplier_profiles', [
            'company_name' => 'Kelompok Tani Makmur',
            'phone' => '081234567890',
            'commodity_id' => $commodity->id,
        ]);
    }
}
