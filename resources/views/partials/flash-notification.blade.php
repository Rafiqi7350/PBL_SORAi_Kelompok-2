{{-- Floating Toast Notifications Component (Tidak Menambah Baris Konten / No Layout Shift) --}}
<div class="fixed top-5 right-4 sm:right-8 z-50 max-w-sm sm:max-w-md w-full pointer-events-none space-y-3">
    @if(session('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 3000)"
             x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
             class="bg-emerald-600 text-white p-4 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between shadow-2xl pointer-events-auto"
             role="alert">
            <span class="tracking-tight">{{ session('success') }}</span>
            <button type="button"
                    @click="show = false"
                    class="font-bold text-base ml-3 hover:opacity-75 cursor-pointer leading-none p-1 rounded-md transition shrink-0"
                    title="Tutup Notifikasi">
                ✕
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 3500)"
             x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
             class="bg-rose-600 text-white p-4 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between shadow-2xl pointer-events-auto"
             role="alert">
            <span class="tracking-tight">{{ session('error') }}</span>
            <button type="button"
                    @click="show = false"
                    class="font-bold text-base ml-3 hover:opacity-75 cursor-pointer leading-none p-1 rounded-md transition shrink-0"
                    title="Tutup Notifikasi">
                ✕
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 3500)"
             x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
             class="bg-amber-600 text-white p-4 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between shadow-2xl pointer-events-auto"
             role="alert">
            <span class="tracking-tight">{{ session('warning') }}</span>
            <button type="button"
                    @click="show = false"
                    class="font-bold text-base ml-3 hover:opacity-75 cursor-pointer leading-none p-1 rounded-md transition shrink-0"
                    title="Tutup Notifikasi">
                ✕
            </button>
        </div>
    @endif

    @if(session('info'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 3000)"
             x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
             class="bg-blue-600 text-white p-4 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between shadow-2xl pointer-events-auto"
             role="alert">
            <span class="tracking-tight">{{ session('info') }}</span>
            <button type="button"
                    @click="show = false"
                    class="font-bold text-base ml-3 hover:opacity-75 cursor-pointer leading-none p-1 rounded-md transition shrink-0"
                    title="Tutup Notifikasi">
                ✕
            </button>
        </div>
    @endif
</div>
