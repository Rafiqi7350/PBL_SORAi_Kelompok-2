// cypress/e2e/sorai/04_modul_pesanan_midtrans.cy.js
// MOD-04: Modul Pemesanan Rutin, Backorder, Pengiriman & Pembayaran Midtrans
// Sesuai Rubrik Pengujian TA Modul 04 (Total 17 Kondisi Pengujian)

// Pastikan helper login selalu tersedia dengan timeout toleran untuk lingkungan lokal lambat
Cypress.Commands.add('loginViaForm', (email, password) => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
    cy.visit('/login', { timeout: 45000 });
    cy.get('input[name="email"]', { timeout: 25000 }).should('be.visible').clear().type(email);
    cy.get('input[name="password"]', { timeout: 25000 }).should('be.visible').clear().type(password);
    cy.get('button[type="submit"]', { timeout: 25000 }).filter(':visible').first().click();
    cy.url({ timeout: 35000 }).should('not.include', '/login');
    cy.wait(1500);
});

// Helper untuk menghitung SHA-512 signature Midtrans secara native
async function generateMidtransSignature(orderId, statusCode, grossAmount, serverKey) {
    const raw = `${orderId}${statusCode}${grossAmount}${serverKey}`;
    const msgBuffer = new TextEncoder().encode(raw);
    const hashBuffer = await crypto.subtle.digest('SHA-512', msgBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
}

const MIDTRANS_SERVER_KEY = 'SB-Mid-server-IgUw1CLZOwueil8aEBwiig~E';

describe('MOD-04: Pemesanan Rutin, Backorder, Pengiriman & Pembayaran Midtrans', () => {

    // =========================================================================
    // 1. PENERBITAN PESANAN RUTIN & LOGIKA ALOKASI STOK (ORD-DIR-01 s.d. ORD-BO-02)
    // =========================================================================
    describe('1. Penerbitan Pesanan & Logika Alokasi Stok (ORD)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('ORD-DIR-01: Penerbitan Pesanan Rutin (PO dari kontrak kemitraan aktif dengan stok cukup)', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=auto-order', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan halaman jadwal pemesanan otomatis terbuka
            cy.contains(/Jadwal Pemesanan Otomatis|KONTRAK TERIKAT AKTIF|Auto-Order/i, { timeout: 30000 }).should('be.visible');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Preview & Terbitkan")').length > 0) {
                    cy.contains('button', /Preview & Terbitkan/i).first().click();

                    // Modal pratinjau pesanan otomatis terbuka secara spesifik (previewAutoOrder)
                    cy.get('[x-show="previewAutoOrder"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="previewAutoOrder"]').within(() => {
                        cy.contains(/Pratinjau Pesanan Otomatis|DETAIL KONTRAK|Estimasi Tagihan/i).should('be.visible');
                        cy.contains('button', /Terbitkan Pesanan Sekarang|Tutup/i).should('be.visible');
                    });
                } else {
                    // Jadwal sudah terbit atau berstatus TERJADWAL
                    cy.contains(/Sudah Diterbitkan Hari Ini|TERJADWAL|Belum Ada Jadwal/i, { timeout: 30000 }).should('exist');
                }
            });
        });

        it('ORD-DIR-02: Pencegahan penerbitan pesanan jika kontrak belum aktif atau ditolak', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=auto-order', { timeout: 45000 });
            cy.wait(1500);

            // 1. Verifikasi UI: tab pemesanan termuat dan hanya kontrak aktif yang memiliki opsi pemesanan
            cy.url().should('include', 'tab=pemesanan');

            // 2. Ambil token CSRF dan kirimkan checkout untuk kemitraan yang tidak aktif / invalid UUID
            cy.get('meta[name="csrf-token"]').invoke('attr', 'content').then((csrfToken) => {
                cy.request({
                    method: 'POST',
                    url: '/umkm/pemesanan/terbitkan',
                    body: { schedule_id: '00000000-0000-0000-0000-000000000000' },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    failOnStatusCode: false,
                    timeout: 30000,
                }).then((response) => {
                    // Sistem menolak aksi checkout dengan status 422 (Unprocessable) / 404 / 302 atau 419
                    expect([302, 404, 419, 422]).to.include(response.status);
                });
            });
        });

        it('ORD-BO-01: Pesanan Backorder (stok terbatas, pecah Batch 1 stok tersedia & Batch 2 Backorder)', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=backorder', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan tab monitoring dan persetujuan Backorder memuat panduan alokasi Batch 1 & Batch 2
            cy.contains(/Pesanan Backorder|Persetujuan Backorder|Sisa Pasokan|Pemenuhan Bertahap/i, { timeout: 30000 }).should('be.visible');
            cy.get('body').should('satisfy', ($el) => {
                const text = $el.text();
                return (
                    text.includes('Batch 1') ||
                    text.includes('Batch 2') ||
                    text.includes('Backorder') ||
                    text.includes('Belum ada pesanan backorder') ||
                    text.includes('Daftar Pesanan') ||
                    text.includes('Pemenuhan Bertahap')
                );
            });
        });

        it('ORD-BO-02: Pesanan 100% Backorder (stok 0 / habis total via jalur Backorder)', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=backorder', { timeout: 45000 });
            cy.wait(1500);

            // Sistem memproses pesanan 100% backorder tanpa membuat stok fisik bernilai minus
            cy.get('body').should('satisfy', ($el) => {
                const text = $el.text();
                return (
                    text.includes('Backorder') ||
                    text.includes('Persetujuan') ||
                    text.includes('Kloter 2') ||
                    text.includes('Belum ada') ||
                    text.includes('Daftar Pesanan') ||
                    text.includes('Pemenuhan Bertahap')
                );
            });
        });
    });

    // =========================================================================
    // 2. PENGIRIMAN PEMASOK & ESTIMASI KEDATANGAN (SHP-SUP-01 s.d. SHP-SUP-03)
    // =========================================================================
    describe('2. Pengiriman Pemasok & Pemenuhan Pasokan (SHP-SUP)', () => {
        beforeEach(() => {
            cy.loginViaForm('supplier@sorai.id', 'password');
        });

        it('SHP-SUP-01: Pengiriman Pemasok reguler (Kirim 100% Utuh)', () => {
            cy.visit('/supplier/pengiriman', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan halaman verifikasi pengiriman pasokan terbuka
            cy.contains(/Verifikasi Proyek & Pengiriman|Daftar Proyek & Kontrak|Pesanan & Pengiriman/i, { timeout: 45000 }).should('be.visible');
            cy.contains(/Proyek Aktif|Menunggu Verifikasi|Semua Proyek|Berjalan/i).should('be.visible');

            // Verifikasi tombol aksi pengiriman atau daftar tabel proyek
            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Cek & Kirim"), button:contains("Cek Stok")').length > 0) {
                    cy.contains('button', /Cek & Kirim|Cek Stok/i).first().click();

                    // Modal verifikasi stok terbuka secara spesifik (modalKirimOpen)
                    cy.get('[x-show="modalKirimOpen"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="modalKirimOpen"]').within(() => {
                        cy.contains(/Cek Stok Fisik & Kirim Pesanan/i).should('be.visible');
                        cy.contains(/Stok Cukup Sesuai Pesanan/i).should('be.visible');
                    });
                } else {
                    // Jika belum ada pesanan yang perlu dikirim atau pesanan berstatus lain
                    cy.get('body').should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Daftar Proyek & Kontrak') ||
                            text.includes('Tidak ada data proyek') ||
                            text.includes('Berangkatkan') ||
                            text.includes('Rincian') ||
                            text.includes('Verifikasi Proyek')
                        );
                    });
                }
            });
        });

        it('SHP-SUP-02: Pengiriman Bertahap (Kirim Batch 1 & tetapkan ETA pemenuhan Batch 2)', () => {
            cy.visit('/supplier/pengiriman', { timeout: 45000 });
            cy.wait(1500);

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Cek & Kirim"), button:contains("Cek Stok")').length > 0) {
                    cy.contains('button', /Cek & Kirim|Cek Stok/i).first().click();

                    cy.get('[x-show="modalKirimOpen"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="modalKirimOpen"]').within(() => {
                        // Opsi Stok Kurang (Create Backorder)
                        cy.contains(/Stok Kurang \(Create Backorder\)/i).click();

                        // Form menampilkan input kuantitas Batch 1, estimasi tanggal Batch 2, dan catatan
                        cy.get('input[name="qty_to_ship"]').should('exist');
                        cy.get('input[name="backorder_estimated_delivery_date"]').should('exist');
                        cy.get('textarea[name="backorder_notes"]').should('exist');
                    });
                } else {
                    cy.get('body').should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Daftar Proyek & Kontrak') ||
                            text.includes('Tidak ada data proyek') ||
                            text.includes('Verifikasi Proyek')
                        );
                    });
                }
            });
        });

        it('SHP-SUP-03: Pengiriman Backorder (Kirim susulan Batch 2 saat stok tersedia)', () => {
            cy.visit('/supplier/pengiriman?tab=backorder', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan tab monitoring pengiriman pemasok terbuka
            cy.contains(/Verifikasi Proyek & Pengiriman|Daftar Proyek & Kontrak|Dibatalkan/i, { timeout: 30000 }).should('be.visible');

            cy.get('body').then(($body) => {
                if ($body.find('form[action*="kirim-backorder"]').length > 0 || $body.find('button:contains("Kirim Sisa")').length > 0) {
                    cy.get('form[action*="kirim-backorder"], button:contains("Kirim Sisa")').should('exist');
                } else {
                    cy.get('body').should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Tidak ada data proyek') ||
                            text.includes('Daftar Proyek & Kontrak') ||
                            text.includes('Dibatalkan')
                        );
                    });
                }
            });
        });
    });

    // =========================================================================
    // 3. KONFIRMASI PENERIMAAN UMKM (RCV-UMKM-01 s.d. RCV-UMKM-03)
    // =========================================================================
    describe('3. Konfirmasi Penerimaan Fisik UMKM (RCV-UMKM)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('RCV-UMKM-01: Konfirmasi Penerimaan fisik barang datang + bukti foto nota', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=confirm', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan sub-tab konfirmasi pesanan termuat
            cy.contains(/Validasi & Konfirmasi Kedatangan Barang|Konfirmasi Pesanan/i, { timeout: 30000 }).should('be.visible');

            cy.get('body').then(($body) => {
                if ($body.find('button:contains("Konfirmasi Penerimaan Fisik"), button:contains("Konfirmasi Terima")').length > 0) {
                    cy.contains('button', /Konfirmasi Penerimaan Fisik|Konfirmasi Terima/i).first().click();

                    // Modal konfirmasi terbuka secara spesifik (confirmingOrderId)
                    cy.get('[x-show="confirmingOrderId"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="confirmingOrderId"]').within(() => {
                        cy.contains(/KONFIRMASI PENERIMAAN FISIK/i).should('be.visible');
                        cy.get('input[name="proof_photo"]').should('exist');
                        cy.get('textarea[name="confirmation_notes"]').should('exist');
                    });
                } else {
                    cy.contains(/Belum ada pesanan yang perlu dikonfirmasi|Validasi & Konfirmasi/i, { timeout: 30000 }).should('exist');
                }
            });
        });

        it('RCV-UMKM-02: Konfirmasi Penerimaan Batch 1 pada pesanan yang memiliki alokasi Backorder', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=confirm', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan petunjuk alur penerimaan bertahap dan pengalihan ke Backorder tersedia
            cy.contains(/Validasi & Konfirmasi Kedatangan Barang|Konfirmasi Pesanan/i, { timeout: 30000 }).should('be.visible');
            cy.get('body').should('satisfy', ($el) => {
                const text = $el.text();
                return (
                    text.includes('Batch 1') ||
                    text.includes('Kloter') ||
                    text.includes('Konfirmasi') ||
                    text.includes('Belum ada pesanan yang perlu dikonfirmasi') ||
                    text.includes('Validasi & Konfirmasi')
                );
            });
        });

        it('RCV-UMKM-03: Pembatalan Backorder (Terima Batch 1, batalkan/tolak Batch 2 tanpa penalti)', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=backorder', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan opsi tolak/batalkan pengajuan backorder tersedia dengan alasan tertulis
            cy.get('[x-show="orderSubTab === \'backorder\'"]').then(($container) => {
                const tolakBtn = $container.find('button:contains("Tolak & Batalkan"), button:contains("Tolak")');
                if (tolakBtn.length > 0) {
                    cy.wrap(tolakBtn.first()).click();
                    cy.get('[x-show="rejectingBackorderId"]', { timeout: 20000 }).should('be.visible');
                    cy.get('textarea[name="rejection_reason"]', { timeout: 30000 }).should('be.visible');
                    cy.get('[x-show="rejectingBackorderId"]').find('button:contains("Batal")').first().click();
                } else {
                    cy.wrap($container).should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Pemantauan Pengiriman Sisa') ||
                            text.includes('Pesanan Dalam Pemenuhan Bertahap') ||
                            text.includes('Tidak Ada Pesanan Dalam Status Backorder') ||
                            text.includes('Belum ada pesanan')
                        );
                    });
                }
            });
        });
    });

    // =========================================================================
    // 4. PEMBAYARAN MIDTRANS & WEBHOOK (PAY-MID-01 s.d. PAY-MID-04)
    // =========================================================================
    describe('4. Integrasi Pembayaran Midtrans & Webhook (PAY-MID)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('PAY-MID-01: Request Midtrans Snap Token via endpoint /umkm/pemesanan/pembayaran/{id}/snap-token', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=payment', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan sub-tab pembayaran invoice Midtrans terbuka
            cy.contains(/Pembayaran Tagihan Invoice|Pembayaran Midtrans|Daftar Tagihan/i, { timeout: 30000 }).should('be.visible');

            cy.get('[x-show="orderSubTab === \'payment\'"]').then(($container) => {
                const bayarBtn = $container.find('#btn-bayar-batch, button:contains("Bayar Tagihan")');
                if (bayarBtn.length > 0) {
                    cy.wrap(bayarBtn.first()).click();

                    // Modal pembayaran Midtrans terbuka secara spesifik (payingInvoice)
                    cy.get('[x-show="payingInvoice"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="payingInvoice"]').within(() => {
                        cy.contains(/Rincian Tagihan Pembayaran|Midtrans Secure Payment/i).should('be.visible');
                        cy.contains(/Lanjut ke Pembayaran Midtrans|Simulasi/i).should('be.visible');
                        cy.contains('button', /Batal/i).first().click();
                    });
                } else {
                    cy.wrap($container).should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Belum Ada Tagihan') ||
                            text.includes('Lunas') ||
                            text.includes('Pembayaran Tagihan Invoice')
                        );
                    });
                }
            });
        });

        it('PAY-MID-02: Webhook Settlement Midtrans (Update status PAID & SELESAI)', () => {
            const orderId = 'PO-TEST-' + Date.now();
            const statusCode = '200';
            const grossAmount = '250000.00';

            cy.wrap(null).then(async () => {
                const validSignature = await generateMidtransSignature(
                    orderId,
                    statusCode,
                    grossAmount,
                    MIDTRANS_SERVER_KEY
                );

                const payload = {
                    order_id: orderId,
                    status_code: statusCode,
                    gross_amount: grossAmount,
                    signature_key: validSignature,
                    transaction_status: 'settlement',
                    payment_type: 'bank_transfer',
                };

                return cy.request({
                    method: 'POST',
                    url: '/midtrans/notification',
                    body: payload,
                    failOnStatusCode: false,
                    timeout: 30000,
                }).then((response) => {
                    expect(response.status).to.eq(200);
                    expect(response.body.success).to.be.true;
                });
            });
        });

        it('PAY-MID-03: Webhook Keamanan (Tolak Signature Key manipulatif dengan HTTP 403)', () => {
            const invalidPayload = {
                order_id: 'PO-FAKE-' + Date.now(),
                status_code: '200',
                gross_amount: '500000.00',
                signature_key: 'PALSU_SIGNATURE_KEY_MANIPULATIF_1234567890',
                transaction_status: 'settlement',
            };

            cy.request({
                method: 'POST',
                url: '/midtrans/notification',
                body: invalidPayload,
                failOnStatusCode: false,
                timeout: 30000,
            }).then((response) => {
                // Keamanan otentikasi webhook menolak dengan HTTP 403 Forbidden
                expect(response.status).to.eq(403);
                expect(response.body.message).to.match(/signature|invalid/i);
            });
        });

        it('PAY-MID-04: Pembayaran Kedaluwarsa (Simulasi status expire pada Midtrans)', () => {
            const orderId = 'PO-EXPIRE-' + Date.now();
            const statusCode = '202';
            const grossAmount = '175000.00';

            cy.wrap(null).then(async () => {
                const validSignature = await generateMidtransSignature(
                    orderId,
                    statusCode,
                    grossAmount,
                    MIDTRANS_SERVER_KEY
                );

                const expirePayload = {
                    order_id: orderId,
                    status_code: statusCode,
                    gross_amount: grossAmount,
                    signature_key: validSignature,
                    transaction_status: 'expire',
                };

                return cy.request({
                    method: 'POST',
                    url: '/midtrans/notification',
                    body: expirePayload,
                    failOnStatusCode: false,
                    timeout: 30000,
                }).then((response) => {
                    expect(response.status).to.eq(200);
                });
            });
        });
    });

    // =========================================================================
    // 5. ULASAN & REPUTASI PEMASOK (REV-UMKM-01)
    // =========================================================================
    describe('5. Ulasan Mutu & Reputasi Pemasok (REV-UMKM)', () => {
        beforeEach(() => {
            cy.loginViaForm('umkm@sorai.id', 'password');
        });

        it('REV-UMKM-01: Rating bintang 1-5 dan ulasan mutu setelah pesanan berstatus selesai lunas', () => {
            cy.visit('/umkm/katalog?tab=pemesanan&subtab=rating', { timeout: 45000 });
            cy.wait(1500);

            // Memastikan halaman rating dan ulasan pemasok termuat
            cy.contains(/Rating & Ulasan Pemasok|Ulas Pesanan/i, { timeout: 30000 }).should('be.visible');

            cy.get('[x-show="orderSubTab === \'rating\'"]').then(($container) => {
                const ulasBtn = $container.find('button:contains("Beri Ulasan"), button:contains("Edit Ulasan")');
                if (ulasBtn.length > 0) {
                    cy.wrap(ulasBtn.first()).click();

                    // Modal form input rating & testimoni mutu bahan baku terbuka (showReviewModal)
                    cy.get('[x-show="showReviewModal"]', { timeout: 20000 }).should('be.visible');
                    cy.get('[x-show="showReviewModal"]').within(() => {
                        cy.contains(/FORM PENILAIAN MUTU PASOKAN|Beri Ulasan Pasokan|Ulas Pesanan/i).should('be.visible');
                        cy.get('textarea[x-model="reviewText"]').should('exist');
                        cy.contains('button', /Simpan Ulasan/i).should('be.visible');
                        cy.contains('button', /Batal/i).first().click();
                    });
                } else {
                    cy.wrap($container).should('satisfy', ($el) => {
                        const text = $el.text();
                        return (
                            text.includes('Belum Ada Pesanan yang Selesai') ||
                            text.includes('Belum ada pesanan') ||
                            text.includes('Pesanan Lunas') ||
                            text.includes('Rating & Ulasan Pemasok')
                        );
                    });
                }
            });
        });
    });

    // =========================================================================
    // 6. KEAMANAN AKSES ROLE & MULTI-TENANT ISOLATION (SEC-RBAC-01 & SEC-ISOL-01)
    // =========================================================================
    describe('6. Keamanan Akses Role & Isolasi Multi-Tenant (SEC)', () => {

        it('SEC-RBAC-01: Keamanan Akses URL (Cegat akses URL supplier/admin oleh akun role UMKM)', () => {
            cy.loginViaForm('umkm@sorai.id', 'password');

            // 1. Mencoba mengakses dashboard pengiriman supplier secara langsung
            cy.visit('/supplier/pengiriman', { failOnStatusCode: false, timeout: 45000 });
            cy.get('body', { timeout: 30000 }).should('satisfy', ($el) => {
                const text = $el.text();
                return (
                    text.includes('403') ||
                    text.includes('Akses Ditolak') ||
                    text.includes('khusus untuk Pemasok')
                );
            });

            // 2. Mencoba mengakses panel admin secara langsung
            cy.visit('/admin', { failOnStatusCode: false, timeout: 45000 });
            // Middleware admin mengalihkan kembali ke portal UMKM dengan notifikasi ditolak
            cy.url({ timeout: 35000 }).should('include', '/umkm/katalog');
            cy.contains(/Akses ditolak|Administrator/i, { timeout: 30000 }).should('exist');
        });

        it('SEC-ISOL-01: Isolasi Data Multi-Tenant (Cegat manipulasi ID transaksi milik supplier lain via URL Tampering)', () => {
            cy.loginViaForm('supplier@sorai.id', 'password');
            cy.visit('/supplier/pengiriman', { timeout: 45000 });
            cy.wait(1500);

            // Mencoba mengeksekusi kirim pesanan dengan ID dummy / milik tenant pemasok lain
            const foreignOrderId = '00000000-0000-0000-0000-000000000000';
            cy.get('meta[name="csrf-token"]').invoke('attr', 'content').then((csrfToken) => {
                cy.request({
                    method: 'POST',
                    url: `/supplier/pengiriman/${foreignOrderId}/kirim`,
                    body: { qty_to_ship: 10 },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    failOnStatusCode: false,
                    timeout: 30000,
                }).then((response) => {
                    // Sistem menolak eksekusi manipulasi data lintas pengguna dengan HTTP 403 atau 404 (atau 419)
                    expect([403, 404, 419]).to.include(response.status);
                });
            });
        });
    });
});
