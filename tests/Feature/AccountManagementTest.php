<?php

namespace Tests\Feature;

use App\Models\CommodityCategory;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function guestProtectedEndpointsProvider(): array
    {
        return [
            'umkm account view' => ['GET', '/umkm/akun'],
            'umkm account update' => ['PUT', '/umkm/akun'],
            'umkm password update' => ['PUT', '/umkm/akun/password'],
            'umkm photo update' => ['PUT', '/umkm/akun/foto'],
            'umkm photo delete' => ['DELETE', '/umkm/akun/foto'],
            'supplier account view' => ['GET', '/supplier/akun'],
            'supplier account update' => ['PUT', '/supplier/akun'],
            'supplier password update' => ['PUT', '/supplier/akun/password'],
            'supplier photo update' => ['PUT', '/supplier/akun/foto'],
            'supplier photo delete' => ['DELETE', '/supplier/akun/foto'],
        ];
    }

    #[DataProvider('guestProtectedEndpointsProvider')]
    public function test_guest_cannot_access_account_management_endpoints(string $method, string $uri): void
    {
        $response = $this->call($method, $uri);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_profile_photo_media_endpoint(): void
    {
        $user = User::factory()->create();

        $this->get(route('account.photo.show', ['user' => $user->id]))
            ->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedUmkmRolesProvider(): array
    {
        return [
            'supplier role' => ['supplier'],
            'admin role' => ['admin'],
        ];
    }

    #[DataProvider('unauthorizedUmkmRolesProvider')]
    public function test_non_umkm_role_cannot_access_umkm_account_endpoints(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/umkm/akun')->assertForbidden();
        $this->actingAs($user)->put('/umkm/akun', [])->assertForbidden();
        $this->actingAs($user)->put('/umkm/akun/password', [])->assertForbidden();
        $this->actingAs($user)->put('/umkm/akun/foto', [])->assertForbidden();
        $this->actingAs($user)->delete('/umkm/akun/foto')->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unauthorizedSupplierRolesProvider(): array
    {
        return [
            'umkm role' => ['umkm'],
            'admin role' => ['admin'],
        ];
    }

    #[DataProvider('unauthorizedSupplierRolesProvider')]
    public function test_non_supplier_role_cannot_access_supplier_account_endpoints(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/supplier/akun')->assertForbidden();
        $this->actingAs($user)->put('/supplier/akun', [])->assertForbidden();
        $this->actingAs($user)->put('/supplier/akun/password', [])->assertForbidden();
        $this->actingAs($user)->put('/supplier/akun/foto', [])->assertForbidden();
        $this->actingAs($user)->delete('/supplier/akun/foto')->assertForbidden();
    }

    public function test_umkm_account_page_renders_profile_data_and_escapes_html(): void
    {
        $user = User::factory()->create([
            'email' => 'dapur.amanah@example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => "Warung <script>alert('xss')</script>",
            'owner_name' => 'Siti Aminah',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10, Malang',
        ]);

        $response = $this->actingAs($user)->get(route('umkm.account.edit'));

        $response->assertViewIs('umkm.account.edit');
        $response->assertSee('dapur.amanah@example.com');
        $response->assertSee('Siti Aminah');
        $response->assertSee('081234567890');
        $response->assertSee('Jl. Merdeka No. 10, Malang');
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_supplier_account_page_renders_profile_data_and_escapes_html(): void
    {
        $category = CommodityCategory::create(['name' => 'Daging & Unggas']);
        $user = User::factory()->create([
            'email' => 'supplier.utama@example.com',
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'commodity_id' => $category->id,
            'company_name' => "PT Sumber <script>alert('xss')</script>",
            'owner_name' => 'Haji Mansyur',
            'phone' => '085712345678',
            'warehouse_address' => 'Jl. Raya Kepanjen No. 12, Malang',
            'max_capacity' => '2.500 kg / Minggu',
            'delivery_area' => 'Malang Raya',
            'nib_number' => '9120001234567',
        ]);

        $response = $this->actingAs($user)->get(route('supplier.account.edit'));

        $response->assertViewIs('supplier.account.edit');
        $response->assertSee('supplier.utama@example.com');
        $response->assertSee('Haji Mansyur');
        $response->assertSee('085712345678');
        $response->assertSee('2.500 kg / Minggu');
        $response->assertSee('Malang Raya');
        $response->assertSee('9120001234567');
        $response->assertSee('Daging &amp; Unggas', false);
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_umkm_can_update_account_and_profile_data_while_ignoring_privileged_fields(): void
    {
        $user = User::factory()->create([
            'email' => 'lama@umkm.example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Dapur Lama',
            'owner_name' => 'Pemilik Lama',
            'phone' => '081111111111',
            'address' => 'Alamat Lama No. 1',
        ]);

        $response = $this->actingAs($user)->put(route('umkm.account.update'), [
            'email' => 'baru@umkm.example.com',
            'business_name' => 'Dapur Sambal Nusantara',
            'owner_name' => 'Dewi Sartika',
            'phone' => '082233445566',
            'address' => 'Jl. Ijen Boulevard No. 88, Kota Malang',
            'role' => 'admin',
            'status' => 'suspended',
        ]);

        $response->assertRedirect(route('umkm.account.edit'));
        $response->assertSessionHas('success', 'Data akun & profil UMKM berhasil diperbarui.');

        $user->refresh();
        $profile = $user->umkmProfile()->firstOrFail();

        $this->assertSame('baru@umkm.example.com', $user->email);
        $this->assertSame('umkm', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('Dapur Sambal Nusantara', $profile->business_name);
        $this->assertSame('Dewi Sartika', $profile->owner_name);
        $this->assertSame('082233445566', $profile->phone);
        $this->assertSame('Jl. Ijen Boulevard No. 88, Kota Malang', $profile->address);
    }

    public function test_umkm_can_keep_same_email_and_create_missing_profile_on_update(): void
    {
        $user = User::factory()->create([
            'email' => 'tetap@umkm.example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('umkm.account.update'), [
            'email' => 'tetap@umkm.example.com',
            'business_name' => 'Warung Baru Dibuat',
            'owner_name' => 'Budi Santoso',
            'phone' => '081298765432',
            'address' => 'Jl. Veteran No. 5, Malang',
        ]);

        $response->assertRedirect(route('umkm.account.edit'));
        $response->assertSessionHasNoErrors();

        $profile = $user->umkmProfile()->first();
        $this->assertNotNull($profile);
        $this->assertSame('Warung Baru Dibuat', $profile->business_name);
    }

    public function test_umkm_account_update_fails_when_required_fields_are_empty(): void
    {
        $user = User::factory()->create([
            'email' => 'asli@umkm.example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('umkm.account.update'), []);

        $response->assertSessionHasErrors([
            'email',
            'business_name',
            'owner_name',
            'phone',
            'address',
        ]);
        $user->refresh();
        $this->assertSame('asli@umkm.example.com', $user->email);
    }

    public function test_umkm_account_update_rejects_duplicate_email_from_another_user(): void
    {
        User::factory()->create(['email' => 'sudah.dipakai@example.com']);
        $user = User::factory()->create([
            'email' => 'saya@umkm.example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('umkm.account.update'), [
            'email' => 'sudah.dipakai@example.com',
            'business_name' => 'Dapur Sederhana',
            'owner_name' => 'Rina Putri',
            'phone' => '081234567890',
            'address' => 'Jl. Bunga Coklat No. 3',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email ini sudah digunakan oleh akun lain.',
        ]);
        $user->refresh();
        $this->assertSame('saya@umkm.example.com', $user->email);
    }

    public function test_umkm_account_update_rejects_invalid_phone_and_owner_name(): void
    {
        $user = User::factory()->create(['role' => 'umkm', 'status' => 'active']);

        $response = $this->actingAs($user)->put(route('umkm.account.update'), [
            'email' => 'valid@umkm.example.com',
            'business_name' => 'Dapur Kuliner',
            'owner_name' => 'Budi 123 !@#',
            'phone' => '0812huruf',
            'address' => 'Jl. Cempaka No. 12',
        ]);

        $response->assertSessionHasErrors([
            'owner_name' => 'Nama pemilik hanya boleh berisi huruf, spasi, atau tanda petik.',
            'phone' => 'Nomor WhatsApp hanya boleh berisi angka.',
        ]);
    }

    public function test_supplier_can_update_account_and_profile_data_while_ignoring_privileged_fields(): void
    {
        $catOld = CommodityCategory::create(['name' => 'Sayuran']);
        $catNew = CommodityCategory::create(['name' => 'Daging & Unggas']);

        $user = User::factory()->create([
            'email' => 'lama@supplier.example.com',
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'commodity_id' => $catOld->id,
            'company_name' => 'CV Pemasok Lama',
            'owner_name' => 'Nama Lama',
            'phone' => '081111111111',
            'warehouse_address' => 'Jl. Gudang Lama No. 1',
            'max_capacity' => '500 kg / Minggu',
            'delivery_area' => 'Kota Malang',
            'nib_number' => '1111111111',
        ]);

        $response = $this->actingAs($user)->put(route('supplier.account.update'), [
            'email' => 'baru@supplier.example.com',
            'company_name' => 'PT Agro Mandiri Sentosa',
            'owner_name' => 'Haji Sutrisno',
            'phone' => '085612345678',
            'commodity_id' => $catNew->id,
            'max_capacity' => '3.500 kg / Minggu',
            'delivery_area' => 'Malang Raya, Blitar, Kediri',
            'nib_number' => '9120009988776',
            'warehouse_address' => 'Jl. Industri Karanglo Kav. 9, Malang',
            'role' => 'admin',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('supplier.account.edit'));
        $response->assertSessionHas('success', 'Data akun & profil Pemasok berhasil diperbarui.');

        $user->refresh();
        $profile = $user->supplierProfile()->firstOrFail();

        $this->assertSame('baru@supplier.example.com', $user->email);
        $this->assertSame('supplier', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('PT Agro Mandiri Sentosa', $profile->company_name);
        $this->assertSame('Haji Sutrisno', $profile->owner_name);
        $this->assertSame('085612345678', $profile->phone);
        $this->assertSame($catNew->id, $profile->commodity_id);
        $this->assertSame('3.500 kg / Minggu', $profile->max_capacity);
        $this->assertSame('Malang Raya, Blitar, Kediri', $profile->delivery_area);
        $this->assertSame('9120009988776', $profile->nib_number);
        $this->assertSame('Jl. Industri Karanglo Kav. 9, Malang', $profile->warehouse_address);
    }

    public function test_supplier_account_update_preserves_owner_name_and_creates_missing_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'tetap@supplier.example.com',
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('supplier.account.update'), [
            'email' => 'tetap@supplier.example.com',
            'company_name' => 'Kelompok Tani Sejahtera',
            'owner_name' => '',
            'phone' => '085700112233',
            'commodity_id' => '',
            'max_capacity' => '1.000 kg / Minggu',
            'delivery_area' => 'Malang Raya',
            'nib_number' => '',
            'warehouse_address' => 'Jl. Raya Bululawang No. 14',
        ]);

        $response->assertRedirect(route('supplier.account.edit'));
        $response->assertSessionHasNoErrors();

        $profile = $user->supplierProfile()->first();
        $this->assertNotNull($profile);
        $this->assertSame('Kelompok Tani Sejahtera', $profile->company_name);
        $this->assertSame('Kelompok Tani Sejahtera', $profile->owner_name);
        $this->assertNull($profile->commodity_id);
        $this->assertNull($profile->nib_number);
    }

    public function test_supplier_account_update_fails_validation_for_invalid_inputs(): void
    {
        User::factory()->create(['email' => 'taken@supplier.example.com']);
        $user = User::factory()->create([
            'email' => 'awal@supplier.example.com',
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('supplier.account.update'), [
            'email' => 'taken@supplier.example.com',
            'company_name' => 'AB',
            'phone' => '123',
            'commodity_id' => 'not-a-uuid',
            'max_capacity' => '',
            'delivery_area' => '',
            'nib_number' => 'ABC-999',
            'warehouse_address' => 'Jl.',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email ini sudah digunakan oleh akun lain.',
            'company_name',
            'phone',
            'commodity_id',
            'max_capacity',
            'delivery_area',
            'nib_number' => 'Nomor NIB / Legalitas Usaha hanya boleh berisi angka.',
            'warehouse_address',
        ]);

        $user->refresh();
        $this->assertSame('awal@supplier.example.com', $user->email);
    }

    public function test_umkm_and_supplier_can_update_password_with_valid_current_password(): void
    {
        $umkm = User::factory()->create([
            'role' => 'umkm',
            'status' => 'active',
            'password' => Hash::make('oldsecret123'),
        ]);

        $umkmResponse = $this->actingAs($umkm)->put(route('umkm.account.password.update'), [
            'current_password' => 'oldsecret123',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ]);

        $umkmResponse->assertRedirect(route('umkm.account.edit'));
        $umkmResponse->assertSessionHas('success', 'Kata sandi akun berhasil diperbarui.');
        $umkm->refresh();
        $this->assertTrue(Hash::check('newsecret456', $umkm->password));
        $this->assertFalse(Hash::check('oldsecret123', $umkm->password));

        $supplier = User::factory()->create([
            'role' => 'supplier',
            'status' => 'active',
            'password' => Hash::make('supplierOld123'),
        ]);

        $supplierResponse = $this->actingAs($supplier)->put(route('supplier.account.password.update'), [
            'current_password' => 'supplierOld123',
            'password' => 'supplierNew456',
            'password_confirmation' => 'supplierNew456',
        ]);

        $supplierResponse->assertRedirect(route('supplier.account.edit'));
        $supplierResponse->assertSessionHas('success', 'Kata sandi akun berhasil diperbarui.');
        $supplier->refresh();
        $this->assertTrue(Hash::check('supplierNew456', $supplier->password));
        $this->assertFalse(Hash::check('supplierOld123', $supplier->password));
    }

    public function test_password_update_rejects_wrong_current_password_and_preserves_existing_hash(): void
    {
        $user = User::factory()->create([
            'role' => 'umkm',
            'status' => 'active',
            'password' => Hash::make('realpassword123'),
        ]);

        $response = $this->actingAs($user)->put(route('umkm.account.password.update'), [
            'current_password' => 'wrongpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors([
            'current_password' => 'Kata sandi saat ini tidak sesuai.',
        ]);
        $user->refresh();
        $this->assertTrue(Hash::check('realpassword123', $user->password));
    }

    public function test_password_update_rejects_same_password_and_short_or_mismatched_password(): void
    {
        $supplier = User::factory()->create([
            'role' => 'supplier',
            'status' => 'active',
            'password' => Hash::make('samepass123'),
        ]);

        $sameResponse = $this->actingAs($supplier)->put(route('supplier.account.password.update'), [
            'current_password' => 'samepass123',
            'password' => 'samepass123',
            'password_confirmation' => 'samepass123',
        ]);

        $sameResponse->assertSessionHasErrors([
            'password' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        $mismatchResponse = $this->actingAs($supplier)->put(route('supplier.account.password.update'), [
            'current_password' => 'samepass123',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $mismatchResponse->assertSessionHasErrors(['password']);
        $supplier->refresh();
        $this->assertTrue(Hash::check('samepass123', $supplier->password));
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function portalEditStateProvider(): array
    {
        return [
            'umkm portal' => ['umkm', 'umkm.account.edit', 'umkm.account.update', 'umkm.account.password.update'],
            'supplier portal' => ['supplier', 'supplier.account.edit', 'supplier.account.update', 'supplier.account.password.update'],
        ];
    }

    #[DataProvider('portalEditStateProvider')]
    public function test_account_pages_start_locked_with_edit_icons_and_reopen_relevant_section_on_validation_error(
        string $role,
        string $editRoute,
        string $profileUpdateRoute,
        string $passwordUpdateRoute,
    ): void {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'password' => Hash::make('secret1234'),
        ]);

        $initialResponse = $this->actingAs($user)->get(route($editRoute));
        $initialResponse->assertOk();
        $initialResponse->assertSee('Edit Data');
        $initialResponse->assertSee('Batalkan Edit');
        $initialResponse->assertSee('Ganti Foto Profil');
        $initialResponse->assertDontSee('Data dalam mode lihat');
        $initialResponse->assertSee('aria-label="Edit Data Akun"', false);
        $initialResponse->assertSee('aria-label="Edit Kata Sandi"', false);
        $initialResponse->assertSee('aria-label="Batalkan Edit Data Akun"', false);
        $initialResponse->assertSee('aria-label="Batalkan Edit Kata Sandi"', false);
        $initialResponse->assertSee('isEditingProfile: false', false);
        $initialResponse->assertSee('isEditingPassword: false', false);

        $profileErrorResponse = $this->actingAs($user)
            ->from(route($editRoute))
            ->followingRedirects()
            ->put(route($profileUpdateRoute), []);

        $profileErrorResponse->assertOk();
        $profileErrorResponse->assertSee('isEditingProfile: true', false);
        $profileErrorResponse->assertSee('isEditingPassword: false', false);

        $passwordErrorResponse = $this->actingAs($user)
            ->from(route($editRoute))
            ->followingRedirects()
            ->put(route($passwordUpdateRoute), [
                'current_password' => 'wrongpassword',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ]);

        $passwordErrorResponse->assertOk();
        $passwordErrorResponse->assertSee('Kata sandi saat ini tidak sesuai.');
        $passwordErrorResponse->assertSee('isEditingPassword: true', false);
        $passwordErrorResponse->assertSee('isEditingProfile: false', false);
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function portalPhotoRoutesProvider(): array
    {
        return [
            'umkm portal' => ['umkm', 'umkm.account.edit', 'umkm.account.photo.update', 'umkm.account.photo.destroy'],
            'supplier portal' => ['supplier', 'supplier.account.edit', 'supplier.account.photo.update', 'supplier.account.photo.destroy'],
        ];
    }

    #[DataProvider('portalPhotoRoutesProvider')]
    public function test_users_can_upload_replace_view_and_delete_profile_photo(
        string $role,
        string $editRoute,
        string $photoUpdateRoute,
        string $photoDestroyRoute,
    ): void {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $firstUpload = UploadedFile::fake()->image('avatar-pertama.jpg', 240, 240);
        $uploadResponse = $this->actingAs($user)->put(route($photoUpdateRoute), [
            'photo' => $firstUpload,
        ]);

        $uploadResponse->assertRedirect(route($editRoute));
        $uploadResponse->assertSessionHas('success', 'Foto profil akun berhasil diperbarui.');

        $user->refresh();
        $firstPath = $user->profile_photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);
        $this->assertNotNull($user->profile_photo_url);

        $pageWithPhoto = $this->actingAs($user)->get(route($editRoute));
        $pageWithPhoto->assertOk();
        $pageWithPhoto->assertSee(route('account.photo.show', ['user' => $user->id]), false);

        $mediaResponse = $this->actingAs($user)->get($user->profile_photo_url);
        $mediaResponse->assertOk();

        $secondUpload = UploadedFile::fake()->image('avatar-baru.png', 240, 240);
        $replaceResponse = $this->actingAs($user)->put(route($photoUpdateRoute), [
            'photo' => $secondUpload,
        ]);

        $replaceResponse->assertRedirect(route($editRoute));
        $user->refresh();
        $secondPath = $user->profile_photo_path;
        $this->assertNotNull($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $deleteResponse = $this->actingAs($user)->delete(route($photoDestroyRoute));
        $deleteResponse->assertRedirect(route($editRoute));
        $deleteResponse->assertSessionHas('success', 'Foto profil akun berhasil dihapus.');

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
        $this->assertNull($user->profile_photo_url);
        Storage::disk('public')->assertMissing($secondPath);

        $this->actingAs($user)
            ->get(route('account.photo.show', ['user' => $user->id]))
            ->assertNotFound();
    }

    #[DataProvider('portalPhotoRoutesProvider')]
    public function test_profile_photo_upload_rejects_invalid_and_oversized_files_and_reopens_photo_editor(
        string $role,
        string $editRoute,
        string $photoUpdateRoute,
        string $photoDestroyRoute,
    ): void {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $invalidFileResponse = $this->actingAs($user)
            ->from(route($editRoute))
            ->followingRedirects()
            ->put(route($photoUpdateRoute), [
                'photo' => UploadedFile::fake()->create('dokumen.pdf', 120, 'application/pdf'),
            ]);

        $invalidFileResponse->assertOk();
        $invalidFileResponse->assertSee('isEditingPhoto: true', false);
        $user->refresh();
        $this->assertNull($user->profile_photo_path);

        $oversizedFileResponse = $this->actingAs($user)->put(route($photoUpdateRoute), [
            'photo' => UploadedFile::fake()->image('terlalu-besar.png')->size(3000),
        ]);

        $oversizedFileResponse->assertSessionHasErrors([
            'photo' => 'Ukuran foto profil maksimal 2 MB.',
        ]);
        $user->refresh();
        $this->assertNull($user->profile_photo_path);

        $this->actingAs($user)
            ->delete(route($photoDestroyRoute))
            ->assertRedirect(route($editRoute));
    }

    public function test_supplier_layout_shows_initial_avatar_in_top_bar_and_omits_sidebar_footer_profile_card(): void
    {
        $user = User::factory()->create([
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Kelompok Tani Poultry Malang',
            'owner_name' => 'H. Tani Kurniawan',
            'phone' => '089988776655',
            'warehouse_address' => 'Jl. Raya Malang No. 10',
            'max_capacity' => '10 Ton / Minggu',
            'delivery_area' => 'Jawa Timur',
        ]);

        $response = $this->actingAs($user)->get(route('supplier.account.edit'));

        $response->assertDontSee('Pemasok Terverifikasi');

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $sidebarAccountLinks = $xpath->query('//aside[@id="supplier-sidebar"]//a[contains(@href, "/supplier/akun")]');
        $this->assertNotFalse($sidebarAccountLinks);
        $this->assertSame(1, $sidebarAccountLinks->length);
        $this->assertSame('Kelola Akun', trim($sidebarAccountLinks->item(0)?->textContent ?? ''));

        $topBarChips = $xpath->query('//header//a[@title="Kelola Data Akun Pemasok"]');
        $this->assertNotFalse($topBarChips);
        $this->assertSame(2, $topBarChips->length);

        foreach ($topBarChips as $chip) {
            $chipText = preg_replace('/\s+/', ' ', trim($chip->textContent ?? '')) ?? '';
            $this->assertSame('K Kelompok Tani Poultry Malang', $chipText);
            $this->assertSame(0, $xpath->query('.//img', $chip)->length);
        }
    }

    public function test_supplier_layout_displays_uploaded_profile_photo_in_top_bar_beside_company_name(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'supplier',
            'status' => 'active',
        ]);

        SupplierProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Kelompok Tani Poultry Malang',
            'owner_name' => 'H. Tani Kurniawan',
            'phone' => '089988776655',
            'warehouse_address' => 'Jl. Raya Malang No. 10',
            'max_capacity' => '10 Ton / Minggu',
            'delivery_area' => 'Jawa Timur',
        ]);

        $path = UploadedFile::fake()->image('profil-pemasok.png', 200, 200)->store('profile-photos', 'public');
        $user->forceFill(['profile_photo_path' => $path])->save();

        $response = $this->actingAs($user)->get(route('supplier.account.edit'));

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $topBarChips = $xpath->query('//header//a[@title="Kelola Data Akun Pemasok"]');
        $this->assertNotFalse($topBarChips);
        $this->assertSame(2, $topBarChips->length);

        $expectedPhotoPrefix = route('account.photo.show', ['user' => $user->id]);

        foreach ($topBarChips as $chip) {
            $chipImages = $xpath->query('.//img', $chip);
            $this->assertNotFalse($chipImages);
            $this->assertSame(1, $chipImages->length);

            $img = $chipImages->item(0);
            $src = $img?->attributes?->getNamedItem('src')?->nodeValue ?? '';
            $alt = $img?->attributes?->getNamedItem('alt')?->nodeValue ?? '';

            $this->assertStringStartsWith($expectedPhotoPrefix, $src);
            $this->assertSame('Foto Profil Kelompok Tani Poultry Malang', $alt);
            $this->assertSame('Kelompok Tani Poultry Malang', trim($chip->textContent ?? ''));
        }
    }

    public function test_umkm_navbar_groups_account_and_logout_under_profile_dropdown_and_omits_active_status_badge(): void
    {
        $user = User::factory()->create([
            'email' => 'dapur.sambal@example.com',
            'role' => 'umkm',
            'status' => 'active',
        ]);

        UmkmProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Dapur Sambal Malang',
            'owner_name' => 'Siti Aminah',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10, Malang',
        ]);

        $response = $this->actingAs($user)->get(route('umkm.account.edit'));

        $response->assertDontSee('Akun UMKM Aktif');

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $topBarStandaloneAccount = $xpath->query('//header/div[1]//a[not(ancestor::*[@data-umkm-profile-menu])][normalize-space(.)="Kelola Akun"]');
        $this->assertNotFalse($topBarStandaloneAccount);
        $this->assertSame(0, $topBarStandaloneAccount->length);

        $topBarStandaloneLogout = $xpath->query('//header/div[1]//*[(self::a or self::button) and not(ancestor::*[@data-umkm-profile-menu])][normalize-space(.)="Keluar"]');
        $this->assertNotFalse($topBarStandaloneLogout);
        $this->assertSame(0, $topBarStandaloneLogout->length);

        $profileTriggers = $xpath->query('//header//*[@data-umkm-profile-trigger]');
        $this->assertNotFalse($profileTriggers);
        $this->assertSame(1, $profileTriggers->length);
        $this->assertStringContainsString('Dapur Sambal Malang', $profileTriggers->item(0)?->textContent ?? '');
        $this->assertStringContainsString('dapur.sambal@example.com', $profileTriggers->item(0)?->textContent ?? '');

        $dropdownMenus = $xpath->query('//header//*[@data-umkm-profile-menu]');
        $this->assertNotFalse($dropdownMenus);
        $this->assertSame(1, $dropdownMenus->length);

        $menuAccountLinks = $xpath->query('.//a[@role="menuitem"][normalize-space(.)="Kelola Akun"]', $dropdownMenus->item(0));
        $this->assertNotFalse($menuAccountLinks);
        $this->assertSame(1, $menuAccountLinks->length);
        $this->assertSame(
            route('umkm.account.edit'),
            $menuAccountLinks->item(0)?->attributes?->getNamedItem('href')?->nodeValue
        );

        $menuLogoutButtons = $xpath->query('.//button[@role="menuitem"][normalize-space(.)="Keluar"]', $dropdownMenus->item(0));
        $this->assertNotFalse($menuLogoutButtons);
        $this->assertSame(1, $menuLogoutButtons->length);
        $response->assertSee('@click="profileMenuOpen = false; $dispatch(\'open-logout-modal\')"', false);
    }
}
