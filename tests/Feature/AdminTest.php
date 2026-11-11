<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $umkm = User::factory()->create(['role' => 'umkm']);

        $response = $this->actingAs($umkm)->get('/admin');
        $response->assertRedirect('/umkm/katalog');
    }

    public function test_admin_can_access_admin_dashboard_and_see_all_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk();
        $response->assertSee('Dashboard Admin');
    }

    public function test_admin_can_update_user_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch("/admin/users/{$user->id}/status", [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_admin_verification_allows_previously_pending_user_to_login(): void
    {
        $user = User::factory()->create(['status' => 'active', 'password' => bcrypt('password123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
