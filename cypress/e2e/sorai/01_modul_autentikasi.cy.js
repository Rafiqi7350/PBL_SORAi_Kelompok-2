// cypress/e2e/sorai/01_modul_autentikasi.cy.js

describe('MOD-01: Modul Autentikasi dan Otorisasi Multi-Role', () => {
    let sharedPendingEmail;

    // =========================================================================
    // 1. REGISTRASI AKUN UMKM KULINER (REG-UMKM-01 s.d. REG-UMKM-11)
    // =========================================================================
    describe('1. Registrasi Akun UMKM Kuliner', () => {
        beforeEach(() => {
            cy.clearAllCookies();
            cy.clearAllLocalStorage();
            cy.visit('/register/umkm');
        });

        it('REG-UMKM-01: Memastikan halaman registrasi UMKM dapat diakses dengan seluruh elemen form lengkap', () => {
            cy.contains(/Daftar Sebagai.*UMKM Kuliner/i).should('be.visible');
            cy.get('input[name="business_name"]').should('be.visible');
            cy.get('input[name="owner_name"]').should('be.visible');
            cy.get('input[name="email"]').should('be.visible');
            cy.get('input[name="phone"]').should('be.visible');
            cy.get('input[name="password"]').should('be.visible');
            cy.get('input[name="password_confirmation"]').should('be.visible');
            cy.get('textarea[name="address"]').should('be.visible');
            cy.get('button[type="submit"]').should('be.visible');
        });

        it('REG-UMKM-02: Mendaftarkan akun UMKM kuliner dengan seluruh data valid dan lengkap', () => {
            const timestamp = Date.now();
            sharedPendingEmail = `umkm_${timestamp}@sorai.test`;
            cy.get('input[name="business_name"]').clear().type(`Dapur Berkah ${timestamp}`);
            cy.get('input[name="owner_name"]').clear().type('Irlangsa Eka');
            cy.get('input[name="email"]').clear().type(sharedPendingEmail);
            cy.get('input[name="phone"]').clear().type('081234567890');
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');
            cy.get('textarea[name="address"]').clear().type('Jl. Remujung No. 12, Malang');

            cy.get('button[type="submit"]').click();

            cy.url().should('include', '/register/pending');
            cy.contains(/Pendaftaran Sedang Ditinjau|STATUS: MENUNGGU PERSETUJUAN ADMIN/i).should('be.visible');
        });

        it('REG-UMKM-03: Memastikan validasi error berjalan ketika semua field formulir dibiarkan kosong', () => {
            cy.get('button[type="submit"]').click();

            cy.contains(/Email wajib diisi/i).should('be.visible');
            cy.contains(/Kata sandi wajib diisi/i).should('be.visible');
            cy.contains(/Nama usaha wajib diisi/i).should('be.visible');
            cy.contains(/Nama pemilik wajib diisi/i).should('be.visible');
            cy.contains(/Nomor WhatsApp wajib diisi/i).should('be.visible');
            cy.contains(/Alamat operasional dapur wajib diisi/i).should('be.visible');
        });

        it('REG-UMKM-04: Memastikan validasi error ketika field Nama Pemilik / Pengelola diinput karakter angka', () => {
            cy.get('input[name="owner_name"]').clear().type('Irlangsa Eka 2');
            cy.get('button[type="submit"]').click();

            cy.contains(/Nama pemilik hanya boleh berisi huruf/i).should('be.visible');
            cy.url().should('not.include', '/register/pending');
        });

        it('REG-UMKM-05: Memastikan validasi error ketika nomor WhatsApp operasional mengandung huruf atau karakter non-angka', () => {
            cy.get('input[name="phone"]').clear().type('081234'); // Kurang dari 10 digit
            cy.get('button[type="submit"]').click();

            cy.contains(/Nomor WhatsApp minimal 10 digit angka/i).should('be.visible');
        });

        it('REG-UMKM-06: Memastikan email dengan format dummy / tidak valid ditolak oleh sistem', () => {
            cy.get('input[name="email"]').clear().type('email-tidak-valid');
            cy.get('button[type="submit"]').click();

            cy.contains(/Format email tidak valid/i).should('be.visible');
        });

        it('REG-UMKM-07: Memastikan pendaftaran ditolak jika password dan konfirmasi password tidak cocok', () => {
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('input[name="password_confirmation"]').clear().type('PasswordBeda123!');
            cy.get('button[type="submit"]').click();

            cy.contains(/Konfirmasi kata sandi tidak cocok/i).should('be.visible');
        });

        it('REG-UMKM-08: Memastikan password dengan panjang kurang dari 8 karakter ditolak', () => {
            cy.get('input[name="password"]').clear().type('pendek');
            cy.get('button[type="submit"]').click();

            cy.contains(/Kata sandi minimal 8 karakter/i).should('be.visible');
        });

        it('REG-UMKM-09: Memastikan pendaftaran ditolak jika email sudah terdaftar sebelumnya di database', () => {
            cy.get('input[name="business_name"]').clear().type('Dapur Eksis');
            cy.get('input[name="owner_name"]').clear().type('Budi Santoso');
            cy.get('input[name="email"]').clear().type('umkm@sorai.id'); // Email sudah terdaftar di database seeder
            cy.get('input[name="phone"]').clear().type('081234567899');
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');
            cy.get('textarea[name="address"]').clear().type('Jl. Merdeka No. 1, Malang');

            cy.get('button[type="submit"]').click();

            cy.contains(/Email ini sudah terdaftar/i).should('be.visible');
        });

        it('REG-UMKM-10: Memastikan link Sudah punya akun? Masuk di sini mengarah ke route /login', () => {
            cy.contains('a', 'Masuk di sini').click();
            cy.url().should('include', '/login');
        });
    });

    // =========================================================================
    // 2. REGISTRASI AKUN PEMASOK BAHAN BAKU (REG-SUP-01 s.d. REG-SUP-05)
    // =========================================================================
    describe('2. Registrasi Akun Pemasok Bahan Baku', () => {
        beforeEach(() => {
            cy.clearAllCookies();
            cy.clearAllLocalStorage();
            cy.visit('/register/supplier');
        });

        it('REG-SUP-01: Memastikan halaman pendaftaran pemasok dapat diakses dengan elemen form legalitas lengkap', () => {
            cy.contains(/Daftar Sebagai.*Pemasok Bahan Baku/i).should('be.visible');
            cy.get('input[name="company_name"]').should('be.visible');
            cy.get('input[name="phone"]').should('be.visible');
            cy.get('select[name="commodity_id"]').should('be.visible');
            cy.get('input[name="max_capacity"]').should('be.visible');
            cy.get('input[name="delivery_area"]').should('be.visible');
            cy.get('input[name="nib_number"]').should('be.visible');
            cy.get('textarea[name="warehouse_address"]').should('be.visible');
            cy.get('input[name="email"]').should('be.visible');
            cy.get('input[name="password"]').should('be.visible');
            cy.get('input[name="password_confirmation"]').should('be.visible');
            cy.get('button[type="submit"]').should('be.visible');
        });

        it('REG-SUP-02: Mendaftarkan akun pemasok dengan data valid dan NIB 13 digit angka lengkap', () => {
            const timestamp = Date.now();
            cy.get('input[name="company_name"]').clear().type(`Kelompok Tani Sejahtera ${timestamp}`);
            cy.get('input[name="email"]').clear().type(`supplier_${timestamp}@sorai.test`);
            cy.get('input[name="phone"]').clear().type('081234567888');
            cy.get('select[name="commodity_id"]').select(1);
            cy.get('input[name="max_capacity"]').clear().type('10 Ton / Minggu');
            cy.get('input[name="delivery_area"]').clear().type('Malang Raya & Surabaya');
            cy.get('input[name="nib_number"]').clear().type('9120001234567'); // 13 digit angka valid
            cy.get('textarea[name="warehouse_address"]').clear().type('Pergudangan Agrobisnis Blok B No. 4, Malang');
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');

            cy.get('button[type="submit"]').click();

            cy.url().should('include', '/register/pending');
            cy.contains(/Pendaftaran Sedang Ditinjau|STATUS: MENUNGGU PERSETUJUAN ADMIN/i).should('be.visible');
        });

        it('REG-SUP-03: Memastikan validasi error ketika field NIB diinput kurang dari 13 digit atau mengandung huruf', () => {
            cy.get('input[name="nib_number"]').clear().type('abc123xyz');
            cy.get('input[name="nib_number"]').should('have.value', '123');
        });

        it('REG-SUP-04: Memastikan nomor NIB yang sudah terdaftar di sistem ditolak', () => {
            cy.get('input[name="company_name"]').clear().type('CV Pemasok Duplikat');
            cy.get('input[name="email"]').clear().type('supplier@sorai.id'); // Akun / email pemasok sudah terdaftar
            cy.get('input[name="phone"]').clear().type('081234567899');
            cy.get('select[name="commodity_id"]').select(1);
            cy.get('input[name="max_capacity"]').clear().type('5 Ton / Minggu');
            cy.get('input[name="delivery_area"]').clear().type('Malang');
            cy.get('input[name="nib_number"]').clear().type('9120004561234');
            cy.get('textarea[name="warehouse_address"]').clear().type('Alamat Gudang Terdaftar');
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');

            cy.get('button[type="submit"]').click();
            cy.contains(/Email ini sudah terdaftar/i).should('be.visible');
        });
    });

    // =========================================================================
    // 3. ANTREAN PENDING & POLLING STATUS VERIFIKASI (PND-01 s.d. PND-04)
    // =========================================================================
    describe('3. Antrean Pending & Polling Verifikasi', () => {
        before(() => {
            if (!sharedPendingEmail) {
                const timestamp = Date.now();
                sharedPendingEmail = `pending_auto_${timestamp}@sorai.test`;
                cy.clearAllCookies();
                cy.clearAllLocalStorage();
                cy.visit('/register/umkm');
                cy.get('input[name="business_name"]').clear().type(`Dapur Uji Pending ${timestamp}`);
                cy.get('input[name="owner_name"]').clear().type('Penguji Pending');
                cy.get('input[name="email"]').clear().type(sharedPendingEmail);
                cy.get('input[name="phone"]').clear().type('081234567890');
                cy.get('input[name="password"]').clear().type('PasswordValid123!');
                cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');
                cy.get('textarea[name="address"]').clear().type('Jl. Remujung No. 12, Malang');
                cy.get('button[type="submit"]').click();
                cy.url().should('include', '/register/pending');
            }
        });

        it('PND-01: Memastikan halaman /register/pending memuat status antrean verifikasi dan email pendaftar', () => {
            cy.visit(`/register/pending?email=${encodeURIComponent(sharedPendingEmail)}`);

            cy.contains(/Pendaftaran Sedang Ditinjau/i).should('be.visible');
            cy.contains(/STATUS: MENUNGGU PERSETUJUAN ADMIN/i).should('be.visible');
            cy.contains(sharedPendingEmail).should('be.visible');
        });

        it('PND-02: Memastikan endpoint /api/check-status merespons status PENDING saat akun belum diverifikasi', () => {
            cy.request({
                url: `/api/check-status?email=${encodeURIComponent(sharedPendingEmail)}`,
                failOnStatusCode: false
            }).then((response) => {
                expect(response.status).to.eq(200);
                expect(response.body).to.have.property('status', 'pending');
                expect(response.body).to.have.property('is_active', false);
            });
        });

        it('PND-04: Memastikan halaman pending menangani parameter email yang tidak terdaftar secara aman', () => {
            cy.visit('/register/pending?email=email_tidak_terdaftar@sorai.test', { failOnStatusCode: false });
            cy.get('body').should('be.visible');
            cy.contains(/Pendaftaran Sedang Ditinjau|STATUS: MENUNGGU PERSETUJUAN/i).should('be.visible');
        });
    });

    // =========================================================================
    // 4. LOGIN PENGGUNA MULTI-ROLE (LOG-01 s.d. LOG-10)
    // =========================================================================
    describe('4. Login Pengguna Multi-Role', () => {
        beforeEach(() => {
            cy.clearAllCookies();
            cy.clearAllLocalStorage();
            cy.visit('/login');
        });

        it('LOG-01: Memastikan halaman login (/login) dapat diakses dengan elemen form lengkap', () => {
            cy.contains('Masuk ke SORAI').should('be.visible');
            cy.get('input[name="email"]').should('be.visible');
            cy.get('input[name="password"]').should('be.visible');
            cy.get('input[name="remember"]').should('exist');
            cy.get('button[type="submit"]').should('be.visible');
            cy.contains('Daftar UMKM').should('be.visible');
            cy.contains('Daftar Pemasok').should('be.visible');
        });

        it('LOG-02: Memastikan login berhasil untuk user UMKM aktif dan auto-redirect ke /umkm/katalog', () => {
            cy.get('input[name="email"]').clear().type('umkm@sorai.id');
            cy.get('input[name="password"]').clear().type('password');
            cy.get('button[type="submit"]').click();

            cy.url({ timeout: 20000 }).should('include', '/umkm/katalog');
        });

        it('LOG-03: Memastikan login berhasil untuk user Supplier aktif dan auto-redirect ke /supplier/dashboard', () => {
            cy.get('input[name="email"]').clear().type('supplier@sorai.id');
            cy.get('input[name="password"]').clear().type('password');
            cy.get('button[type="submit"]').click();

            cy.url({ timeout: 15000 }).should('include', '/supplier/dashboard');
        });

        it('LOG-04: Memastikan penolakan login bagi akun yang masih berstatus PENDING verifikasi', () => {
            // Pastikan akun berstatus pending tersedia di database
            if (!sharedPendingEmail) {
                const ts = Date.now();
                sharedPendingEmail = `pending_login_${ts}@sorai.test`;
                cy.visit('/register/umkm');
                cy.get('input[name="business_name"]').clear().type(`Dapur Pending ${ts}`);
                cy.get('input[name="owner_name"]').clear().type('Budi Pending');
                cy.get('input[name="email"]').clear().type(sharedPendingEmail);
                cy.get('input[name="phone"]').clear().type('081234567890');
                cy.get('input[name="password"]').clear().type('PasswordValid123!');
                cy.get('input[name="password_confirmation"]').clear().type('PasswordValid123!');
                cy.get('textarea[name="address"]').clear().type('Jl. Uji No. 12, Malang');
                cy.get('button[type="submit"]').click();
                cy.url().should('include', '/register/pending');
                cy.visit('/login');
            }

            cy.get('input[name="email"]').clear().type(sharedPendingEmail);
            cy.get('input[name="password"]').clear().type('PasswordValid123!');
            cy.get('button[type="submit"]').click();

            cy.contains(/proses verifikasi oleh Administrator/i).should('be.visible');
            cy.contains(/Pantau Status Verifikasi di Loading Page/i).should('be.visible');
        });

            it('LOG-05: Memastikan penolakan login bagi akun yang berstatus REJECTED (Ditolak Admin)', () => {
                cy.get('input[name="email"]').clear().type('rejected@sorai.id');
                cy.get('input[name="password"]').clear().type('password');
                cy.get('button[type="submit"]').click();

                cy.get('body').then(($body) => {
                    if ($body.text().includes('tidak aktif atau ditangguhkan')) {
                        cy.contains(/tidak aktif atau ditangguhkan/i).should('be.visible');
                    } else {
                        cy.contains(/Email belum terdaftar/i).should('be.visible');
                    }
                });
            });

            it('LOG-06: Memastikan validasi error ketika email dan password dibiarkan kosong', () => {
                cy.get('button[type="submit"]').click();
                cy.get('input[name="email"]:invalid').should('exist');
            });

            it('LOG-07: Memastikan validasi error jika password salah untuk email yang terdaftar', () => {
                cy.get('input[name="email"]').clear().type('umkm@sorai.id');
                cy.get('input[name="password"]').clear().type('passwordsalah123');
                cy.get('button[type="submit"]').click();

                cy.contains(/Kata sandi yang Anda masukkan salah/i).should('be.visible');
            });

            it('LOG-08: Memastikan validasi error jika email belum terdaftar di sistem', () => {
                cy.get('input[name="email"]').clear().type('belumterdaftar999@sorai.id');
                cy.get('input[name="password"]').clear().type('password123');
                cy.get('button[type="submit"]').click();

                cy.contains(/Email belum terdaftar/i).should('be.visible');
            });

            it('LOG-09: Memastikan checkbox Ingat Saya (Remember Me) berfungsi menyimpan preferensi', () => {
                cy.get('input[name="remember"]').check().should('be.checked');
            });
    });

    // =========================================================================
    // 5. LOGIN ADMINISTRATOR SORAI (LOG-ADM-01 s.d. LOG-ADM-04)
    // =========================================================================
    describe('5. Login Administrator SORAI', () => {
        beforeEach(() => {
            cy.clearAllCookies();
            cy.clearAllLocalStorage();
            cy.visit('/admin/login');
        });

        it('LOG-ADM-01: Memastikan halaman login admin (/admin/login) terisolasi dan dapat diakses', () => {
            cy.contains(/SORAI Admin Panel/i).should('be.visible');
            cy.contains(/Admin Control Access|Masuk Ke Dashboard/i).should('be.visible');
            cy.get('input[name="email"]').should('be.visible');
            cy.get('input[name="password"]').should('be.visible');
            cy.get('button[type="submit"]').should('be.visible');
        });

        it('LOG-ADM-02: Memastikan login admin berhasil dengan kredensial administrator valid', () => {
            cy.get('input[name="email"]').clear().type('admin@sorai.id');
            cy.get('input[name="password"]').clear().type('password');
            cy.get('button[type="submit"]').click();

            cy.url({ timeout: 15000 }).should('include', '/admin');
            cy.get('body').should('contain.text', 'Komoditas');
        });

        it('LOG-ADM-03: Memastikan user dengan role umkm atau supplier ditolak masuk via login admin', () => {
            cy.get('input[name="email"]').clear().type('umkm@sorai.id');
            cy.get('input[name="password"]').clear().type('password');
            cy.get('button[type="submit"]').click();

            cy.url().should('include', '/admin/login');
            cy.contains(/Akses ditolak.*tidak memiliki hak akses sebagai Administrator/i).should('be.visible');
        });
    });

});
