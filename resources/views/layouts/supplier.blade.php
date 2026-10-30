<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI - Kelola Stok Komoditas Pemasok</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Outfit', sans-serif; }
        .font-mono-code { font-family: 'Space Mono', monospace; }
    </style>
<body class="bg-[#f3f4f6] text-gray-900 antialiased min-h-screen flex">

    <aside class="fixed top-0 left-0 h-screen w-64 bg-[#1E222D] text-white flex flex-col justify-between p-6 z-30 overflow-y-auto shadow-xl">
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="bg-[#ea580c] text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-xl shadow-xs">
                    S
                </div>
                <div>
                    <h1 class="font-extrabold text-xl tracking-wide text-white leading-none">SORAI</h1>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Portal Pemasok B2B</span>
                </div>
            </div>

            <div class="text-[10px] font-black uppercase text-[#ea580c] tracking-widest px-1">
                PANEL PEMASOK
            </div>

            <nav class="space-y-2">
                <a href="{{ route('supplier.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition
                    {{ request()->routeIs('supplier.dashboard') ? 'bg-[#ea580c] text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Dashboard & Analisis
                </a>

                <a href="{{ route('supplier.pengajuan-masuk.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition
                    {{ request()->routeIs('supplier.pengajuan-masuk.*') ? 'bg-[#ea580c] text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Pengajuan Masuk
                </a>

                <a href="{{ route('supplier.pengiriman.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition
                    {{ request()->routeIs('supplier.pengiriman.*') ? 'bg-[#ea580c] text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Pesanan & Pengiriman
                </a>

                <a href="{{ route('supplier.katalog.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition
                    {{ request()->routeIs('supplier.katalog.*') ? 'bg-[#ea580c] text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Kelola Stok
                </a>

                <a href="{{ route('supplier.supply-planning.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition
                    {{ request()->routeIs('supplier.supply-planning.*') ? 'bg-[#ea580c] text-white shadow-md' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Supply Planning
                </a>

                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold text-gray-400 hover:text-white hover:bg-gray-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Contract Monitoring
                </a>
            </nav>
        </div>

        <div class="space-y-3 pt-6">
            <div class="bg-gray-800/60 p-3 rounded-2xl flex items-center gap-3 border border-gray-700/50">
                <div class="w-8 h-8 rounded-full bg-[#ea580c] text-white flex items-center justify-center font-extrabold text-xs">
                    {{ substr(auth()->user()?->supplierProfile?->company_name ?? 'P', 0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ auth()->user()?->supplierProfile?->company_name ?? 'Pemasok' }}</p>
                    <p class="text-[10px] text-orange-400 font-medium">Pemasok Terverifikasi</p>
                </div>
            </div>


            <a href="{{ route('logout') }}"
               @click.prevent="$dispatch('open-logout-modal')"
               onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
               class="w-full py-2.5 px-4 bg-gray-800 hover:bg-red-600/80 text-gray-300 hover:text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-6 sm:p-8 min-w-0">
        @yield('content')
    </main>

    @include('partials.logout-modal')

</body>
</html>