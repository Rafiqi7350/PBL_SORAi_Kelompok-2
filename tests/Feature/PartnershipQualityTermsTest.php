<?php

namespace Tests\Feature;

use App\Models\Commodity;
use App\Models\Partnership;
use App\Models\SupplierProduct;
use App\Models\SupplierProfile;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnershipQualityTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $umkmUser;

    private UmkmProfile $umkmProfile;

    private User $supplierUser;

    private SupplierProfile $supplierProfile;

    private SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->umkmUser = User::create([
            'email' => 'umkm.terms@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
            'status' => 'active',
        ]);

        $this->umkmProfile = UmkmProfile::create([
            'user_id' => $this->umkmUser->id,
            'business_name' => 'Dapur Kuliner Mantap',
            'owner_name' => 'Siti Aminah',
            'phone' => '081234567890',
            'address' => 'Jl. Veteran No. 5, Malang',
        ]);

        $this->supplierUser = User::create([
            'email' => 'supplier.terms@example.com',
            'password' => Hash::make('password123'),
            'role' => 'supplier',
            'status' => 'active',
        ]);

        $this->supplierProfile = SupplierProfile::create([
            'user_id' => $this->supplierUser->id,
            'company_name' => 'PT Pangan Segar Nusantara',
            'owner_name' => 'Ahmad Suhendra',
            'phone' => '081987654321',
            'warehouse_address' => 'Jl. Kebon Jeruk No. 12, Batu',
            'max_capacity' => '5 Ton / Minggu',
            'delivery_area' => 'Malang Raya',
        ]);

        $commodity = Commodity::create([
            'name' => 'Daging Ayam Broiler',
            'category' => 'Daging & Unggas',
            'unit' => 'kg',
            'government_ref_price' => 38000,
        ]);

        $this->product = SupplierProduct::create([
            'supplier_id' => $this->supplierProfile->id,
            'commodity_id' => $commodity->id,
            'price' => 38000,
            'stock_status' => 'Tersedia',
            'stock_capacity' => '500 kg/hari',
            'is_halal_certified' => true,
        ]);
    }

    public function test_partnership_model_parses_quality_terms_and_clean_notes(): void
    {
        $partnership = new Partnership([
            'submission_details' => '[Ketentuan Mutu: Garansi Segar & Bebas Busuk | Wajib Tiba Tepat Waktu | Kemasan Tertutup & Higienis] Tolong potong ayam karkas jadi 8 bagian.',
        ]);

        $this->assertCount(3, $partnership->quality_terms);
        $this->assertContains('Garansi Segar & Bebas Busuk', $partnership->quality_terms);
        $this->assertContains('Wajib Tiba Tepat Waktu', $partnership->quality_terms);
        $this->assertContains('Kemasan Tertutup & Higienis', $partnership->quality_terms);
        $this->assertSame('Tolong potong ayam karkas jadi 8 bagian.', $partnership->clean_notes);
    }

    public function test_partnership_model_handles_legacy_plain_notes_without_tags(): void
    {
        $partnership = new Partnership([
            'submission_details' => 'Pasokan terikat kontrak kemitraan resmi.',
        ]);

        $this->assertEmpty($partnership->quality_terms);
        $this->assertSame('Pasokan terikat kontrak kemitraan resmi.', $partnership->clean_notes);
    }

    public function test_umkm_can_submit_request_with_quality_terms(): void
    {
        $this->actingAs($this->umkmUser);

        $response = $this->post(route('umkm.katalog.ajukan'), [
            'supplier_product_id' => $this->product->id,
            'supplier_id' => $this->supplierProfile->id,
            'unit' => 'kg',
            'volume' => 50,
            'frekuensi' => '2x Seminggu (Senin & Kamis)',
            'terms' => [
                'Garansi Segar & Bebas Busuk',
                'Wajib Tiba Tepat Waktu',
                'Kemasan Tertutup & Higienis',
            ],
            'submission_details' => 'Kemas per kantong 5 kg.',
        ]);

        $response->assertRedirect(route('umkm.katalog.index', ['tab' => 'riwayat-permintaan']));

        $partnership = Partnership::where('umkm_id', $this->umkmProfile->id)
            ->where('supplier_id', $this->supplierProfile->id)
            ->first();

        $this->assertNotNull($partnership);
        $this->assertStringContainsString('Garansi Segar & Bebas Busuk', $partnership->submission_details);
        $this->assertStringContainsString('Kemas per kantong 5 kg.', $partnership->submission_details);

        $this->assertCount(3, $partnership->quality_terms);
        $this->assertSame('Kemas per kantong 5 kg.', $partnership->clean_notes);
    }

    public function test_supplier_pengajuan_masuk_page_renders_quality_terms_chips(): void
    {
        Partnership::create([
            'umkm_id' => $this->umkmProfile->id,
            'supplier_id' => $this->supplierProfile->id,
            'submission_details' => '[Ketentuan Mutu: Garansi Segar & Bebas Busuk | Kemasan Tertutup & Higienis] Khusus untuk stok ayam resto.',
            'status' => 'pending',
        ]);

        $this->actingAs($this->supplierUser);

        $response = $this->get(route('supplier.pengajuan-masuk.index'));
        $response->assertStatus(200);
        $response->assertSee('Ketentuan Mutu &amp; Pengiriman Dapur', false);
        $response->assertSee('Garansi Segar &amp; Bebas Busuk', false);
        $response->assertSee('Kemasan Tertutup &amp; Higienis', false);
        $response->assertSee('Khusus untuk stok ayam resto.');
    }
}
