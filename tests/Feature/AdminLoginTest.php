<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('SELAMAT DATANG');
        $response->assertSee('SORAI Admin Panel');
        $response->assertSee('Masuk Ke Dashboard');
        $response->assertSee('admin@sorai.id');
    }

    public function test_admin_user_can_login_and_is_redirected_to_admin_commodities(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@sorai.id',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.commodities.index'));
        $response->assertSessionHas('success');
    }

    public function test_non_admin_user_cannot_login_via_admin_login(): void
    {
        User::create([
            'email' => 'umkm@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'umkm@dapur.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_user_cannot_login_with_invalid_password(): void
    {
        User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@sorai.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_user_cannot_login_with_unregistered_email(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'unknown@sorai.id',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_user_can_logout(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/logout');

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard_and_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin/commodities');

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('warning');
    }

    public function test_authenticated_non_admin_cannot_access_admin_dashboard(): void
    {
        $umkm = User::create([
            'email' => 'umkm@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($umkm)->get('/admin/commodities');

        $response->assertRedirect(route('umkm.katalog.index'));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_admin_visiting_login_page_is_redirected_to_dashboard(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/login');

        $response->assertRedirect(route('admin.commodities.index'));
    }
}
