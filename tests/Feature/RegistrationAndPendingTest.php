<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAndPendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_umkm_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register/umkm');
        $response->assertOk();
    }

    public function test_umkm_validation_fails_with_empty_data()
    {
        $response = $this->post('/register/umkm', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    public function test_umkm_validation_fails_with_invalid_or_dummy_email()
    {
        $response = $this->post('/register/umkm', ['email' => 'invalid-email']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    public function test_umkm_validation_fails_when_password_confirmation_mismatches()
    {
        $response = $this->post('/register/umkm', [
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
    }

    public function test_umkm_validation_fails_when_owner_name_contains_numbers()
    {
        $response = $this->post('/register/umkm', ['owner_name' => 'Aurellia123']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('owner_name');
    }

    public function test_umkm_validation_fails_when_phone_contains_letters()
    {
        $response = $this->post('/register/umkm', ['phone' => 'abcde']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('phone');
    }

    public function test_new_umkm_can_register()
    {
        $response = $this->post('/register/umkm', [
            'business_name' => 'Warung Kuliner Sejahtera',
            'owner_name' => 'Aurellia Mezaluna',
            'email' => 'aurellia@example.com',
            'phone' => '081234567890',
            'address' => 'Malang',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'umkm'
        ]);

        $response->assertRedirect('/register/pending');
        $this->assertDatabaseHas('users', ['email' => 'aurellia@example.com', 'status' => 'pending']);
    }

    public function test_supplier_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register/supplier');
        $response->assertOk();
    }

    public function test_supplier_validation_fails_with_invalid_email_or_password_mismatch()
    {
        $response = $this->post('/register/supplier', [
            'email' => 'bademail',
            'password' => '123',
            'password_confirmation' => '456'
        ]);
        $response->assertStatus(302);
    }

    public function test_supplier_validation_fails_with_invalid_phone_or_nib()
    {
        $response = $this->post('/register/supplier', [
            'nib' => 'invalid',
            'phone' => 'salah'
        ]);
        $response->assertStatus(302);
    }

    public function test_new_supplier_can_register()
    {
        $response = $this->post('/register/supplier', [
            'supplier_name' => 'Pemasok Sayur Malang',
            'email' => 'pemasok@example.com',
            'phone' => '089876543210',
            'nib' => '1234567890123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'pemasok'
        ]);

        $response->assertRedirect('/register/pending');
        $this->assertDatabaseHas('users', ['email' => 'pemasok@example.com', 'status' => 'pending']);
    }

    public function test_user_can_view_pending_page()
    {
        $user = User::factory()->create(['status' => 'pending']);
        $response = $this->actingAs($user)->get('/register/pending');
        $response->assertOk();
    }

    public function test_api_check_status_returns_pending_state()
    {
        $user = User::factory()->create(['status' => 'pending']);
        $response = $this->actingAs($user)->getJson('/api/check-status');
        $response->assertOk();
        $response->assertJson(['status' => 'pending']);
    }

    public function test_api_check_status_returns_active_when_admin_approve_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $response = $this->actingAs($user)->getJson('/api/check-status');
        $response->assertOk();
        $response->assertJson(['status' => 'active']);
    }
}