@php
    $logoutActionRoute = $logoutRoute ?? (auth()->user()?->role === 'admin' ? route('admin.logout') : route('logout'));
@endphp

<div id="logout-modal-root" x-data="{
         showLogoutModal: false,
         isSubmitting: false,
         targetForm: null,
         openLogoutModal(detail = {}) {
             this.isSubmitting = false;
             this.targetForm = detail?.form || null;
             this.showLogoutModal = true;
         },
         closeLogoutModal() {
             if (!this.isSubmitting) {
                 this.showLogoutModal = false;
                 this.targetForm = null;
             }
         },
         handleConfirmSubmit(event) {
             if (this.isSubmitting) {
                 event.preventDefault();
                 return;
             }
             this.isSubmitting = true;
             if (this.targetForm) {
                 event.preventDefault();
                 this.targetForm.dataset.confirmed = 'true';
                 this.targetForm.submit();
             }
         }
     }" @open-logout-modal.window="openLogoutModal($event.detail)" @keydown.escape.window="closeLogoutModal()" x-cloak>

    <!-- Modal Backdrop & Dialog -->
    <div x-show="showLogoutModal" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click.self="closeLogoutModal()" role="dialog" aria-modal="true"
        aria-labelledby="logout-modal-title"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/55 backdrop-blur-sm">

        <!-- Card Dialog -->
        <div x-show="showLogoutModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="bg-white rounded-3xl max-w-[340px] sm:max-w-[360px] w-full px-6 py-7 sm:px-7 sm:py-8 text-center shadow-2xl border border-slate-100 relative">

            <!-- Door & Exit Arrow Icon -->
            <div class="w-16 h-16 rounded-full bg-[#FEF0EE] flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true">
                    <rect x="8.5" y="6" width="10" height="20" rx="1.5" fill="#F04438" fill-opacity="0.12"
                        stroke="#F04438" stroke-opacity="0.45" stroke-width="1.5" />
                    <path d="M8.5 6.4L14.8 8.3V23.7L8.5 25.6V6.4Z" fill="#F04438" fill-opacity="0.28" stroke="#F04438"
                        stroke-width="1.5" stroke-linejoin="round" />
                    <circle cx="12.7" cy="16" r="0.95" fill="#E03121" />
                    <path d="M16.5 16H25M22 12.8L25.2 16L22 19.2" stroke="#E03121" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <!-- Title & Description -->
            <h3 id="logout-modal-title"
                class="text-[18px] sm:text-[19px] font-bold text-slate-900 tracking-tight leading-snug">
                Keluar dari akun?
            </h3>

            <p class="mt-2 text-xs sm:text-[13px] text-slate-500 leading-relaxed max-w-[270px] mx-auto">
                Sesi aktif Anda akan berakhir dan Anda perlu masuk kembali untuk mengakses akun ini.
            </p>

            <!-- Action Buttons -->
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button type="button" @click="closeLogoutModal()" :disabled="isSubmitting"
                    class="w-full py-2.5 px-4 rounded-xl bg-white hover:bg-[#FEF2F0] text-[#E63924] border border-[#F5A89D] font-semibold text-sm transition cursor-pointer disabled:opacity-60">
                    Batal
                </button>

                <form method="POST" action="{{ $logoutActionRoute }}" data-no-confirm="true"
                    @submit="handleConfirmSubmit($event)" class="w-full m-0 p-0">
                    @csrf
                    <button type="submit" :disabled="isSubmitting"
                        class="w-full py-2.5 px-4 rounded-xl bg-[#EE3D23] hover:bg-[#D93118] active:bg-[#C42912] text-white font-semibold text-sm shadow-sm hover:shadow transition cursor-pointer inline-flex items-center justify-center gap-1.5 disabled:opacity-75 disabled:cursor-wait">
                        <svg x-show="isSubmitting" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Keluar...' : 'Keluar'">Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Listener untuk mendeteksi penekanan tombol Backspace di luar input field
    // atau navigasi history browser back
    (function () {
        // Tangani tombol Backspace
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                const target = e.target;
                const tag = (target.tagName || '').toLowerCase();
                const isInputField = tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable;

                // Jika Backspace ditekan di luar input teks (perilaku navigasi kembali)
                if (!isInputField) {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-logout-modal'));
                }
            }
        });

        // Tangani navigasi Back button browser jika sedang login
        if (window.history && window.history.pushState) {
            window.history.pushState({ page: 'auth_dashboard' }, document.title, window.location.href);
            window.addEventListener('popstate', function (event) {
                // Tahan navigasi back dan munculkan pop up konfirmasi keluar
                window.history.pushState({ page: 'auth_dashboard' }, document.title, window.location.href);
                window.dispatchEvent(new CustomEvent('open-logout-modal'));
            });
        }
    })();
</script>