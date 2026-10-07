<div x-data="actionConfirmModal()"
     @open-confirm-modal.window="open($event.detail)"
     @keydown.escape.window="cancel()"
     x-cloak>

    <!-- Modal Backdrop & Container -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-midnight-ink/80 backdrop-blur-sm">

        <!-- Card Dialog Box (Neo-Brutalist Aesthetic) -->
        <div @click.away="cancel()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             class="bg-plaster-white rounded-card p-6 sm:p-7 max-w-md w-full border-4 border-midnight-ink shadow-[8px_8px_0px_0px_rgba(30,34,45,1)] relative text-center space-y-4">

            <!-- Tombol Tutup (X) -->
            <button type="button"
                    @click="cancel()"
                    class="absolute top-4 right-4 text-midnight-ink/50 hover:text-midnight-ink font-bold text-2xl leading-none cursor-pointer"
                    title="Tutup">
                &times;
            </button>

            <!-- Ikon Badge Sesuai Tindakan -->
            <div class="w-16 h-16 rounded-2xl border-3 border-midnight-ink flex items-center justify-center mx-auto shadow-sm"
                 :class="{
                     'bg-rose-100 text-rose-600': modalType === 'delete' || modalType === 'logout',
                     'bg-amber-100 text-amber-700': modalType === 'edit' || modalType === 'warning',
                     'bg-voltage-lime text-midnight-ink': modalType === 'create',
                     'bg-emerald-100 text-emerald-700': modalType === 'verify'
                 }">
                <!-- Icon Delete -->
                <template x-if="modalType === 'delete'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </template>
                <!-- Icon Logout -->
                <template x-if="modalType === 'logout'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </template>
                <!-- Icon Edit -->
                <template x-if="modalType === 'edit'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </template>
                <!-- Icon Create / Tambah -->
                <template x-if="modalType === 'create'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </template>
                <!-- Icon Verify -->
                <template x-if="modalType === 'verify'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>
            </div>

            <!-- Header Badge & Informasi Konfirmasi -->
            <div class="space-y-1">
                <span class="font-mono text-[10px] font-extrabold uppercase px-3 py-1 rounded-pill border-2 border-midnight-ink inline-block"
                      :class="{
                          'bg-rose-200 text-rose-950': modalType === 'delete' || modalType === 'logout',
                          'bg-amber-200 text-amber-950': modalType === 'edit' || modalType === 'warning',
                          'bg-voltage-lime text-midnight-ink': modalType === 'create',
                          'bg-emerald-200 text-emerald-950': modalType === 'verify'
                      }"
                      x-text="badgeText">
                </span>
                <h3 class="font-display font-extrabold text-xl sm:text-2xl text-midnight-ink uppercase pt-1" x-text="title"></h3>
                <p class="text-xs sm:text-sm font-semibold text-midnight-ink/75 leading-relaxed pt-1" x-text="message"></p>
            </div>

            <!-- Tombol Aksi (Batal & Lanjutkan) -->
            <div class="flex flex-col sm:flex-row items-center gap-3 pt-3">
                <button type="button"
                        @click="cancel()"
                        class="w-full sm:w-1/2 py-3 px-4 rounded-pill border-2 border-midnight-ink bg-gray-100 hover:bg-gray-200 text-midnight-ink font-bold text-xs sm:text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        @click="confirm()"
                        class="w-full sm:w-1/2 py-3 px-4 rounded-pill border-2 border-midnight-ink font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition cursor-pointer"
                        :class="{
                            'bg-rose-600 hover:bg-rose-700 text-white': modalType === 'delete' || modalType === 'logout',
                            'bg-sun-orange hover:bg-amber-600 text-midnight-ink': modalType === 'edit' || modalType === 'create',
                            'bg-emerald-500 hover:bg-emerald-600 text-white': modalType === 'verify'
                        }"
                        x-text="confirmText">
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function actionConfirmModal() {
        return {
            isOpen: false,
            modalType: 'delete',
            badgeText: 'PERINGATAN',
            title: 'Konfirmasi Tindakan',
            message: 'Apakah Anda yakin ingin melanjutkan?',
            confirmText: 'Ya, Lanjutkan',
            targetForm: null,
            onConfirmCallback: null,

            open(detail) {
                this.modalType = detail.type || 'delete';
                this.badgeText = detail.badge || (
                    this.modalType === 'delete' ? 'PERINGATAN HAPUS' :
                    this.modalType === 'logout' ? 'KONFIRMASI KELUAR' :
                    this.modalType === 'edit' ? 'KONFIRMASI PERUBAHAN' :
                    this.modalType === 'verify' ? 'VERIFIKASI AKUN' :
                    'KONFIRMASI PENAMBAHAN'
                );
                this.title = detail.title || 'Konfirmasi Tindakan';
                this.message = detail.message || 'Apakah Anda yakin ingin melanjutkan?';
                this.confirmText = detail.confirmText || (
                    this.modalType === 'delete' ? 'Ya, Hapus Data' :
                    this.modalType === 'logout' ? 'Ya, Keluar Akun' :
                    this.modalType === 'edit' ? 'Ya, Simpan Perubahan' :
                    this.modalType === 'verify' ? 'Ya, Verifikasi' :
                    'Ya, Simpan'
                );
                this.targetForm = detail.form || null;
                this.onConfirmCallback = detail.onConfirm || null;
                this.isOpen = true;
            },

            cancel() {
                this.isOpen = false;
                this.targetForm = null;
                this.onConfirmCallback = null;
            },

            confirm() {
                this.isOpen = false;
                if (typeof this.onConfirmCallback === 'function') {
                    this.onConfirmCallback();
                } else if (this.targetForm) {
                    this.targetForm.dataset.confirmed = 'true';
                    if (typeof this.targetForm.requestSubmit === 'function') {
                        this.targetForm.requestSubmit();
                    } else {
                        this.targetForm.submit();
                    }
                }
                this.targetForm = null;
                this.onConfirmCallback = null;
            }
        };
    }

    // Interceptor Global untuk Form Modifikasi Data & Logout
    (function() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || form.dataset.confirmed === 'true' || form.dataset.noConfirm === 'true') {
                return;
            }

            // Validasi HTML5 bawaan (jika ada input required yang kosong, jangan cegah pesan bawaan browser)
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            const action = (form.getAttribute('action') || '').toLowerCase();
            const methodInput = form.querySelector('input[name="_method"]');
            const spoofedMethod = methodInput ? methodInput.value.toUpperCase() : '';
            const formMethod = (form.getAttribute('method') || 'GET').toUpperCase();

            // Lewatkan form login atau register agar proses input kredensial berjalan langsung
            if (action.includes('/login') || action.includes('/register')) {
                return;
            }

            // 1. Aksi Keluar (Logout)
            if (action.includes('logout') || form.dataset.confirmType === 'logout') {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        type: 'logout',
                        badge: 'KONFIRMASI KELUAR',
                        title: 'Apakah Anda Yakin Ingin Keluar?',
                        message: 'Sesi akun Anda saat ini akan diakhiri. Anda perlu memasukkan email & kata sandi kembali untuk masuk ke sistem.',
                        confirmText: 'Ya, Keluar Akun',
                        form: form
                    }
                }));
                return;
            }

            // 2. Aksi Hapus (Delete)
            if (spoofedMethod === 'DELETE' || form.dataset.confirmType === 'delete') {
                e.preventDefault();
                const customTitle = form.dataset.confirmTitle || 'Konfirmasi Hapus Data';
                const customMsg = form.dataset.confirmMessage || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.';
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        type: 'delete',
                        badge: 'PERINGATAN HAPUS',
                        title: customTitle,
                        message: customMsg,
                        confirmText: 'Ya, Hapus Data',
                        form: form
                    }
                }));
                return;
            }

            // 3. Aksi Verifikasi / Moderasi Pengguna
            if (action.includes('/users/') && action.includes('/status')) {
                e.preventDefault();
                const statusInput = form.querySelector('input[name="status"]');
                const nextStatus = statusInput ? statusInput.value.toUpperCase() : '';
                const isVerifying = nextStatus === 'AKTIF' || nextStatus === 'ACTIVE';
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        type: isVerifying ? 'verify' : 'warning',
                        badge: isVerifying ? 'VERIFIKASI AKUN' : 'MODERASI AKUN',
                        title: isVerifying ? 'Verifikasi & Aktifkan Akun?' : 'Ubah Status Akun Pengguna?',
                        message: isVerifying 
                            ? 'Apakah Anda yakin ingin memverifikasi akun pengguna ini? Pengguna akan segera dapat mengakses sistem.'
                            : 'Apakah Anda yakin ingin mengubah status akun pengguna ini? Akses ke sistem akan ditangguhkan.',
                        confirmText: isVerifying ? 'Ya, Aktifkan Akun' : 'Ya, Ubah Status',
                        form: form
                    }
                }));
                return;
            }

            // 4. Aksi Edit / Simpan Perubahan (PUT / PATCH)
            if (spoofedMethod === 'PUT' || spoofedMethod === 'PATCH' || form.dataset.confirmType === 'edit') {
                e.preventDefault();
                const customTitle = form.dataset.confirmTitle || 'Konfirmasi Simpan Perubahan';
                const customMsg = form.dataset.confirmMessage || 'Apakah Anda yakin ingin menyimpan perubahan pada data ini?';
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        type: 'edit',
                        badge: 'KONFIRMASI PERUBAHAN',
                        title: customTitle,
                        message: customMsg,
                        confirmText: 'Ya, Simpan Perubahan',
                        form: form
                    }
                }));
                return;
            }

            // 5. Aksi Tambah Data (POST)
            if (formMethod === 'POST') {
                e.preventDefault();
                const customTitle = form.dataset.confirmTitle || 'Konfirmasi Tambah Data';
                const customMsg = form.dataset.confirmMessage || 'Apakah Anda yakin ingin menambahkan data baru ini ke dalam sistem?';
                window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                    detail: {
                        type: 'create',
                        badge: 'KONFIRMASI PENAMBAHAN',
                        title: customTitle,
                        message: customMsg,
                        confirmText: 'Ya, Tambahkan Data',
                        form: form
                    }
                }));
                return;
            }
        }, true);
    })();
</script>
