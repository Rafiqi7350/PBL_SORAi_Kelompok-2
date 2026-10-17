<header class="w-full bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-50 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <a href="#beranda" class="flex items-center gap-3.5 group">
            <div class="w-10 h-10 rounded-xl bg-orange-600 text-white font-extrabold text-xl flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform duration-200">
                S
            </div>
            <div class="flex flex-col">
                <span class="font-extrabold text-slate-900 text-xl tracking-tight leading-none group-hover:text-orange-600 transition-colors">SORAi</span>
                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mt-1">Portal Dapur & Pemasok B2B</span>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-8 lg:gap-10 text-sm font-semibold text-slate-600">
            <a href="#beranda" class="hover:text-orange-600 transition-colors">Beranda</a>
            <a href="#fitur" class="hover:text-orange-600 transition-colors">Keunggulan</a>
            <a href="#alur" class="hover:text-orange-600 transition-colors">Alur Kerja</a>
            <a href="#kategori" class="hover:text-orange-600 transition-colors">Bahan Baku</a>
            <a href="#faq" class="hover:text-orange-600 transition-colors">FAQ</a>
        </nav>

        <div class="hidden sm:flex items-center gap-3">
            @auth
                @if(auth()->user()->role === 'umkm')
                    <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 transition-all cursor-pointer shadow-xs">
                        Dashboard UMKM
                    </a>
                @elseif(auth()->user()->role === 'supplier')
                    <a href="{{ route('supplier.katalog.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all cursor-pointer shadow-xs">
                        Dashboard Pemasok
                    </a>
                @elseif(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.commodities.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-black transition-all cursor-pointer shadow-xs">
                        Panel Admin
                    </a>
                @endif
                <a href="{{ route('logout') }}"
                   @click.prevent="$dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition-all cursor-pointer">
                    Keluar
                </a>
            @else
                <a href="{{ route('login') }}" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-orange-600 hover:text-white hover:border-orange-600 border border-slate-300 shadow-2xs transition-all duration-200 cursor-pointer">
                    Masuk
                </a>
            @endauth
        </div>

        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-xl hover:bg-slate-100 text-slate-700 focus:outline-none cursor-pointer" aria-label="Toggle Navigation">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenuOpen" x-cloak @click.away="mobileMenuOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden bg-white border-b border-slate-200 px-6 py-5 shadow-lg flex flex-col gap-3 font-semibold text-slate-700">
        <a href="#beranda" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-lg hover:bg-slate-50 hover:text-orange-600 transition">Beranda</a>
        <a href="#fitur" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-lg hover:bg-slate-50 hover:text-orange-600 transition">Keunggulan</a>
        <a href="#alur" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-lg hover:bg-slate-50 hover:text-orange-600 transition">Alur Kerja</a>
        <a href="#kategori" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-lg hover:bg-slate-50 hover:text-orange-600 transition">Bahan Baku</a>
        <a href="#faq" @click="mobileMenuOpen = false" class="py-2 px-3 rounded-lg hover:bg-slate-50 hover:text-orange-600 transition">FAQ</a>
        <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
            @auth
                @if(auth()->user()->role === 'umkm')
                    <a href="{{ route('umkm.katalog.index') }}" @click="mobileMenuOpen = false" class="w-full py-2.5 rounded-xl text-center text-sm font-bold text-white bg-orange-600">
                        Dashboard UMKM
                    </a>
                @elseif(auth()->user()->role === 'supplier')
                    <a href="{{ route('supplier.katalog.index') }}" @click="mobileMenuOpen = false" class="w-full py-2.5 rounded-xl text-center text-sm font-bold text-white bg-slate-900">
                        Dashboard Pemasok
                    </a>
                @endif
                <a href="{{ route('logout') }}"
                   @click.prevent="mobileMenuOpen = false; $dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="w-full py-2 rounded-xl text-center text-xs font-bold text-slate-600 bg-slate-100 cursor-pointer">
                    Keluar
                </a>
            @else
                <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="w-full py-2.5 rounded-xl text-center text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-orange-600 hover:text-white transition-colors">
                    Masuk
                </a>
            @endauth
        </div>
    </div>


    @auth
        @include('partials.logout-modal')
    @endauth
</header>

