<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardModerationTest extends TestCase
{
    use RefreshDatabase;

    // Guest tidak boleh masuk ke panel dashboard admin
    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }

    // Role UMKM dilarang membuka halaman admin
    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $umkm = User::create([
            'email' => 'umkm@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->actingAs($umkm);

        $response = $this->get('/admin');

        $response->assertRedirect(route('umkm.katalog.index'));
    }

    // Admin login bisa melihat ringkasan metrik dan master data
    public function test_admin_can_access_admin_dashboard_and_see_all_sections(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHasAll([
            'users',
            'totalUmkm',
            'activeUmkm',
            'totalSuppliers',
            'activeSuppliers',
            'pendingUsersCount',
            'categories',
            'units',
            'totalCommodities',
        ]);
    }

    // Admin mengubah status user pending menjadi aktif
    public function test_admin_can_update_user_status(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user = User::create([
            'email' => 'pendinguser@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->patch("/admin/users/{$user->id}/status", [
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.dashboard', ['tab' => 'users']));

        $user->refresh();
        $this->assertEquals('active', $user->status);
    }

    // User yang baru di-acc admin bisa langsung login
    public function test_admin_verification_allows_previously_pending_user_to_login(): void
    {
        $user = User::create([
            'email' => 'verifyme@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        // Login ditolak saat status masih pending
        $attempt1 = $this->post('/login', [
            'email' => 'verifyme@example.com',
            'password' => 'password123',
        ]);
        $attempt1->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // Admin approve user
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->actingAs($admin);
        $this->patch("/admin/users/{$user->id}/status", ['status' => 'active']);
        $this->post('/admin/logout');

        // Login ulang setelah diapprove
        $attempt2 = $this->post('/login', [
            'email' => 'verifyme@example.com',
            'password' => 'password123',
        ]);
        $attempt2->assertRedirect(route('umkm.katalog.index'));
        $this->assertAuthenticatedAs($user);
    }
}
