<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PendingRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_pending_page(): void
    {
        $user = User::create([
            'email' => 'menunggu_acc@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $response = $this->get(route('register.pending', ['email' => $user->email]));

        $response->assertOk();
        $response->assertSee('STATUS: MENUNGGU PERSETUJUAN ADMIN');
        $response->assertSee('Mohon ditunggu, data Anda sedang diproses');
        $response->assertSee('Kembali');
        $response->assertSee('menunggu_acc@test.com');
    }

    public function test_api_check_status_returns_pending_state(): void
    {
        $user = User::create([
            'email' => 'cek_pending@test.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $response = $this->get(route('api.check-status', ['email' => $user->email]));

        $response->assertOk();
        $response->assertJson([
            'exists' => true,
            'status' => 'pending',
            'is_active' => false,
            'role' => 'umkm',
        ]);
    }

    public function test_api_check_status_returns_active_when_admin_acc_user(): void
    {
        $user = User::create([
            'email' => 'sudah_acc@test.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $response = $this->get(route('api.check-status', ['email' => $user->email]));

        $response->assertOk();
        $response->assertJson([
            'exists' => true,
            'status' => 'active',
            'is_active' => true,
            'role' => 'supplier',
        ]);
    }

    public function test_pending_page_handles_non_existent_email_gracefully(): void
    {
        $response = $this->get(route('api.check-status', ['email' => 'tidak_ada@test.com']));

        $response->assertNotFound();
        $response->assertJson([
            'exists' => false,
            'status' => 'not_found',
            'is_active' => false,
        ]);
    }
}
