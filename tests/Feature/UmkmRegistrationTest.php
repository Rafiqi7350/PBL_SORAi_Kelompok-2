<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmkmRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_umkm_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register/umkm');

        $response->assertStatus(200);
        $response->assertSee('DAFTAR SEBAGAI');
        $response->assertSee('UMKM KULINER');
        $response->assertSee('password_confirmation');
    }

    public function test_umkm_validation_fails_with_empty_data(): void
    {
        $response = $this->post('/register/umkm', []);

        $response->assertSessionHasErrors([
            'email',
            'password',
            'password_confirmation',
            'business_name',
            'owner_name',
            'phone',
            'address',
        ]);
    }

    public function test_umkm_validation_fails_with_invalid_or_dummy_email(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'teks-bebas-bukan-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Dapur Uji',
            'owner_name' => 'Pemilik Uji',
            'phone' => '081234567899',
            'address' => 'Jl. Pengujian No. 123, Malang',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_umkm_validation_fails_when_password_confirmation_mismatches(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'valid_user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'berbeda456',
            'business_name' => 'Dapur Uji',
            'owner_name' => 'Pemilik Uji',
            'phone' => '081234567899',
            'address' => 'Jl. Pengujian No. 123, Malang',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_new_umkm_can_register(): void
    {
        $testEmail = 'test_umkm_'.uniqid().'@example.com';

        $response = $this->post('/register/umkm', [
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Dapur Uji Coba',
            'owner_name' => 'Pemilik Uji Coba',
            'phone' => '081234567899',
            'address' => 'Jl. Pengujian No. 123, Malang',
        ]);

        $response->assertRedirect(route('register.pending', ['email' => $testEmail]));
        $response->assertSessionHas('success');

        $pendingResponse = $this->get(route('register.pending', ['email' => $testEmail]));
        $pendingResponse->assertOk();
        $pendingResponse->assertSee('STATUS: MENUNGGU ACC ADMIN');

        $this->assertDatabaseHas('users', [
            'email' => $testEmail,
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $user = User::where('email', $testEmail)->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('umkm_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Dapur Uji Coba',
            'owner_name' => 'Pemilik Uji Coba',
            'phone' => '081234567899',
            'address' => 'Jl. Pengujian No. 123, Malang',
        ]);

        // Pending user cannot log in before admin verification
        $loginResponse = $this->post('/login', [
            'email' => $testEmail,
            'password' => 'password123',
        ]);
        $this->assertGuest();
        $loginResponse->assertSessionHasErrors(['email']);
    }
}
