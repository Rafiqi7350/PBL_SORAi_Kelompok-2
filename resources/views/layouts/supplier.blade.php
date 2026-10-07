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
    <aside class="w-full md:w-64 lg:w-72 bg-[#1E222D] text-white flex flex-col justify-between min-h-screen p-6 flex-shrink-0">
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

            <!-- Sidebar Navigation -->
            <div class="space-y-5">
                <p class="text-[11px] font-bold tracking-widest text-[#FFB703] uppercase font-mono-code">PANEL PEMASOK</p>
                
                <nav class="space-y-1.5">
                    <!-- Dashboard -->
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-semibold text-gray-300 hover:text-white hover:bg-gray-800 transition">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span>Dashboard & Analisis</span>
                    </a>

                    <!-- Pengajuan Masuk -->
                    <a href="#" class="flex items-center justify-between px-4 py-3 rounded-full text-sm font-semibold text-gray-300 hover:text-white hover:bg-gray-800 transition">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>Pengajuan Masuk</span>
                        </div>
                        <span class="bg-[#F4511E] text-white text-xs font-black rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">2</span>
                    </a>

                    <!-- Pesanan & Pengiriman -->
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-semibold text-gray-300 hover:text-white hover:bg-gray-800 transition">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                        <span>Pesanan & Pengiriman</span>
                    </a>

                    <!-- Kelola Stok (CRUD) — ACTIVE when on katalog pages -->
                    <a href="{{ route('supplier.katalog.index') }}" class="flex items-center gap-3 px-5 py-3.5 rounded-full text-sm font-extrabold transition
                        {{ request()->routeIs('supplier.katalog.*') 
                            ? 'bg-[#FFB703] text-[#1E222D] shadow-lg border-2 border-black' 
                            : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('supplier.katalog.*') ? 'text-[#1E222D]' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Kelola Stok (CRUD)</span>
                    </a>

                    <!-- Supply Planning -->
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-semibold text-gray-300 hover:text-white hover:bg-gray-800 transition">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Supply Planning</span>
                    </a>

                    <!-- Contract Monitoring -->
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-full text-sm font-semibold text-gray-300 hover:text-white hover:bg-gray-800 transition">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Contract Monitoring</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar Footer: Profile + Switch + Logout -->
        <div class="space-y-3 pt-8 border-t border-gray-800">
            <!-- User Profile Box -->
            <div class="bg-[#2A2E3D] p-3.5 rounded-2xl flex items-center gap-3 border border-gray-700">
                <div class="bg-[#FFB703] text-black w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                    👤
                </div>
                <div class="leading-tight overflow-hidden">
                    <p class="font-extrabold text-xs text-white truncate">
                        {{ auth()->user()?->supplierProfile?->company_name ?? 'Kelompok Tani Poultry ...' }}
                    </p>
                    <p class="text-[10px] font-bold text-[#FFB703]">Pemasok Terverifikasi</p>
                </div>
            </div>

            <!-- Switch to UMKM Button -->
            <a href="{{ route('umkm.katalog.index') }}" class="w-full flex items-center justify-center gap-2 bg-[#FFB703] hover:bg-[#FFB703]/90 text-black font-extrabold text-xs py-3 px-4 rounded-full border-2 border-black transition shadow-sm">
                <span>🔄</span> Switch Ke Sisi UMKM
            </a>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-[#3A2228] hover:bg-[#4A262E] text-rose-300 font-extrabold text-xs py-2.5 px-4 rounded-full transition border border-rose-900">
                    <span>🚪</span> Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Right Column -->
    <main class="flex-1 p-6 md:p-10 overflow-y-auto">
        @if(session('success'))
            <div class="bg-emerald-50 border-2 border-emerald-500 text-emerald-900 px-5 py-3.5 rounded-2xl shadow-sm mb-6 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs">✓</span>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border-2 border-rose-500 text-rose-900 px-5 py-3.5 rounded-2xl shadow-sm mb-6 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <span class="bg-rose-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs">✕</span>
                    <span class="font-bold text-sm">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.action-confirm-modal')

</body>
</html>
