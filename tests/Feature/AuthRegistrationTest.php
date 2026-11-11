<?php

namespace Tests\Feature;

use App\Models\CommodityCategory;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // Tampilkan form registrasi UMKM
    public function test_umkm_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register/umkm');

        $response->assertStatus(200);
    }

    // Validasi input kosong saat daftar UMKM
    public function test_umkm_validation_fails_with_empty_data(): void
    {
        $response = $this->post('/register/umkm', []);

        $response->assertSessionHasErrors([
            'email',
            'password',
            'business_name',
            'owner_name',
            'phone',
            'address',
        ]);
    }

    // Validasi format email salah
    public function test_umkm_validation_fails_with_invalid_or_dummy_email(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'email-tidak-valid',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Warung Enak',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 10',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // Validasi konfirmasi password tidak sama
    public function test_umkm_validation_fails_when_password_confirmation_mismatches(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'umkm@example.com',
            'password' => 'password123',
            'password_confirmation' => 'passwordBeda',
            'business_name' => 'Warung Enak',
            'owner_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 10',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // Validasi nama pemilik tidak boleh angka atau simbol
    public function test_umkm_validation_fails_when_owner_name_contains_numbers_or_invalid_characters(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'umkm@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Warung Enak',
            'owner_name' => 'Budi 123 !@#',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 10',
        ]);

        $response->assertSessionHasErrors(['owner_name']);
    }

    // Validasi nomor wa harus angka dan panjang sesuai
    public function test_umkm_validation_fails_when_phone_contains_letters_or_invalid_length(): void
    {
        $responseLetters = $this->post('/register/umkm', [
            'email' => 'umkm1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Warung Enak',
            'owner_name' => 'Budi Santoso',
            'phone' => '0812abcd3456',
            'address' => 'Jl. Mawar No. 10',
        ]);
        $responseLetters->assertSessionHasErrors(['phone']);

        $responseShort = $this->post('/register/umkm', [
            'email' => 'umkm2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Warung Enak',
            'owner_name' => 'Budi Santoso',
            'phone' => '0812',
            'address' => 'Jl. Mawar No. 10',
        ]);
        $responseShort->assertSessionHasErrors(['phone']);
    }

    // Registrasi UMKM baru berhasil dan masuk antrean pending
    public function test_new_umkm_can_register(): void
    {
        $response = $this->post('/register/umkm', [
            'email' => 'umkmbaru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Dapur Kuliner Mantap',
            'owner_name' => 'Ahmad Subarjo',
            'phone' => '081234567890',
            'address' => 'Jl. Soekarno Hatta No. 45, Malang',
        ]);

        $response->assertRedirect(route('register.pending', ['email' => 'umkmbaru@example.com']));

        $this->assertDatabaseHas('users', [
            'email' => 'umkmbaru@example.com',
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('umkm_profiles', [
            'business_name' => 'Dapur Kuliner Mantap',
            'owner_name' => 'Ahmad Subarjo',
            'phone' => '081234567890',
        ]);
    }

    // Tampilkan form pendaftaran supplier
    public function test_supplier_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register/supplier');

        $response->assertStatus(200);
    }

    // Validasi email dan konfirmasi password supplier
    public function test_supplier_validation_fails_with_invalid_email_or_password_mismatch(): void
    {
        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $response = $this->post('/register/supplier', [
            'email' => 'bukan-email-resmi',
            'password' => 'password123',
            'password_confirmation' => 'passwordBeda',
            'company_name' => 'CV Sumber Makmur',
            'phone' => '081234567890',
            'commodity_id' => $category->id,
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Jawa Timur',
            'warehouse_address' => 'Jl. Pergudangan No. 8',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    // Validasi format nomor telepon dan NIB
    public function test_supplier_validation_fails_with_invalid_phone_or_nib(): void
    {
        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $response = $this->post('/register/supplier', [
            'email' => 'supplier@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'CV Sumber Makmur',
            'phone' => '0812hurufwa',
            'commodity_id' => $category->id,
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Jawa Timur',
            'warehouse_address' => 'Jl. Pergudangan No. 8',
            'nib_number' => 'NIB-HURUF-BUKAN-ANGKA',
        ]);

        $response->assertSessionHasErrors(['phone', 'nib_number']);
    }

    // Pendaftaran akun supplier baru berhasil
    public function test_new_supplier_can_register(): void
    {
        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $response = $this->post('/register/supplier', [
            'email' => 'pemasokbaru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'PT Berkah Unggas Nusantara',
            'owner_name' => 'Haji Mansyur',
            'phone' => '085712345678',
            'commodity_id' => $category->id,
            'max_capacity' => '10 Ton / Minggu',
            'delivery_area' => 'Malang Raya & Surabaya',
            'warehouse_address' => 'Jl. Industri Peternakan No. 12',
            'nib_number' => '9120001234567',
        ]);

        $response->assertRedirect(route('register.pending', ['email' => 'pemasokbaru@example.com']));

        $this->assertDatabaseHas('users', [
            'email' => 'pemasokbaru@example.com',
            'role' => 'supplier',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('supplier_profiles', [
            'company_name' => 'PT Berkah Unggas Nusantara',
            'phone' => '085712345678',
            'nib_number' => '9120001234567',
        ]);
    }

    // Akses halaman pending registrasi
    public function test_user_can_view_pending_page(): void
    {
        $user = User::create([
            'email' => 'pendinguser@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $response = $this->get('/register/pending?email='.$user->email);

        $response->assertStatus(200);
        $response->assertViewIs('Auth.pending');
    }

    // Cek status pending via endpoint polling API
    public function test_api_check_status_returns_pending_state(): void
    {
        $user = User::create([
            'email' => 'checkpending@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/check-status?email='.$user->email);

        $response->assertStatus(200);
        $response->assertJson([
            'exists' => true,
            'status' => 'pending',
            'is_active' => false,
        ]);
    }

    // Cek status saat akun sudah diaktifkan admin
    public function test_api_check_status_returns_active_when_admin_acc_user(): void
    {
        $user = User::create([
            'email' => 'approveduser@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/check-status?email='.$user->email);

        $response->assertStatus(200);
        $response->assertJson([
            'exists' => true,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    // Cek email yang belum terdaftar di sistem
    public function test_pending_page_handles_non_existent_email_gracefully(): void
    {
        $response = $this->get('/register/pending?email=nonexistent@example.com');

        $response->assertStatus(200);

        $apiResponse = $this->getJson('/api/check-status?email=nonexistent@example.com');
        $apiResponse->assertStatus(404);
        $apiResponse->assertJson([
            'exists' => false,
            'is_active' => false,
        ]);
    }

    // Tampilkan halaman login tanpa opsi login administrator
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('Auth.login');
        $response->assertDontSee('Masuk sebagai Administrator Panel');
        $response->assertDontSee('/admin/login');
    }

    // Login UMKM berhasil redirect ke katalog
    public function test_umkm_user_can_login_and_is_redirected_to_umkm_catalog(): void
    {
        $user = User::create([
            'email' => 'activeumkm@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Warung Ayam Geprek',
            'owner_name' => 'Doni',
            'phone' => '081234567890',
            'address' => 'Malang',
        ]);

        $response = $this->post('/login', [
            'email' => 'activeumkm@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('umkm.katalog.index'));
        $this->assertAuthenticatedAs($user);
    }

    // Login pemasok berhasil redirect ke dashboard pemasok
    public function test_supplier_user_can_login_and_is_redirected_to_supplier_dashboard(): void
    {
        $user = User::create([
            'email' => 'activesupplier@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Kelompok Tani Poultry',
            'owner_name' => 'Sutrisno',
            'phone' => '085712345678',
            'warehouse_address' => 'Jl. Peternakan 5',
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Malang Raya',
        ]);

        $response = $this->post('/login', [
            'email' => 'activesupplier@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('supplier.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    // Login gagal saat password salah
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('passwordBenar'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser@example.com',
            'password' => 'passwordSalah',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    // Login gagal saat email belum ada di database
    public function test_user_cannot_login_with_unregistered_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'belumterdaftar@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    // Proses logout user
    public function test_user_can_logout(): void
    {
        $user = User::create([
            'email' => 'logoutuser@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // Ganti akun langsung via halaman login
    public function test_active_supplier_can_switch_to_umkm_via_login_screen(): void
    {
        $supplier = User::create([
            'email' => 'switchsupplier@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $umkm = User::create([
            'email' => 'switchumkm@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->actingAs($supplier);
        $this->assertAuthenticatedAs($supplier);

        $response = $this->post('/login', [
            'email' => 'switchumkm@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('umkm.katalog.index'));
        $this->assertAuthenticatedAs($umkm);
    }

    // Login dengan opsi remember me
    public function test_user_can_login_with_remember_me(): void
    {
        $user = User::create([
            'email' => 'rememberuser@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'rememberuser@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('umkm.katalog.index'));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    // Login remember me dengan nilai checkbox on
    public function test_user_can_login_with_remember_me_checkbox_value_on(): void
    {
        $user = User::create([
            'email' => 'rememberon@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'rememberon@example.com',
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('umkm.katalog.index'));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    // Halaman login admin terpisah sudah dihapus
    public function test_admin_login_route_is_removed_and_returns_not_found(): void
    {
        $this->get('/admin/login')->assertNotFound();
        $this->post('/admin/login', [
            'email' => 'admin@sorai.id',
            'password' => 'admin12345',
        ])->assertNotFound();
    }

    // Login admin melalui halaman /login berhasil redirect ke dashboard admin
    public function test_admin_user_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::create([
            'email' => 'admin@sorai.id',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@sorai.id',
            'password' => 'admin12345',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    // Login admin ditolak jika status akun tidak aktif
    public function test_inactive_admin_user_cannot_login(): void
    {
        User::create([
            'email' => 'suspendedadmin@sorai.id',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => 'suspendedadmin@sorai.id',
            'password' => 'admin12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    // Logout session admin
    public function test_admin_user_can_logout(): void
    {
        $admin = User::create([
            'email' => 'adminlogout@sorai.id',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // Guest dilarang masuk ke dashboard admin dan diarahkan ke /login
    public function test_unauthenticated_user_cannot_access_admin_dashboard_and_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    // User UMKM dialihkan saat mencoba buka dashboard admin
    public function test_authenticated_non_admin_cannot_access_admin_dashboard(): void
    {
        $umkm = User::create([
            'email' => 'umkmcobaadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->actingAs($umkm);

        $response = $this->get('/admin');

        $response->assertRedirect(route('umkm.katalog.index'));
    }

    // Admin yang sudah login melihat pintasan Panel Admin saat membuka /login
    public function test_authenticated_admin_visiting_login_page_sees_admin_panel_shortcut(): void
    {
        $admin = User::create([
            'email' => 'adminloginpage@sorai.id',
            'password' => Hash::make('admin12345'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/login');

        $response->assertSee('Mode Admin');
        $response->assertSee(route('admin.dashboard'), false);
    }
}
