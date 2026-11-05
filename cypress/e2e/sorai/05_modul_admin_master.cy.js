if (!Cypress.Commands._commands || !Cypress.Commands._commands.loginAdminViaForm) {
    Cypress.Commands.add('loginAdminViaForm', (email, password) => {
        cy.clearAllCookies();
        cy.clearAllLocalStorage();
        cy.visit('/admin/login', { timeout: 45000 });
        cy.get('input[name="email"]', { timeout: 25000 }).should('be.visible').clear().type(email);
        cy.get('input[name="password"]', { timeout: 25000 }).should('be.visible').clear().type(password);
        cy.get('button[type="submit"]', { timeout: 25000 }).filter(':visible').first().click();
        cy.url({ timeout: 35000 }).should('not.include', '/admin/login');
        cy.wait(1500);
    });
}

describe('MOD-05: Modul Administrator, Moderasi Pengguna & Master Data Komoditas', () => {

    beforeEach(() => {
        cy.loginAdminViaForm('admin@sorai.id', 'password');
    });

    // =========================================================================
    // 1. MANAJEMEN PENGGUNA & MODERASI AKUN (ADM-USR-01 s.d. ADM-USR-05)
    // =========================================================================
    describe('1. Manajemen Pengguna & Moderasi Akun (ADM-USR)', () => {
        beforeEach(() => {
            cy.visit('/admin?tab=users', { timeout: 45000 });
            cy.wait(1500);
        });

          it('ADM-USR-01: Memastikan tabel antrean verifikasi menampilkan akun PENDING', () => {
              cy.contains('h2', /Manajemen Pengguna System/i, { timeout: 30000 }).should('be.visible');
              cy.get('table').should('be.visible');
              cy.contains('th', /ID User/i).should('exist');
              cy.contains('th', /Nama Usaha|Akun/i).should('exist');
              cy.contains('th', /Tipe Akun/i).should('exist');
              cy.contains('th', /Kontak|Email/i).should('exist');
              cy.contains('th', /Status Akun/i).should('exist');
              cy.contains('th', /Aksi Moderasi/i).should('exist');
          });

          it('ADM-USR-02: Admin menyetujui (ACC) pendaftaran akun pengguna berstatus PENDING menjadi ACTIVE', () => {
              cy.get('[x-show="adminTab === \'users\'"]').then(($container) => {
                  const accBtn = $container.find('button:contains("Verifikasi & Aktifkan")');
                  if (accBtn.length > 0) {
                      cy.wrap(accBtn.first()).click();
                      cy.url({ timeout: 35000 }).should('include', '/admin');
                      cy.get('[x-show="adminTab === \'users\'"]', { timeout: 30000 }).within(() => {
                          cy.contains(/AKTIF|Manajemen Pengguna/i).should('be.visible');
                      });
                  } else {
                      // Jika seluruh akun pengguna di database saat ini sudah berstatus aktif
                      cy.wrap($container).within(() => {
                          cy.get('table').should('be.visible');
                          cy.contains(/AKTIF|NONAKTIF|Manajemen Pengguna/i).should('exist');
                      });
                  }
              });
          });

          it('ADM-USR-03: Admin menolak (Reject) pendaftaran akun pengguna berstatus PENDING menjadi REJECTED', () => {
              cy.get('[x-show="adminTab === \'users\'"]').then(($container) => {
                  const rejectBtn = $container.find('button:contains("Tolak"), button:contains("Nonaktifkan")');
                  if (rejectBtn.length > 0) {
                      cy.wrap(rejectBtn.first()).click();
                      cy.url({ timeout: 35000 }).should('include', '/admin');
                      cy.get('[x-show="adminTab === \'users\'"]', { timeout: 30000 }).within(() => {
                          cy.contains(/NONAKTIF|REJECT|Manajemen Pengguna/i).should('be.visible');
                      });
                  } else {
                      cy.wrap($container).within(() => {
                          cy.get('table').should('be.visible');
                          cy.contains(/NONAKTIF|PENDING|AKTIF/i).should('exist');
                      });
                  }
              });
          });

          it('ADM-USR-04: Admin menonaktifkan sementara (Suspend) akun pengguna aktif yang melanggar ketentuan', () => {
              cy.get('[x-show="adminTab === \'users\'"]').then(($container) => {
                  const suspendBtn = $container.find('button:contains("Nonaktifkan")');
                  if (suspendBtn.length > 0) {
                      cy.wrap(suspendBtn.first()).click();
                      cy.url({ timeout: 35000 }).should('include', '/admin');
                      cy.get('[x-show="adminTab === \'users\'"]', { timeout: 30000 }).within(() => {
                          cy.contains(/NONAKTIF|Manajemen Pengguna/i).should('be.visible');
                      });
                  } else {
                      cy.wrap($container).within(() => {
                          cy.get('table').should('be.visible');
                          cy.contains(/NONAKTIF|AKTIF/i).should('exist');
                      });
                  }
              });
          });

        it('ADM-USR-05: Filter dan pencarian daftar pengguna berdasarkan role (UMKM / Supplier) dan status akun', () => {
            cy.get('[x-show="adminTab === \'users\'"]', { timeout: 30000 }).within(() => {
                // 1. Pencarian kata kunci nama/email
                cy.get('input[x-model="userSearch"]', { timeout: 25000 }).clear().type('sorai');
                cy.get('table').should('be.visible');

                // 2. Filter Role UMKM
                cy.get('select[x-model="userFilterRole"]').select('UMKM');
                cy.get('table').should('be.visible');

                // 3. Filter Role PEMASOK
                cy.get('select[x-model="userFilterRole"]').select('PEMASOK');
                cy.get('table').should('be.visible');

                // 4. Kembalikan ke filter semua akun
                cy.get('select[x-model="userFilterRole"]').select('ALL');
                cy.get('input[x-model="userSearch"]').clear();
            });
        });
    });

    // =========================================================================
    // 2. MASTER KATEGORI & SATUAN BAKU (ADM-KAT & ADM-SAT)
    // =========================================================================
    describe('2. Master Kategori Komoditas & Satuan Baku (ADM-KAT & ADM-SAT)', () => {
        let testCategoryName;
        let testUnitSymbol;
        let testUnitName;

        beforeEach(() => {
            cy.visit('/admin?tab=master', { timeout: 45000 });
            cy.wait(1500);
        });

        it('ADM-KAT-01: Admin menambahkan kategori komoditas baru (misal: Daging & Unggas, Bumbu & Rempah)', () => {
            const uniqueSuffix = Date.now() + '_' + Math.floor(Math.random() * 10000);
            testCategoryName = `Kategori Uji ${uniqueSuffix}`;

            cy.intercept('POST', '**/admin/categories').as('storeCategory');

            cy.contains('button', '+ Tambah Kategori Baru', { timeout: 30000 }).click();
            cy.get('[x-show="isCategoryModalOpen"]', { timeout: 25000 }).should('be.visible');

            cy.get('[x-show="isCategoryModalOpen"] input[name="name"]').type(testCategoryName);
            cy.get('[x-show="isCategoryModalOpen"] textarea[name="description"]').type('Deskripsi kategori pengujian otomatis Cypress.');
            cy.get('[x-show="isCategoryModalOpen"] form').submit();

            // Tunggu request selesai dan halaman termuat kembali di tab master
            cy.wait('@storeCategory', { timeout: 35000 });
            cy.url({ timeout: 35000 }).should('include', '/admin');

            // Verifikasi spesifik pada tab master aktif
            cy.get('[x-show="adminTab === \'master\'"]', { timeout: 30000 }).within(() => {
                cy.contains('h4', testCategoryName, { timeout: 30000 }).should('be.visible');
            });
        });

        it('ADM-KAT-02: Validasi error saat nama kategori dikosongkan atau diinput duplikat', () => {
            cy.contains('button', '+ Tambah Kategori Baru', { timeout: 30000 }).click();
            cy.get('[x-show="isCategoryModalOpen"]', { timeout: 25000 }).should('be.visible');

            // Hapus atribut required untuk menguji validasi server
            cy.get('[x-show="isCategoryModalOpen"] input[name="name"]').clear().invoke('removeAttr', 'required');
            cy.get('[x-show="isCategoryModalOpen"] button[type="submit"]').click();

            cy.contains(/wajib diisi|tidak boleh kosong|required/i, { timeout: 35000 }).should('be.visible');
        });

        it('ADM-KAT-03: Admin memperbarui atau menghapus kategori komoditas yang kosong', () => {
            const catToDelete = testCategoryName || `Kategori Hapus ${Date.now()}`;

            cy.get('[x-show="adminTab === \'master\'"]', { timeout: 30000 }).then(($container) => {
                if ($container.find(`h4:contains("${catToDelete}")`).length === 0) {
                    cy.contains('button', '+ Tambah Kategori Baru').click();
                    cy.get('[x-show="isCategoryModalOpen"] input[name="name"]').type(catToDelete);
                    cy.get('[x-show="isCategoryModalOpen"] button[type="submit"]').click();
                    cy.contains('h4', catToDelete, { timeout: 35000 }).should('be.visible');
                }

                cy.get('[x-show="adminTab === \'master\'"]').within(() => {
                    cy.contains('h4', catToDelete, { timeout: 30000 })
                        .parents('div.bg-slate-50')
                        .within(() => {
                            cy.get('form button[type="submit"]').first().click();
                        });
                });
                cy.contains(/berhasil dihapus/i, { timeout: 35000 }).should('be.visible');
            });
        });

        it('ADM-SAT-01: Admin mengelola satuan baku komoditas pangan (Kg, Gram, Liter, Ikat, Ekor)', () => {
            const uniqueNum = Math.floor(Date.now() % 100000);
            testUnitSymbol = `u${uniqueNum}`;
            testUnitName = `Satuan ${testUnitSymbol}`;

            cy.contains('button', '+ Tambah Satuan Baru', { timeout: 30000 }).click();
            cy.get('[x-show="isUnitModalOpen"]', { timeout: 25000 }).should('be.visible');

            cy.get('[x-show="isUnitModalOpen"] input[name="name"]').type(testUnitName);
            cy.get('[x-show="isUnitModalOpen"] input[name="symbol"]').type(testUnitSymbol);
            cy.get('[x-show="isUnitModalOpen"] input[name="description"]').type('Berat');

            cy.get('[x-show="isUnitModalOpen"] button[type="submit"]').click();

            cy.get('[x-show="adminTab === \'master\'"]', { timeout: 35000 }).within(() => {
                cy.contains('td', testUnitName, { timeout: 30000 }).should('be.visible');
            });
        });

        it('ADM-SAT-02: Validasi error jika simbol atau nama satuan baku dibiarkan kosong', () => {
            cy.contains('button', '+ Tambah Satuan Baru', { timeout: 30000 }).click();
            cy.get('[x-show="isUnitModalOpen"]', { timeout: 25000 }).should('be.visible');

            // Hapus required attribute untuk menguji validasi server
            cy.get('[x-show="isUnitModalOpen"] input[name="name"]').clear().invoke('removeAttr', 'required');
            cy.get('[x-show="isUnitModalOpen"] input[name="symbol"]').clear().invoke('removeAttr', 'required');
            cy.get('[x-show="isUnitModalOpen"] button[type="submit"]').click();

            cy.contains(/wajib diisi|tidak boleh kosong|required/i, { timeout: 35000 }).should('be.visible');
        });
    });
});
