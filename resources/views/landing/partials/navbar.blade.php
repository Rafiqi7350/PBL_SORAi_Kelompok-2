<header class="w-full max-w-6xl mx-auto pt-4 sm:pt-6 px-4 sm:px-6 sticky top-0 z-40">
    <div class="bg-white/90 backdrop-blur-md rounded-full px-5 sm:px-8 py-3.5 shadow-md flex items-center justify-between transition-all duration-300">
        <a href="#" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-full bg-[#F5B800] text-black font-extrabold text-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                S
            </div>
            <div class="flex flex-col">
                <span class="font-extrabold text-gray-900 text-lg sm:text-xl tracking-tight leading-none group-hover:text-[#DC4E00] transition-colors">SORAi</span>
                <span class="text-[9px] sm:text-[10px] font-bold text-gray-500 tracking-wider uppercase mt-0.5">PORTAL DAPUR UMKM</span>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-700">
            <a href="#beranda" class="hover:text-[#DC4E00] transition-colors">Beranda</a>
            <a href="#fitur" class="hover:text-[#DC4E00] transition-colors">Keunggulan</a>
            <a href="#alur" class="hover:text-[#DC4E00] transition-colors">Alur Kerja</a>
            <a href="#kategori" class="hover:text-[#DC4E00] transition-colors">Bahan Baku</a>
            <a href="#faq" class="hover:text-[#DC4E00] transition-colors">FAQ</a>
        </nav>

        <div class="hidden sm:flex items-center gap-3">
            @auth
                @if(auth()->user()->role === 'umkm')
                    <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2.5 rounded-full text-sm font-bold text-black bg-[#F5B800] hover:bg-[#e0a800] transition-all cursor-pointer shadow-sm">
                        Dashboard UMKM
                    </a>
                @elseif(auth()->user()->role === 'supplier')
                    <a href="{{ route('supplier.katalog.index') }}" class="px-5 py-2.5 rounded-full text-sm font-bold text-white bg-[#DC4E00] hover:bg-[#C83E00] transition-all cursor-pointer shadow-sm">
                        Dashboard Pemasok
                    </a>
                @elseif(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.commodities.index') }}" class="px-5 py-2.5 rounded-full text-sm font-bold text-white bg-gray-900 hover:bg-black transition-all cursor-pointer shadow-sm">
                        Panel Admin
                    </a>
                @endif
                <a href="{{ route('logout') }}"
                   @click.prevent="$dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="px-4 py-2.5 rounded-full text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">
                    Keluar
                </a>
            @else
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-full text-sm font-bold text-gray-800 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">
                    Masuk
                </a>
            @endauth
        </div>

        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-full hover:bg-gray-100 text-gray-700 focus:outline-none" aria-label="Toggle Navigation">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div x-show="mobileMenuOpen" x-cloak @click.away="mobileMenuOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden mt-2 bg-white rounded-3xl p-6 shadow-xl flex flex-col gap-4 text-center font-semibold text-gray-800">
        <a href="#beranda" @click="mobileMenuOpen = false" class="py-2 hover:text-[#DC4E00]">Beranda</a>
        <a href="#fitur" @click="mobileMenuOpen = false" class="py-2 hover:text-[#DC4E00]">Keunggulan</a>
        <a href="#alur" @click="mobileMenuOpen = false" class="py-2 hover:text-[#DC4E00]">Alur Kerja</a>
        <a href="#kategori" @click="mobileMenuOpen = false" class="py-2 hover:text-[#DC4E00]">Bahan Baku</a>
        <a href="#faq" @click="mobileMenuOpen = false" class="py-2 hover:text-[#DC4E00]">FAQ</a>
        <div class="pt-2 border-t border-gray-100 flex flex-col gap-2">
            @auth
                @if(auth()->user()->role === 'umkm')
                    <a href="{{ route('umkm.katalog.index') }}" @click="mobileMenuOpen = false" class="w-full py-3 rounded-full text-sm font-bold text-black bg-[#F5B800]">
                        Dashboard UMKM
                    </a>
                @elseif(auth()->user()->role === 'supplier')
                    <a href="{{ route('supplier.katalog.index') }}" @click="mobileMenuOpen = false" class="w-full py-3 rounded-full text-sm font-bold text-white bg-[#DC4E00]">
                        Dashboard Pemasok
                    </a>
                @endif
                <a href="{{ route('logout') }}"
                   @click.prevent="mobileMenuOpen = false; $dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="w-full py-2.5 rounded-full text-xs font-bold text-gray-700 bg-gray-100 cursor-pointer">
                    Keluar
                </a>
            @else
                <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="w-full py-3 rounded-full text-sm font-bold text-gray-800 bg-gray-100 hover:bg-gray-200">
                    Masuk
                </a>
            @endauth
        </div>
    </div>

    @auth
        @include('partials.logout-modal')
    @endauth
</header>
