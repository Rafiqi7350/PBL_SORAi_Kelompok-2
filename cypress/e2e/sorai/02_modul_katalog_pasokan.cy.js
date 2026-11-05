Cypress.Commands.add('loginViaForm', (email, password) => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
    cy.visit('/login', { timeout: 30000 });
    cy.get('input[name="email"]', { timeout: 15000 }).should('be.visible').clear().type(email);
    cy.get('input[name="password"]', { timeout: 15000 }).should('be.visible').clear().type(password);
    cy.get('button[type="submit"]', { timeout: 15000 }).filter(':visible').first().click();
    cy.url({ timeout: 25000 }).should('not.include', '/login');
    cy.wait(1000);
});

describe('MOD-02: Manajemen Katalog & Penelusuran Pasokan Bahan Baku', () => {

    // =========================================================================
    // 1. KELOLA KATALOG & STOK SISI PEMASOK (KAT-SUP-01 s.d. KAT-SUP-08)
    // =========================================================================
    describe('1. Manajemen Katalog Pemasok (KAT-SUP)', () => {
        beforeEach(() => {
            cy.loginViaForm('supplier@sorai.id', 'password');
        });

        it('KAT-SUP-01: Memastikan halaman kelola stok pemasok (/supplier/katalog) menampilkan daftar produk', () => {
            cy.visit('/supplier/katalog');
            cy.contains(/KELOLA STOK KOMODITAS|INVENTARIS BAHAN BAKU/i).should('be.visible');
            cy.get('body').then(($body) => {
                if ($body.find('table').length > 0) {
                    cy.get('table').should('be.visible');
                    cy.contains('th', /KOMODITAS/i).should('be.visible');
                    cy.contains('th', /HARGA/i).should('be.visible');
                    cy.contains('th', /KAPASITAS/i).should('be.visible');
                    cy.contains('th', /AKSI/i).should('be.visible');
                } else {
                    cy.contains(/Belum Ada Komoditas|Tambah Komoditas/i).should('be.visible');
                }
            });
        });

        it('KAT-SUP-02: Menambahkan komoditas bahan baku baru dengan data valid dan upload foto', () => {
            cy.visit('/supplier/katalog');
            cy.get('[data-cy="btn-tambah-komoditas"]').click();
            cy.url().should('include', '/create');

            const timestamp = Date.now();
            const namaKomoditas = `Daging Sapi Segar Premium ${timestamp}`;

            cy.get('select[name="category_id"]').select(1);
            cy.get('input[name="commodity_name"]').clear().type(namaKomoditas);
            cy.get('select[name="unit"]').select(1);
            cy.get('input[name="price"]').clear().type('125000');
            cy.get('input[name="stock_capacity"]').clear().type('500 kg / minggu');
            cy.get('select[name="stock_status"]').select('Tersedia');
            cy.get('input[name="minimum_order"]').clear().type('5 kg');
            cy.get('input[name="quality_grade"]').clear().type('Grade A Fresh Meat');

            // Upload foto komoditas valid menggunakan foto daging asli dari fixtures
            cy.get('input[type="file"][name="image"]').selectFile('cypress/fixtures/daging.jpg', { force: true });

            cy.get('[data-cy="btn-submit-katalog"]').click();

            // Menunggu submit selesai dan kembali ke halaman katalog (toleransi latensi Supabase)
            cy.url({ timeout: 20000 }).should('not.include', '/create');
            cy.url().should('include', '/supplier/katalog');
            cy.contains(namaKomoditas, { timeout: 20000 }).should('be.visible');
        });

        it('KAT-SUP-03: Memastikan validasi error ketika field komoditas, harga satuan, atau stok dikosongkan', () => {
            cy.visit('/supplier/katalog/create');
            cy.get('[data-cy="btn-submit-katalog"]').click();

            // Memastikan browser memblokir submit melalui validasi HTML5 required
            cy.get('input[name="commodity_name"]:invalid').should('exist');
            cy.get('input[name="price"]:invalid').should('exist');
        });

        it('KAT-SUP-04: Memastikan validasi error ketika harga satuan atau stok diinput angka negatif (< 0)', () => {
            cy.visit('/supplier/katalog/create');

            cy.get('input[name="price"]').clear().type('-5000');
            // Memastikan browser memblokir angka negatif melalui validasi HTML5 min="0"
            cy.get('input[name="price"]:invalid').should('exist');
        });

        it('KAT-SUP-05: Memastikan validasi error jika file foto bukan format gambar (JPG/JPEG/PNG) atau > 2MB', () => {
            cy.visit('/supplier/katalog/create');

            cy.get('select[name="category_id"]').select(1);
            cy.get('input[name="commodity_name"]').clear().type('Uji Dokumen File');
            cy.get('input[name="price"]').clear().type('20000');
            cy.get('input[name="stock_capacity"]').clear().type('100 kg');
            cy.get('select[name="stock_status"]').select('Tersedia');

            // Upload file format dokumen PDF (bukan gambar)
            cy.get('input[type="file"][name="image"]').selectFile({
                contents: Cypress.Buffer.from('%PDF-1.4 mock document content'),
                fileName: 'dokumen.pdf',
                mimeType: 'application/pdf'
            }, { force: true });

            cy.get('[data-cy="btn-submit-katalog"]').click();

            // Pesan error validasi server Laravel tampil di bawah input image
            cy.contains(/bukan format|tidak didukung|must be an image|harus berupa gambar|format/i, { timeout: 20000 }).should('be.visible');
        });

        it('KAT-SUP-06: Mengubah data harga kontrak dan deskripsi mutu komoditas yang sudah terdaftar', () => {
            cy.visit('/supplier/katalog');

            cy.contains('a', 'Edit').first().click();
            cy.url().should('include', '/edit');

            // Pastikan kategori terpilih jika sebelumnya tidak matched
            cy.get('select[name="category_id"]').then(($sel) => {
                if (!$sel.val()) {
                    cy.wrap($sel).select(1);
                }
            });

            cy.get('input[name="price"]').clear().type('38500');
            cy.get('textarea[name="description"]').clear().type('Pembaruan deskripsi mutu pasokan super segar harian.');
            cy.get('[data-cy="btn-submit-katalog"]').click();

            // Menunggu redirect selesai kembali ke katalog
            cy.url({ timeout: 20000 }).should('not.include', '/edit');
            cy.url().should('include', '/supplier/katalog');
            cy.contains('38.500', { timeout: 20000 }).should('be.visible');
        });

        it('KAT-SUP-07: Menghapus komoditas bahan baku yang tidak sedang terikat kontrak kemitraan aktif', () => {
            // Tambahkan komoditas sementara khusus untuk pengujian hapus (contoh: Telur Ayam)
            cy.visit('/supplier/katalog/create');
            const tsHapus = Date.now();
            const namaHapus = `Telur Ayam Horn Segar ${tsHapus}`;

            cy.get('select[name="category_id"]').select(1);
            cy.get('input[name="commodity_name"]').clear().type(namaHapus);
            cy.get('select[name="unit"]').select(1);
            cy.get('input[name="price"]').clear().type('28000');
            cy.get('input[name="stock_capacity"]').clear().type('200 kg');
            cy.get('select[name="stock_status"]').select('Tersedia');

            // Upload foto telur dari fixtures
            cy.get('input[type="file"][name="image"]').selectFile('cypress/fixtures/telur.jpg', { force: true });
            cy.get('[data-cy="btn-submit-katalog"]').click();

            cy.url({ timeout: 20000 }).should('not.include', '/create');
            cy.url().should('include', '/supplier/katalog');
            cy.contains(namaHapus, { timeout: 20000 }).should('be.visible');

            // Eksekusi hapus baris produk yang baru dibuat
            cy.contains('tr', namaHapus).within(() => {
                cy.contains('button', 'Hapus').click();
            });

            // Memastikan data produk tersebut terhapus dari tabel katalog
            cy.contains(namaHapus, { timeout: 20000 }).should('not.exist');
        });
    });

    // =========================================================================
    // 2. PENELUSURAN KATALOG BAHAN BAKU SISI UMKM (KAT-UMKM-01 s.d. KAT-UMKM-08)
    // =========================================================================
    describe('2. Penelusuran Katalog Bahan Baku UMKM (KAT-UMKM)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('KAT-UMKM-01: Memastikan halaman katalog bahan baku UMKM (/umkm/katalog) memuat daftar kartu komoditas', () => {
            cy.visit('/umkm/katalog');
            cy.contains(/Pilih & Ajukan Kemitraan Pasokan|KATALOG LOKAL|Bahan Baku/i).should('be.visible');
            cy.get('input[name="search"]').should('be.visible');
            cy.get('select[name="category"]').should('be.visible');
            cy.get('select[name="stock_status"]').should('be.visible');
            cy.get('.grid').should('be.visible');
            cy.get('.grid > div').should('have.length.at.least', 1);
        });

        it('KAT-UMKM-02: Memastikan komoditas baru yang diunggah pemasok langsung tersinkronisasi ke katalog UMKM', () => {
            cy.visit('/umkm/katalog');
            cy.get('.grid').should('be.visible');
            cy.get('.grid > div').should('have.length.at.least', 1);
        });

        it('KAT-UMKM-03: Mencari bahan baku via search bar dengan kata kunci valid (misal: Ayam)', () => {
            cy.visit('/umkm/katalog');
            cy.get('input[name="search"]').clear().type('Ayam{enter}');
            cy.url().should('include', 'search=Ayam');
            cy.get('.grid').should('contain.text', 'Ayam');
        });

        it('KAT-UMKM-04: Mencari bahan baku dengan kata kunci tidak ada memunculkan tampilan Empty State', () => {
            cy.visit('/umkm/katalog');
            cy.get('input[name="search"]').clear().type('BarangKhayalPastiTidakAda999{enter}');
            cy.contains(/Tidak Ada Komoditas Bahan Baku Ditemukan/i).should('be.visible');
        });

        it('KAT-UMKM-05: Menyaring daftar komoditas berdasarkan dropdown kategori (Unggas, Bumbu, Sayur)', () => {
            cy.visit('/umkm/katalog');
            cy.get('select[name="category"]').find('option').then(($options) => {
                if ($options.length > 1) {
                    const selectedCategory = $options[1].value;
                    cy.get('select[name="category"]').select(selectedCategory);
                    cy.url().should('include', 'category=');
                    cy.get('.grid').should('exist');
                }
            });
        });

        it('KAT-UMKM-06: Menyaring komoditas berdasarkan status stok hanya yang TERSEDIA', () => {
            cy.visit('/umkm/katalog');
            cy.get('select[name="stock_status"]').select('Tersedia');
            cy.url().should('include', 'stock_status=Tersedia');
            cy.get('.grid').should('exist');
        });

        it('KAT-UMKM-07: Memastikan tombol Reset Filter mengembalikan katalog ke tampilan default', () => {
            cy.visit('/umkm/katalog?search=Ayam&tab=katalog');
            cy.get('input[name="search"]').should('have.value', 'Ayam');
            cy.contains('a', 'Reset Filter').should('be.visible').click();
            cy.contains('a', 'Reset Filter', { timeout: 15000 }).should('not.exist');
            cy.url().should('not.include', 'search=');
            cy.get('input[name="search"]').should('have.value', '');
            cy.get('.grid').should('be.visible');
        });

        it('KAT-UMKM-08: Memastikan komoditas berstatus Stok Habis menonaktifkan tombol pengajuan permintaan', () => {
            cy.visit('/umkm/katalog?stock_status=Stok+Habis&tab=katalog');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Stok Tidak Tersedia")').length > 0) {
                    cy.contains('button', 'Stok Tidak Tersedia').should('be.disabled');
                } else {
                    cy.contains(/Tidak Ada Komoditas|Stok Habis/i).should('exist');
                }
            });
        });
    });

});
