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
            background-color: #fef3c7;
            color: #1e2330;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }
        .display-poster {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.03em;
            line-height: 1.05;
        }
    </style>
</head>
<body class="min-h-screen bg-bone-canvas text-midnight-ink flex antialiased selection:bg-voltage-lime selection:text-midnight-ink">

    <!-- ========================================================= -->
    <!-- SIDEBAR VERTIKAL SISI ADMIN (WARNA ORANYE NEO-BRUTALISM)  -->
    <!-- ========================================================= -->
    <aside class="fixed top-0 left-0 h-screen w-64 bg-midnight-ink text-plaster-white flex flex-col justify-between p-5 border-r-4 border-midnight-ink z-40 overflow-y-auto shadow-2xl">
        <div class="space-y-8">

            <!-- BRAND & LOGO ADMIN -->
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-2 pt-2 group">
                <div class="w-10 h-10 rounded-2xl bg-sun-orange border-2 border-plaster-white flex items-center justify-center font-display font-extrabold text-midnight-ink text-xl shadow-md group-hover:rotate-6 transition">
                    S
                </div>
                <div>
                    <h1 class="font-display font-extrabold text-2xl tracking-tighter text-voltage-lime leading-none">SORAI</h1>
                    <p class="text-[9px] font-mono text-plaster-white/70 font-bold tracking-widest uppercase">Admin Control Hub</p>
                </div>
            </a>

            <!-- NAVIGASI MENU UTAMA ADMIN -->
            <div class="space-y-1.5">
                <span class="font-mono text-[10px] font-bold text-voltage-lime/80 uppercase px-3 tracking-wider block">MENU ADMINISTRATOR</span>

                <!-- Dashboard Admin -->
                <a 
                    href="{{ route('admin.dashboard') }}"
                    class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between {{ request()->routeIs('admin.dashboard') && !request()->query('tab') ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white' }}">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard Admin</span>
                    </div>
                </a>

                <!-- Manajemen Pengguna -->
                <a 
                    href="{{ route('admin.dashboard', ['tab' => 'users']) }}"
                    class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between {{ request()->query('tab') === 'users' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white' }}">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Manajemen Pengguna</span>
                    </div>
                </a>

                <!-- Master Data Komoditas -->
                <a 
                    href="{{ route('admin.commodities.index') }}"
                    class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between {{ request()->routeIs('admin.commodities.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.units.*') || request()->query('tab') === 'master' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white' }}">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Master Data Komoditas</span>
                    </div>
                </a>

                <!-- Submenu Master Data jika di halaman terkait -->
                <div class="pl-5 space-y-1 pt-1">
                    <a href="{{ route('admin.commodities.index') }}" class="block text-[11px] font-bold py-1 px-2.5 rounded-lg transition {{ request()->routeIs('admin.commodities.*') ? 'text-voltage-lime bg-plaster-white/10' : 'text-plaster-white/60 hover:text-plaster-white' }}">
                        • Daftar Komoditas
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="block text-[11px] font-bold py-1 px-2.5 rounded-lg transition {{ request()->routeIs('admin.categories.*') ? 'text-voltage-lime bg-plaster-white/10' : 'text-plaster-white/60 hover:text-plaster-white' }}">
                        • Kategori Acuan
                    </a>
                    <a href="{{ route('admin.units.index') }}" class="block text-[11px] font-bold py-1 px-2.5 rounded-lg transition {{ request()->routeIs('admin.units.*') ? 'text-voltage-lime bg-plaster-white/10' : 'text-plaster-white/60 hover:text-plaster-white' }}">
                        • Satuan Baku
                    </a>
                </div>

                <!-- Monitoring Sistem -->
                <a 
                    href="{{ route('admin.dashboard', ['tab' => 'monitoring']) }}"
                    class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between {{ request()->query('tab') === 'monitoring' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white' }}">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 012 2h2a2 2 0 012-2z" />
                        </svg>
                        <span>Monitoring Sistem</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- USER PROFILE & LOGOUT -->
        <div class="pt-6 border-t border-plaster-white/20 space-y-3">
            <div class="bg-plaster-white/10 p-3 rounded-2xl border border-plaster-white/20 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-sun-orange text-midnight-ink font-bold flex items-center justify-center text-xs border border-plaster-white flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="overflow-hidden">
                    <p class="font-extrabold text-xs text-plaster-white truncate">Super Admin SORAI</p>
                    <p class="font-mono text-[9px] text-voltage-lime truncate">{{ auth()->user()->email ?? 'System Verifier' }}</p>
                </div>
            </div>

            <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                @csrf
                <button 
                    type="submit"
                    class="w-full bg-rose-500/20 hover:bg-rose-500 text-rose-200 hover:text-plaster-white font-extrabold text-xs py-2.5 rounded-pill transition border border-rose-400/30 flex items-center justify-center gap-1.5 cursor-pointer">
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
            <div class="bg-emerald-300 text-midnight-ink border-2 border-midnight-ink p-4 rounded-2xl font-mono text-xs font-bold flex justify-between items-center shadow-md animate-bounce" role="alert">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="font-extrabold text-base leading-none hover:text-rose-700 cursor-pointer">&times;</button>
            </div>
        @endif

        <!-- Flash Validation Errors -->
        @if($errors->any())
            <div class="bg-rose-100 border-2 border-rose-500 text-rose-950 p-4 rounded-2xl shadow-md space-y-1">
                <div class="flex items-center gap-2 font-bold text-xs uppercase font-mono">
                    <span class="w-4 h-4 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px]">✕</span>
                    <span>Terjadi Kesalahan:</span>
                </div>
                <ul class="list-disc list-inside text-xs font-semibold space-y-0.5 pl-4 text-rose-900">
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
