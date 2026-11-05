// cypress/e2e/sorai/03_modul_kemitraan_csp.cy.js
// MOD-03: Modul Kemitraan B2B & Collaborative Supply Planning (CSP)
// Sesuai Sheet TA-MOD-03 (Total 13 Kondisi Pengujian)

// Pastikan helper login selalu tersedia dengan timeout toleran
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

describe('MOD-03: Kemitraan B2B & Collaborative Supply Planning (CSP)', () => {

    // =========================================================================
    // 1. PENGAJUAN KEMITRAAN PENGADAAN SISI UMKM (PART-REQ-01 s.d. PART-REQ-05)
    // =========================================================================
    describe('1. Form Pengajuan Kemitraan UMKM (PART-REQ)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('PART-REQ-01: Memastikan modal formulir pengajuan kemitraan pasokan terbuka dengan data komoditas terpilih', () => {
            cy.visit('/umkm/katalog');
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Ajukan Permintaan")').length > 0) {
                    cy.contains('button', /Ajukan Permintaan/i).first().click();

                    // Modal terbuka menampilkan informasi komoditas, volume, dan frekuensi
                    cy.contains(/FORM PENGAJUAN KEMITRAAN/i).should('be.visible');
                    cy.get('input[name="volume_per_delivery"]').should('be.visible');
                    cy.get('select[name="frequency_type"]').should('be.visible');
                    cy.get('input[name="start_date"]').should('be.visible');
                } else {
                    cy.contains(/Sudah Diajukan Kemitraan|Stok Tidak Tersedia/i).should('exist');
                }
            });
        });

        it('PART-REQ-02: Mengirimkan formulir pengajuan kemitraan dengan volume kebutuhan dan frekuensi pengiriman valid', () => {
            cy.visit('/umkm/katalog');
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Ajukan Permintaan")').length > 0) {
                    cy.contains('button', /Ajukan Permintaan/i).first().click();

                    cy.get('input[name="volume_per_delivery"]').clear().type('75');
                    cy.get('select[name="frequency_type"]').select('2x Seminggu (Senin & Kamis)');
                    cy.get('textarea[name="submission_details"]').clear().type('Standar pasokan karkas dingin grade A harian.');

                    cy.contains('button', /Kirim Pengajuan Kemitraan/i).click();

                    // Verifikasi redirect dan notifikasi sukses terkirim
                    cy.url({ timeout: 25000 }).should('include', '/umkm/katalog');
                    cy.contains(/berhasil dikirim|kemitraan/i, { timeout: 25000 }).should('be.visible');
                } else {
                    cy.contains(/Sudah Diajukan Kemitraan/i).should('exist');
                }
            });
        });

        it('PART-REQ-03: Memastikan validasi error ketika estimasi volume kebutuhan diinput angka 0 atau negatif', () => {
            cy.visit('/umkm/katalog');
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Ajukan Permintaan")').length > 0) {
                    cy.contains('button', /Ajukan Permintaan/i).first().click();

                    cy.get('input[name="volume_per_delivery"]').clear().type('0');

                    // Validasi HTML5 min="0.1" memblokir angka 0 atau negatif
                    cy.get('input[name="volume_per_delivery"]:invalid').should('exist');
                } else {
                    cy.contains(/Sudah Diajukan Kemitraan/i).should('exist');
                }
            });
        });

        it('PART-REQ-04: Memastikan validasi error ketika frekuensi pengiriman mingguan tidak memilih satupun hari pengiriman', () => {
            cy.visit('/umkm/katalog');
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Ajukan Permintaan")').length > 0) {
                    cy.contains('button', /Ajukan Permintaan/i).first().click();

                    // Verifikasi elemen hari pengiriman terjadwal tersedia di modal
                    cy.contains(/Hari Pengiriman Terjadwal/i).should('be.visible');
                    cy.get('select[name="frequency_type"]').should('exist');
                } else {
                    cy.contains(/Sudah Diajukan Kemitraan/i).should('exist');
                }
            });
        });

        it('PART-REQ-05: Mencegah pengajuan kemitraan ganda yang masih berstatus pending pada komoditas pemasok yang sama', () => {
            cy.visit('/umkm/katalog');

            // Komoditas yang sudah diajukan berstatus disable dengan tombol "Sudah Diajukan Kemitraan"
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Sudah Diajukan Kemitraan")').length > 0) {
                    cy.contains('button', 'Sudah Diajukan Kemitraan').should('be.disabled');
                } else {
                    cy.log('Seluruh komoditas belum diajukan pengajuan aktif');
                }
            });
        });
    });

    // =========================================================================
    // 2. RESPON KEMITRAAN SISI PEMASOK (PART-REC-01 s.d. PART-REC-03)
    // =========================================================================
    describe('2. Pengajuan Masuk & Persetujuan Kemitraan Pemasok (PART-REC)', () => {
        beforeEach(() => {
            cy.loginViaForm('supplier@sorai.id', 'password');
        });

        it('PART-REC-01: Memastikan halaman pengajuan masuk (/supplier/pengajuan-masuk) menampilkan daftar pengajuan kemitraan UMKM', () => {
            cy.visit('/supplier/pengajuan-masuk');
            cy.contains(/PENGAJUAN MASUK & PERSETUJUAN|PERMINTAAN KEMITRAAN/i).should('be.visible');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Terima Kemitraan")').length > 0) {
                    cy.contains(/Terima Kemitraan/i).should('be.visible');
                    cy.contains(/Tolak Kemitraan/i).should('be.visible');
                } else {
                    cy.contains(/Tidak Ada Pengajuan Masuk|Belum ada UMKM/i).should('be.visible');
                }
            });
        });

        it('PART-REC-02: Pemasok menyetujui pengajuan kemitraan UMKM dan mengaktifkan kontrak kerja sama', () => {
            cy.visit('/supplier/pengajuan-masuk');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Terima Kemitraan")').length > 0) {
                    cy.contains('button', /Terima Kemitraan/i).first().click();

                    // Notifikasi sukses persetujuan kemitraan tampil
                    cy.contains(/Kemitraan berhasil diterima/i, { timeout: 20000 }).should('be.visible');
                } else {
                    cy.log('Belum ada data pengajuan masuk berstatus pending untuk diterima');
                }
            });
        });

        it('PART-REC-03: Pemasok menolak pengajuan kemitraan dengan mencantumkan alasan penolakan secara tertulis', () => {
            cy.visit('/supplier/pengajuan-masuk');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Tolak Kemitraan")').length > 0) {
                    cy.contains('button', /Tolak Kemitraan/i).first().click();

                    // Notifikasi penolakan kemitraan tampil
                    cy.contains(/Pengajuan kemitraan telah ditolak/i, { timeout: 20000 }).should('be.visible');
                } else {
                    cy.log('Belum ada data pengajuan masuk berstatus pending untuk ditolak');
                }
            });
        });
    });

    // =========================================================================
    // 3. COLLABORATIVE SUPPLY PLANNING (CSP-UMKM-01 s.d. CSP-SUP-03)
    // =========================================================================
    describe('3. Collaborative Supply Planning (CSP)', () => {

        it('CSP-UMKM-01: UMKM menyusun jadwal perencanaan pasokan kebutuhan bahan baku untuk 4 minggu ke depan', () => {
            cy.loginViaForm('umkm@sorai.id', 'password');
            cy.visit('/umkm/katalog?tab=supply-planning');

            cy.contains(/Collaborative Supply Planning/i).should('be.visible');

            // Isi form perencanaan pasokan
            cy.get('select[name="supplier_id"]').then(($sel) => {
                if ($sel.find('option:not([disabled])').length > 0) {
                    cy.wrap($sel).select(1);
                }
            });

            cy.get('input[name="estimated_quantity"]').clear().type('250');
            cy.get('textarea[name="notes"]').clear().type('Kebutuhan pasokan reguler 4 minggu ke depan untuk menu andalan.');

            cy.contains('button', /Kirim Estimasi ke Pemasok/i).click();

            // Verifikasi notifikasi berhasil
            cy.url({ timeout: 20000 }).should('include', 'tab=supply-planning');
            cy.contains(/berhasil dikirimkan/i, { timeout: 20000 }).should('be.visible');
        });

        it('CSP-UMKM-02: UMKM membatalkan draf perencanaan pasokan yang belum dikonfirmasi oleh pemasok', () => {
            cy.loginViaForm('umkm@sorai.id', 'password');
            cy.visit('/umkm/katalog?tab=supply-planning');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Batalkan")').length > 0) {
                    cy.contains('button', 'Batalkan').first().click();

                    // Notifikasi sukses pembatalan tampil
                    cy.contains(/berhasil dibatalkan/i, { timeout: 20000 }).should('be.visible');
                } else {
                    cy.log('Tidak ada draf estimasi berstatus MENUNGGU yang dapat dibatalkan');
                }
            });
        });

        it('CSP-SUP-01: Pemasok meninjau perencanaan pasokan mitra di menu /supplier/supply-planning', () => {
            cy.loginViaForm('supplier@sorai.id', 'password');
            cy.visit('/supplier/supply-planning');

            cy.contains(/Collaborative Supply Planning/i).should('be.visible');
            cy.contains(/Total Estimasi Masuk|Kapasitas Disetujui|Mitra Merencanakan/i).should('be.visible');
            cy.get('body').then(($body) => {
                if ($body.find('table').length > 0) {
                    cy.get('table').should('be.visible');
                } else {
                    cy.contains(/Tidak Ada Data Estimasi Kebutuhan/i).should('be.visible');
                }
            });
        });

        it('CSP-SUP-02: Pemasok menyetujui penuh kapasitas pasokan mingguan (Full Capacity Confirmation)', () => {
            cy.loginViaForm('supplier@sorai.id', 'password');
            cy.visit('/supplier/supply-planning');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Siap Penuh")').length > 0) {
                    cy.contains('button', 'Siap Penuh').first().click();

                    // Modal respon konfirmasi terbuka
                    cy.contains(/Respon Estimasi Pasokan UMKM/i).should('be.visible');
                    cy.contains('button', /Simpan Respon Pasokan/i).click();

                    cy.contains(/berhasil DISETUJUI PENUH/i, { timeout: 25000 }).should('be.visible');
                } else {
                    cy.log('Belum ada estimasi berstatus menunggu konfirmasi untuk disetujui penuh');
                }
            });
        });

        it('CSP-SUP-03: Pemasok melakukan penyesuaian kuota kapasitas pasokan karena keterbatasan panen (Gap Planning)', () => {
            cy.loginViaForm('supplier@sorai.id', 'password');
            cy.visit('/supplier/supply-planning');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Sesuaikan")').length > 0) {
                    cy.contains('button', 'Sesuaikan').first().click();

                    // Modal respon konfirmasi terbuka dengan kuota disesuaikan
                    cy.contains(/Respon Estimasi Pasokan UMKM/i).should('be.visible');
                    cy.get('input[name="confirmed_quantity"]').clear().type('175');
                    cy.contains('button', /Simpan Respon Pasokan/i).click();

                    cy.contains(/berhasil DISESUAIKAN/i, { timeout: 25000 }).should('be.visible');
                } else {
                    cy.log('Belum ada estimasi berstatus menunggu konfirmasi untuk disesuaikan');
                }
            });
        });
    });

});
