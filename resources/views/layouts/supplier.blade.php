<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI - Kelola Stok Komoditas Pemasok</title>
    
    <!-- Google Fonts Outfit & Space Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Outfit', sans-serif; }
        .font-mono-code { font-family: 'Space Mono', monospace; }
    </style>
</head>
<body class="bg-[#FDF5E6] text-gray-900 antialiased min-h-screen flex flex-col md:flex-row">

    <!-- Left Sidebar -->
    <aside class="w-full md:w-72 lg:w-80 bg-[#1E222D] text-white flex flex-col justify-between min-h-screen p-6 flex-shrink-0">
        <div class="space-y-8">
            <!-- Brand Header -->
            <div class="flex items-center gap-3">
                <div class="bg-[#FFB703] rounded-full w-12 h-12 flex items-center justify-center font-black text-2xl text-black border-2 border-black shadow-md">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-extrabold text-2xl tracking-tight text-[#FFB703]">SORAI</span>
                    <span class="text-[10px] font-bold tracking-widest text-gray-300 uppercase">PORTAL PEMASOK B2B</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('supplier.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('supplier.katalog.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-300 hover:text-white hover:bg-gray-800' }}">
                    Kelola Katalog & Stok
                </a>
                <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full font-semibold text-gray-300 hover:text-white hover:bg-gray-800 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#FFB703]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Lihat Katalog UMKM
                </a>
            </nav>

            <!-- Profile / Actions -->
            <div class="flex items-center gap-2.5">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-xs font-bold text-white">
                        {{ auth()->user()?->supplierProfile?->company_name ?? 'Pemasok' }}
                    </span>
                    <span class="text-[10px] font-mono text-emerald-400">
                        🟢 Sesi Pemasok Aktif
                    </span>
                </div>

                <a href="{{ route('logout') }}"
                   @click.prevent="$dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="px-4 py-2 rounded-full font-bold text-xs bg-red-600/20 hover:bg-red-600 text-red-200 hover:text-white border border-red-500/30 transition flex items-center gap-1.5 cursor-pointer"
                   title="Keluar dari akun ini">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer Actions -->
        <div class="space-y-3 pt-8 border-t border-gray-800">
            <!-- User Profile Box -->
            <div class="bg-[#2A2E3D] p-3.5 rounded-2xl flex items-center gap-3 border border-gray-700">
                <div class="bg-[#FFB703] text-black w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm">
                    👤
                </div>
                <div class="leading-tight overflow-hidden">
                    <p class="font-extrabold text-xs text-white truncate">Kelompok Tani Poultry ...</p>
                    <p class="text-[10px] font-bold text-[#FFB703]">Pemasok Terverifikasi</p>
                </div>
            </div>

            <!-- Switch to UMKM Button -->
            <a href="{{ route('umkm.katalog.index') }}" class="w-full flex items-center justify-center gap-2 bg-[#FFB703] hover:bg-[#FFB703]/90 text-black font-extrabold text-xs py-3 px-4 rounded-full border-2 border-black transition shadow-sm">
                <span>🔄</span> Switch Ke Sisi UMKM
            </a>

            <!-- Logout Button -->
            <button class="w-full flex items-center justify-center gap-2 bg-[#3A2228] hover:bg-[#4A262E] text-rose-300 font-extrabold text-xs py-2.5 px-4 rounded-full transition border border-rose-900">
                <span>🚪</span> Keluar
            </button>
        </div>
    </aside>

    <!-- Main Content Right Column -->
    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        @if(session('success'))
            <div class="bg-emerald-50 border-2 border-emerald-500 text-emerald-900 px-5 py-3.5 rounded-2xl shadow-sm mb-6 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs">✓</span>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs font-semibold text-gray-500">
        SORAI Portal Kemitraan UMKM & Pemasok Bahan Baku Lokal &copy; {{ date('Y') }}
    </footer>

    <!-- Modal Konfirmasi Keluar -->
    @include('partials.logout-modal')

</body>
</html>
