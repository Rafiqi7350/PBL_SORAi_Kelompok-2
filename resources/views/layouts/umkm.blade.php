<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI - Portal Kemitraan UMKM</title>
    
    <!-- Using Google Fonts for Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js for interactive elements -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#FDF5E6] text-gray-900 antialiased min-h-screen flex flex-col">

    <!-- Top Navbar -->
    <header class="p-4 px-6 md:px-12 w-full pt-6">
        <div class="bg-white rounded-full px-6 py-3 flex items-center justify-between shadow-sm">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-[#FFB703] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg text-black">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-extrabold text-xl tracking-tight text-[#1E222D]">SORAI</span>
                    <span class="text-[10px] font-bold tracking-widest text-gray-500 uppercase">Portal Dapur UMKM</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.katalog.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Katalog Bahan Baku
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full flex items-center gap-2 {{ request()->routeIs('umkm.pemesanan.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Pemesanan & Supply Planning
                    <span class="bg-[#FF8FA3] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">2</span>
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.dashboard.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Dashboard Analisis
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.contract.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Contract Monitoring
                </a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-xs font-bold text-gray-900">
                        {{ auth()->user()?->umkmProfile?->owner_name ?? auth()->user()?->umkmProfile?->business_name ?? (auth()->user()?->supplierProfile?->company_name ?? 'Mitra') }}
                    </span>
                    <span class="text-[10px] font-semibold {{ auth()->user()?->role === 'supplier' ? 'text-[#DC4E00]' : 'text-emerald-700' }}">
                        {{ auth()->user()?->role === 'supplier' ? 'Akun Pemasok' : 'Akun UMKM' }}
                    </span>
                </div>

                <a href="{{ route('logout') }}"
                   @click.prevent="$dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="px-5 py-2 rounded-full font-bold text-xs bg-gray-100 hover:bg-red-50 text-gray-800 hover:text-red-600 transition flex items-center gap-1.5 border border-gray-200 shadow-sm cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </a>
            </div>
        </div>

        @if(auth()->check() && auth()->user()->role === 'supplier')
            <div class="bg-amber-100 border-t border-b border-amber-300 text-amber-900 px-6 py-2 text-xs font-semibold flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span>Anda sedang melihat katalog dalam mode <strong>Akun Pemasok</strong> ({{ auth()->user()->supplierProfile?->company_name ?? auth()->user()->email }}).</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('supplier.katalog.index') }}" class="text-[#DC4E00] hover:underline font-bold">
                        Buka Dashboard Pemasok Anda →
                    </a>
                    <button type="button"
                            @click="$dispatch('open-logout-modal')"
                            onclick="window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                            class="bg-[#DC4E00] text-white px-3 py-1 rounded-full text-[11px] font-bold hover:bg-[#C83E00] transition cursor-pointer">
                        Ganti ke Akun UMKM
                    </button>
                </div>
            </div>
        @endif
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col w-full px-6 md:px-12 py-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Modal Konfirmasi Keluar -->
    @include('partials.logout-modal')

</body>
</html>
