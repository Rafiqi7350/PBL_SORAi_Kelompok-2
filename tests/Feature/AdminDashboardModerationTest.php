<?php

namespace Tests\Feature;

use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminDashboardModerationTest extends TestCase
{
    use RefreshDatabase;

    // Guest tidak boleh masuk ke panel dashboard admin
    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
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

    /**
     * @return array<string, array{string, string}>
     */
    public static function userStatusFilterProvider(): array
    {
        return [
            'aktif status' => ['AKTIF', 'aktif@example.com'],
            'pending verifikasi status' => ['PENDING_VERIFIKASI', 'pending@example.com'],
            'nonaktif status' => ['NONAKTIF', 'nonaktif@example.com'],
        ];
    }

    #[DataProvider('userStatusFilterProvider')]
    public function test_admin_can_filter_users_by_account_status(string $statusFilter, string $expectedEmail): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'aktif@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'pending',
        ]);

        User::create([
            'email' => 'nonaktif@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'tab' => 'users',
            'user_status' => $statusFilter,
        ]));

        $response->assertViewHas('userFilterStatus', $statusFilter);
        $response->assertSee('id="user-filter-status"', false);

        $users = $response->viewData('users');
        $returnedEmails = collect($users->items())->pluck('email')->all();

        $this->assertSame([$expectedEmail], $returnedEmails);
    }

    public function test_admin_user_status_filter_combines_with_role_and_handles_empty_results(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'umkm.aktif@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'umkm.pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        User::create([
            'email' => 'supplier.pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'pending',
        ]);

        $combinedResponse = $this->actingAs($admin)->get(route('admin.dashboard', [
            'tab' => 'users',
            'user_role' => 'UMKM',
            'user_status' => 'PENDING_VERIFIKASI',
        ]));

        $combinedEmails = collect($combinedResponse->viewData('users')->items())->pluck('email')->all();
        $this->assertSame(['umkm.pending@example.com'], $combinedEmails);

        $emptyResponse = $this->actingAs($admin)->get(route('admin.dashboard', [
            'tab' => 'users',
            'user_role' => 'PEMASOK',
            'user_status' => 'NONAKTIF',
        ]));

        $emptyResponse->assertSee('Tidak ada akun pengguna yang sesuai dengan filter yang dipilih.');
        $this->assertSame([], collect($emptyResponse->viewData('users')->items())->all());
    }

    public function test_admin_user_status_filter_combines_with_search_and_owner_name(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $matchingUmkm = User::create([
            'email' => 'sambal@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $matchingUmkm->id,
            'business_name' => 'Dapur Sambal Nusantara',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Malang',
        ]);

        $nonMatchingStatusUmkm = User::create([
            'email' => 'sambal.pending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        UmkmProfile::create([
            'user_id' => $nonMatchingStatusUmkm->id,
            'business_name' => 'Warung Sambal Pending',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567891',
            'address' => 'Malang',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'tab' => 'users',
            'user_search' => 'Budi Santoso',
            'user_status' => 'AKTIF',
        ]));

        $response->assertOk();
        $response->assertViewHas('userSearch', 'Budi Santoso');
        $response->assertViewHas('userFilterStatus', 'AKTIF');

        $items = $response->viewData('users')->items();
        $this->assertCount(1, $items);
        $this->assertSame('sambal@example.com', $items[0]['email']);
        $this->assertStringContainsString('budi santoso', $items[0]['search_text']);
    }
}
