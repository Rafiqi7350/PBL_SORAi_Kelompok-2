<?php

use App\Models\User;

test('test_unauthenticated_user_cannot_access_admin_dashboard', function () {
    $response = $this->get('/admin');
    $response->assertRedirect('/admin/login');
});

test('test_non_admin_user_cannot_access_admin_dashboard', function () {
    $umkm = User::factory()->create(['role' => 'umkm']);
    
    $response = $this->actingAs($umkm)->get('/admin');
    $response->assertRedirect('/umkm/katalog');
});

test('test_admin_can_access_admin_dashboard_and_see_all_sections', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('Dashboard Admin');
});

test('test_admin_can_update_user_status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['status' => 'pending']);
    
    $response = $this->actingAs($admin)->patch("/admin/users/{$user->id}/status", [
        'status' => 'active'
    ]);
    
    $response->assertOk();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
});

test('test_admin_verification_allows_previously_pending_user_to_login', function () {
    $user = User::factory()->create(['status' => 'active', 'password' => bcrypt('password123')]);
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);
    
    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});