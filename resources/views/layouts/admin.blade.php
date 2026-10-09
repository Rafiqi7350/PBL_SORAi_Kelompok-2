<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI — Control Panel Administrator Systems</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }
        .display-poster {
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.02em;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-900 flex antialiased selection:bg-orange-500 selection:text-white">

    <!-- ========================================================= -->
    <!-- SIDEBAR VERTIKAL SISI ADMIN (WARNA ORANYE MODERN)         -->
    <!-- ========================================================= -->
    <aside class="fixed top-0 left-0 h-screen w-64 bg-[#151923] text-slate-300 flex flex-col justify-between p-5 border-r border-slate-800 z-40 overflow-y-auto shadow-2xl">
        <div class="space-y-8">

            <!-- BRAND & LOGO ADMIN -->
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2 pt-2 group">
                <div class="w-10 h-10 rounded-xl bg-[#ea580c] text-white flex items-center justify-center font-extrabold text-xl shadow-lg shadow-orange-950/50 group-hover:scale-105 transition transform">
                    S
                </div>
                <div>
                    <h1 class="font-extrabold text-2xl tracking-tight text-white leading-none">SORAI</h1>
                    <p class="text-[9px] font-mono text-orange-400 font-bold tracking-widest uppercase mt-0.5">Admin Control Hub</p>
                </div>
            </a>

            <!-- NAVIGASI MENU UTAMA ADMIN -->
            <div class="space-y-1.5">
                <span class="font-mono text-[10px] font-bold text-slate-500 uppercase px-3 tracking-wider block">MENU ADMINISTRATOR</span>

                <!-- Dashboard Admin -->
                <a 
                    href="{{ route('admin.dashboard') }}"
                    class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between {{ request()->routeIs('admin.dashboard') && !request()->query('tab') ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard Admin</span>
                    </div>
                </a>

                <!-- Manajemen Pengguna -->
                <a 
                    href="{{ route('admin.dashboard', ['tab' => 'users']) }}"
                    class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between {{ request()->query('tab') === 'users' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Manajemen Pengguna</span>
                    </div>
                </a>

                <!-- Master Data Komoditas -->
                <a 
                    href="{{ route('admin.commodities.index') }}"
                    class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between {{ request()->routeIs('admin.commodities.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.units.*') || request()->query('tab') === 'master' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Master Data Komoditas</span>
                    </div>
                </a>

                <!-- Submenu Master Data jika di halaman terkait -->
                <div class="pl-6 space-y-1 pt-1">
                    <a href="{{ route('admin.commodities.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition {{ request()->routeIs('admin.commodities.*') ? 'text-orange-400 bg-orange-500/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span>Daftar Komoditas</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition {{ request()->routeIs('admin.categories.*') ? 'text-orange-400 bg-orange-500/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Kategori Acuan</span>
                    </a>
                    <a href="{{ route('admin.units.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition {{ request()->routeIs('admin.units.*') ? 'text-orange-400 bg-orange-500/10' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 18h12l3-18H3zM9 6v18M15 6v18" />
                        </svg>
                        <span>Satuan Baku</span>
                    </a>
                </div>

                <!-- Monitoring Sistem -->
                <a 
                    href="{{ route('admin.dashboard', ['tab' => 'monitoring']) }}"
                    class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between {{ request()->query('tab') === 'monitoring' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 012 2h2a2 2 0 012-2z" />
                        </svg>
                        <span>Monitoring Sistem</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- USER PROFILE & LOGOUT -->
        <div class="pt-6 border-t border-slate-800 space-y-3">
            <div class="bg-slate-800/60 p-3 rounded-xl border border-slate-700/50 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#ea580c] text-white font-bold flex items-center justify-center text-xs shadow-inner flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="overflow-hidden">
                    <p class="font-extrabold text-xs text-white truncate">Super Admin SORAI</p>
                    <p class="font-mono text-[9px] text-orange-400 truncate">{{ auth()->user()->email ?? 'admin@sorai.id' }}</p>
                </div>
            </div>

            <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                @csrf
                <button 
                    type="submit"
                    class="w-full bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white font-bold text-xs py-2.5 rounded-xl transition border border-rose-500/20 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- AREA KONTEN UTAMA SISI ADMIN (KANAN) -->
    <main class="flex-1 ml-64 p-8 overflow-y-auto space-y-6 min-w-0">

        <!-- Flash Success Notification -->
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-4 rounded-2xl text-xs font-bold flex justify-between items-center shadow-sm" role="alert">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="font-bold text-base hover:text-rose-700 cursor-pointer">&times;</button>
            </div>
        @endif

        <!-- Flash Validation Errors -->
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl shadow-sm space-y-1">
                <div class="flex items-center gap-2 font-bold text-xs uppercase">
                    <span class="w-4 h-4 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px]">✕</span>
                    <span>Terjadi Kesalahan:</span>
                </div>
                <ul class="list-disc list-inside text-xs font-semibold space-y-0.5 pl-4 text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Action & Logout Confirmation Modal -->
    @include('partials.action-confirm-modal')

</body>
</html>
