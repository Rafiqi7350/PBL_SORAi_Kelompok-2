{{-- Flash Notifications Component with Smooth Auto-Dismiss --}}
@if(session('success'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
         class="bg-emerald-50 border border-emerald-400 text-emerald-900 px-5 py-3.5 rounded-2xl mb-6 flex items-center justify-between shadow-xs transition-all"
         role="alert">
        <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                ✓
            </span>
            <span class="font-bold text-xs sm:text-sm tracking-tight">{{ session('success') }}</span>
        </div>
        <button type="button"
                @click="show = false"
                class="text-emerald-700 hover:text-emerald-950 font-bold text-xl leading-none p-1 rounded-lg hover:bg-emerald-100/50 cursor-pointer ml-4 transition"
                title="Tutup Notifikasi">
            &times;
        </button>
    </div>
@endif

@if(session('error'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4500)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
         class="bg-rose-50 border border-rose-300 text-rose-900 px-5 py-3.5 rounded-2xl mb-6 flex items-center justify-between shadow-xs transition-all"
         role="alert">
        <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                ✕
            </span>
            <span class="font-bold text-xs sm:text-sm tracking-tight">{{ session('error') }}</span>
        </div>
        <button type="button"
                @click="show = false"
                class="text-rose-700 hover:text-rose-950 font-bold text-xl leading-none p-1 rounded-lg hover:bg-rose-100/50 cursor-pointer ml-4 transition"
                title="Tutup Notifikasi">
            &times;
        </button>
    </div>
@endif

@if(session('warning'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4500)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
         class="bg-amber-50 border border-amber-300 text-amber-900 px-5 py-3.5 rounded-2xl mb-6 flex items-center justify-between shadow-xs transition-all"
         role="alert">
        <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                !
            </span>
            <span class="font-bold text-xs sm:text-sm tracking-tight">{{ session('warning') }}</span>
        </div>
        <button type="button"
                @click="show = false"
                class="text-amber-700 hover:text-amber-950 font-bold text-xl leading-none p-1 rounded-lg hover:bg-amber-100/50 cursor-pointer ml-4 transition"
                title="Tutup Notifikasi">
            &times;
        </button>
    </div>
@endif

@if(session('info'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
         class="bg-blue-50 border border-blue-300 text-blue-900 px-5 py-3.5 rounded-2xl mb-6 flex items-center justify-between shadow-xs transition-all"
         role="alert">
        <div class="flex items-center gap-3">
            <span class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                ℹ
            </span>
            <span class="font-bold text-xs sm:text-sm tracking-tight">{{ session('info') }}</span>
        </div>
        <button type="button"
                @click="show = false"
                class="text-blue-700 hover:text-blue-950 font-bold text-xl leading-none p-1 rounded-lg hover:bg-blue-100/50 cursor-pointer ml-4 transition"
                title="Tutup Notifikasi">
            &times;
        </button>
    </div>
@endif
