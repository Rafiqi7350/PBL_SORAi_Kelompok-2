<div x-data="{ showLogoutModal: false }"
     @open-logout-modal.window="showLogoutModal = true"
     @keydown.escape.window="showLogoutModal = false"
     x-cloak>

    <!-- Modal Backdrop & Dialog -->
    <div x-show="showLogoutModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

        <!-- Card Dialog -->
        <div @click.away="showLogoutModal = false"
             x-show="showLogoutModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full text-center shadow-2xl border-2 border-gray-900 relative">

            <!-- Close icon button -->
            <button type="button"
                    @click="showLogoutModal = false"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 p-1.5 rounded-full hover:bg-gray-100 transition cursor-pointer"
                    title="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Icon -->
            <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>

            <!-- Content -->
            <h3 class="text-xl sm:text-2xl font-extrabold text-gray-900 mb-2">
                Apakah Anda Yakin Ingin Keluar?
            </h3>

            <p class="text-xs sm:text-sm text-gray-600 mb-5 leading-relaxed">
                Sesi login akun Anda akan diakhiri secara tuntas sehingga data usaha Anda tetap aman. Anda perlu memasukkan email & kata sandi kembali untuk masuk.
            </p>

            @if(auth()->check())
                <div class="mb-6 p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200 text-xs text-left flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-200 text-amber-900 font-extrabold flex items-center justify-center flex-shrink-0 shadow-sm">
                        👤
                    </div>
                    <div class="truncate">
                        <span class="font-bold text-gray-900 block truncate text-sm">
                            {{ auth()->user()->role === 'supplier' ? (auth()->user()->supplierProfile?->company_name ?? auth()->user()->email) : (auth()->user()->umkmProfile?->owner_name ?? auth()->user()->email) }}
                        </span>
                        <span class="text-gray-500 font-mono text-[11px] block truncate">{{ auth()->user()->email }} ({{ strtoupper(auth()->user()->role) }})</span>
                    </div>
                </div>
            @endif

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <button type="button"
                        @click="showLogoutModal = false"
                        class="w-full sm:w-1/2 py-3 px-4 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-extrabold text-xs sm:text-sm transition cursor-pointer">
                    Batal (Tetap Masuk)
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-1/2 m-0 p-0">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 px-4 rounded-full bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Ya, Keluar Akun</span>
                        <span class="text-red-200">→</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Listener untuk mendeteksi penekanan tombol Backspace di luar input field
    // atau navigasi history browser back
    (function() {
        // Tangani tombol Backspace
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace') {
                const target = e.target;
                const tag = (target.tagName || '').toLowerCase();
                const isInputField = tag === 'input' || tag === 'textarea' || target.isContentEditable;

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
            window.addEventListener('popstate', function(event) {
                // Tahan navigasi back dan munculkan pop up konfirmasi keluar
                window.history.pushState({ page: 'auth_dashboard' }, document.title, window.location.href);
                window.dispatchEvent(new CustomEvent('open-logout-modal'));
            });
        }
    })();
</script>
