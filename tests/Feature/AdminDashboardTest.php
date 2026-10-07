<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $umkm = User::create([
            'email' => 'umkm@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($umkm)->get('/admin');

        $response->assertRedirect(route('umkm.katalog.index'));
    }

    public function test_admin_can_access_admin_dashboard_and_see_all_sections(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('SORAI');
        $response->assertSee('Admin Control Hub');
        $response->assertSee('Dashboard Control Systems');
        $response->assertSee('Manajemen Pengguna');
        $response->assertSee('Master Data Komoditas');
        $response->assertSee('Monitoring Sistem');
        $response->assertDontSee('Basis Data Komoditas');
    }

    public function test_admin_can_update_user_status(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $targetUser = User::create([
            'email' => 'mitra@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/users/{$targetUser->id}/status", [
            'status' => 'AKTIF',
        ]);

        $response->assertRedirect(route('admin.dashboard', ['tab' => 'users']));
        $response->assertSessionHas('success');

        $this->assertEquals('active', $targetUser->fresh()->status);
    }

    public function test_admin_verification_allows_previously_pending_user_to_login(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $pendingUser = User::create([
            'email' => 'menunggu_verifikasi@dapur.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        // Attempt login while pending -> fails
        $loginFailResponse = $this->post('/login', [
            'email' => 'menunggu_verifikasi@dapur.com',
            'password' => 'password123',
        ]);
        $this->assertGuest();
        $loginFailResponse->assertSessionHasErrors(['email']);

        // Admin verifies the user
        $this->actingAs($admin)->patch("/admin/users/{$pendingUser->id}/status", [
            'status' => 'AKTIF',
        ]);
        $this->assertEquals('active', $pendingUser->fresh()->status);

        // User can now log in
        $loginSuccessResponse = $this->post('/login', [
            'email' => 'menunggu_verifikasi@dapur.com',
            'password' => 'password123',
        ]);
        $this->assertAuthenticatedAs($pendingUser);
        $loginSuccessResponse->assertRedirect(route('umkm.katalog.index'));
    }
}
