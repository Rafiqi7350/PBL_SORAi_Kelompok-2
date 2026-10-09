<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SORAI — Control Panel Administrator Systems</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

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
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[#f8fafc] text-slate-900 selection:bg-orange-500 selection:text-white">

    <div 
        x-data="{
            adminTab: '{{ $currentTab }}',
            chartFilter: 'minggu',
            userSearch: '',
            userFilterRole: 'ALL',
            isCategoryModalOpen: false,
            isUnitModalOpen: false,
            notification: '{{ session('success') ?? session('warning') ?? '' }}',
            clearNotif() { this.notification = ''; }
        }" 
        class="min-h-screen bg-[#f8fafc] text-slate-900 flex">

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

                    <!-- Tab Dashboard -->
                    <button 
                        type="button"
                        @click="adminTab = 'dashboard'"
                        :class="adminTab === 'dashboard' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white'"
                        class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span>Dashboard Admin</span>
                        </div>
                    </button>

                    <!-- Tab Manajemen Pengguna -->
                    <button 
                        type="button"
                        @click="adminTab = 'users'"
                        :class="adminTab === 'users' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white'"
                        class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Manajemen Pengguna</span>
                        </div>
                        @if($pendingUsersCount > 0)
                            <span class="bg-orange-500 text-white font-mono text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm animate-pulse">
                                {{ $pendingUsersCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Tab Master Data Komoditas -->
                    <button 
                        type="button"
                        @click="adminTab = 'master'"
                        :class="adminTab === 'master' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white'"
                        class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Master Data Komoditas</span>
                        </div>
                    </button>

                    <!-- Submenu Master Data -->
                    <div class="pl-6 space-y-1 pt-1" x-show="adminTab === 'master' || window.location.pathname.includes('/admin/commodities') || window.location.pathname.includes('/admin/categories') || window.location.pathname.includes('/admin/units')">
                        <a href="{{ route('admin.commodities.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition text-slate-400 hover:text-white hover:bg-slate-800/40">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>Daftar Komoditas</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition text-slate-400 hover:text-white hover:bg-slate-800/40">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>Kategori Acuan</span>
                        </a>
                        <a href="{{ route('admin.units.index') }}" class="flex items-center gap-2 text-[11px] font-bold py-2 px-3 rounded-xl transition text-slate-400 hover:text-white hover:bg-slate-800/40">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 18h12l3-18H3zM9 6v18M15 6v18" />
                            </svg>
                            <span>Satuan Baku</span>
                        </a>
                    </div>

                    <!-- Tab Monitoring Sistem -->
                    <button 
                        type="button"
                        @click="adminTab = 'monitoring'"
                        :class="adminTab === 'monitoring' ? 'bg-[#ea580c] text-white shadow-lg shadow-orange-950/40' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white'"
                        class="w-full text-left px-3.5 py-3 rounded-xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 012 2h2a2 2 0 012-2z" />
                            </svg>
                            <span>Monitoring Sistem</span>
                        </div>
                    </button>
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

            <!-- NOTIFICATION ALERT -->
            <div 
                x-show="notification" 
                x-cloak
                class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-4 rounded-2xl text-xs font-bold flex justify-between items-center shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">✓</span>
                    <span x-text="notification"></span>
                </div>
                <button @click="clearNotif()" class="font-bold text-base hover:text-rose-700 cursor-pointer">&times;</button>
            </div>

            <!-- ========================================================= -->
            <!-- 1. DASHBOARD RINGKASAN ADMIN (RE-DESIGN LIKE IMAGE 1)     -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'dashboard'" x-cloak class="space-y-6">
                
                <!-- HEADER AREA -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard Overview</h2>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">Selamat datang di Dashboard Control Systems! Berikut ringkasan aktivitas hari ini</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-[11px] font-semibold text-slate-400 block">Terakhir diperbarui</span>
                        <span class="text-sm font-bold text-slate-800 tracking-tight">{{ date('d M Y, H:i') }}</span>
                    </div>
                </div>

                <!-- TOP METRIC CARDS (GRID 4 COLS) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <!-- Card 1: Total UMKM / Users -->
                    <div class="bg-white p-5 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-3">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-orange-100/80 text-[#ea580c] flex items-center justify-center font-bold shadow-sm shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-400 block">Total UMKM</span>
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ $totalUmkm }}</p>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-orange-50 text-[#ea580c] border border-orange-100/80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span>+12% • {{ $activeUmkm }} Aktif</span>
                        </div>
                    </div>

                    <!-- Card 2: Total Pemasok -->
                    <div class="bg-white p-5 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-3">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-sky-100/80 text-sky-600 flex items-center justify-center font-bold shadow-sm shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-400 block">Total Pemasok</span>
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ $totalSuppliers }}</p>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 text-sky-600 border border-sky-100/80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span>+8% • {{ $activeSuppliers }} Aktif</span>
                        </div>
                    </div>

                    <!-- Card 3: Menunggu Verifikasi -->
                    <div class="bg-white p-5 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-3">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-emerald-100/80 text-emerald-600 flex items-center justify-center font-bold shadow-sm shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-400 block">Menunggu Verifikasi</span>
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ $pendingUsersCount }}</p>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $pendingUsersCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-100/80' : 'bg-emerald-50 text-emerald-700 border border-emerald-100/80' }}">
                            <span>{{ $pendingUsersCount > 0 ? '⚠️ Perlu Moderasi' : '● Semua Terverifikasi' }}</span>
                        </div>
                    </div>

                    <!-- Card 4: Kategori Komoditas -->
                    <div class="bg-white p-5 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-3">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-amber-100/80 text-amber-600 flex items-center justify-center font-bold shadow-sm shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-400 block">Kategori Komoditas</span>
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ count($categories) }}</p>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100/80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Standar Baku</span>
                        </div>
                    </div>

                </div>

                <!-- MAIN CONTENT GRID (LEFT 8 COLS, RIGHT 4 COLS) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- LEFT COLUMN (MAIN CHARTS) -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- CARD: TREN AKTIVITAS MINGGU INI -->
                        <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-slate-800 tracking-tight">Tren Aktivitas Minggu Ini</h3>
                                    <p class="text-xs text-slate-400 font-medium">Monitoring aktivitas harian pengguna</p>
                                </div>
                                <div class="inline-flex p-1 bg-slate-100 rounded-xl self-start sm:self-auto text-xs font-semibold">
                                    <button 
                                        @click="chartFilter = 'minggu'"
                                        :class="chartFilter === 'minggu' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                                        class="px-3.5 py-1 rounded-lg transition cursor-pointer">
                                        Minggu
                                    </button>
                                    <button 
                                        @click="chartFilter = 'bulan'"
                                        :class="chartFilter === 'bulan' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                                        class="px-3.5 py-1 rounded-lg transition cursor-pointer">
                                        Bulan
                                    </button>
                                </div>
                            </div>

                            <!-- WAVE SVG LINE CHART -->
                            <div class="relative w-full overflow-hidden pt-2">
                                <svg viewBox="0 0 700 240" class="w-full h-auto overflow-visible">
                                    <defs>
                                        <linearGradient id="soraiOrangeGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#ea580c" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#ea580c" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Grid lines & Y Axis -->
                                    <line x1="40" y1="20" x2="680" y2="20" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="24" class="text-[10px] fill-slate-300 font-sans" text-anchor="end">100</text>

                                    <line x1="40" y1="60" x2="680" y2="60" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="64" class="text-[10px] fill-slate-300 font-sans" text-anchor="end">80</text>

                                    <line x1="40" y1="100" x2="680" y2="100" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="104" class="text-[10px] fill-slate-300 font-sans" text-anchor="end">60</text>

                                    <line x1="40" y1="140" x2="680" y2="140" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="144" class="text-[10px] fill-slate-300 font-sans" text-anchor="end">40</text>

                                    <line x1="40" y1="180" x2="680" y2="180" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="184" class="text-[10px] fill-slate-300 font-sans" text-anchor="end">20</text>

                                    <line x1="40" y1="215" x2="680" y2="215" stroke="#e2e8f0" stroke-width="1.5" />

                                    <!-- Area Fill under path -->
                                    <path 
                                        d="M40,175 C95,170 135,175 185,145 C235,115 275,105 325,160 C375,200 415,150 465,85 C515,35 555,85 605,95 C635,100 660,25 680,25 L680,215 L40,215 Z" 
                                        fill="url(#soraiOrangeGrad)" 
                                    />

                                    <!-- Smooth Line Path -->
                                    <path 
                                        d="M40,175 C95,170 135,175 185,145 C235,115 275,105 325,160 C375,200 415,150 465,85 C515,35 555,85 605,95 C635,100 660,25 680,25" 
                                        fill="none" 
                                        stroke="#ea580c" 
                                        stroke-width="3" 
                                        stroke-linecap="round"
                                    />

                                    <!-- X-Axis Labels -->
                                    <text x="50" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">JAN</text>
                                    <text x="105" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">FEB</text>
                                    <text x="160" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">MAR</text>
                                    <text x="215" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">APR</text>
                                    <text x="270" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">MEI</text>
                                    <text x="325" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">JUN</text>
                                    <text x="380" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">JUL</text>
                                    <text x="435" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">AGU</text>
                                    <text x="490" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">SEP</text>
                                    <text x="545" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">OKT</text>
                                    <text x="600" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">NOV</text>
                                    <text x="660" y="232" class="text-[9px] fill-slate-400 font-bold font-sans" text-anchor="middle">DES</text>
                                </svg>
                            </div>
                        </div>

                        <!-- TWO SUB-CARDS (ROW 2) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                            <!-- SUB-CARD 1: PROYEK / KOMODITAS PER KATEGORI -->
                            <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                                <div>
                                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Komoditas per Kategori</h3>
                                    <p class="text-xs text-slate-400 font-medium">Distribusi komoditas berdasarkan kategori</p>
                                </div>

                                <div class="space-y-3 pt-1">
                                    @php
                                        $catColors = ['bg-[#ea580c]', 'bg-sky-500', 'bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-indigo-500', 'bg-teal-500'];
                                    @endphp

                                    @forelse($categories as $idx => $cat)
                                        @php
                                            $color = $catColors[$idx % count($catColors)];
                                            $percent = min(100, max(20, ($cat['totalCommodities'] ?? rand(2, 8)) * 12));
                                        @endphp
                                        <div class="space-y-1">
                                            <div class="flex justify-between items-center text-xs font-semibold">
                                                <span class="text-slate-700 truncate max-w-[150px]">{{ $cat['name'] }}</span>
                                                <span class="text-slate-500 font-mono text-[11px]">{{ $cat['totalCommodities'] ?? rand(1, 6) }}</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full {{ $color }} rounded-full transition-all duration-500" style="width: {{ $percent }}%;"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center text-xs font-semibold">
                                                <span class="text-slate-700">Bumbu Dapur</span>
                                                <span class="text-slate-500 font-mono">5</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-[#ea580c] rounded-full" style="width: 75%;"></div>
                                            </div>

                                            <div class="flex justify-between items-center text-xs font-semibold pt-1">
                                                <span class="text-slate-700">Beras & Umbi</span>
                                                <span class="text-slate-500 font-mono">3</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-sky-500 rounded-full" style="width: 50%;"></div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- SUB-CARD 2: PERTUMBUHAN PENGGUNA -->
                            <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                                <div>
                                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Pertumbuhan Pengguna</h3>
                                    <p class="text-xs text-slate-400 font-medium">Jumlah pendaftaran per bulan</p>
                                </div>

                                <!-- VERTICAL BAR CHART SVG -->
                                <div class="pt-2">
                                    <svg viewBox="0 0 300 140" class="w-full h-auto">
                                        <line x1="20" y1="120" x2="290" y2="120" stroke="#e2e8f0" stroke-width="1.5" />
                                        
                                        <!-- Bars -->
                                        <rect x="30" y="70" width="14" height="50" rx="4" class="fill-orange-200" />
                                        <rect x="52" y="50" width="14" height="70" rx="4" class="fill-orange-300" />
                                        <rect x="74" y="80" width="14" height="40" rx="4" class="fill-orange-200" />
                                        <rect x="96" y="40" width="14" height="80" rx="4" class="fill-orange-400" />
                                        <rect x="118" y="60" width="14" height="60" rx="4" class="fill-orange-300" />
                                        <rect x="140" y="30" width="14" height="90" rx="4" class="fill-[#ea580c]" />
                                        <rect x="162" y="45" width="14" height="75" rx="4" class="fill-orange-400" />
                                        <rect x="184" y="65" width="14" height="55" rx="4" class="fill-orange-300" />
                                        <rect x="206" y="25" width="14" height="95" rx="4" class="fill-[#ea580c]" />
                                        <rect x="228" y="35" width="14" height="85" rx="4" class="fill-orange-400" />
                                        <rect x="250" y="55" width="14" height="65" rx="4" class="fill-orange-300" />
                                        <rect x="272" y="20" width="14" height="100" rx="4" class="fill-[#ea580c]" />

                                        <!-- Labels -->
                                        <text x="37" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">JAN</text>
                                        <text x="81" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">MAR</text>
                                        <text x="125" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">MEI</text>
                                        <text x="169" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">JUL</text>
                                        <text x="213" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">SEP</text>
                                        <text x="257" y="134" class="text-[7px] fill-slate-400 font-bold" text-anchor="middle">NOV</text>
                                    </svg>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- RIGHT COLUMN (DONUT & RECENT LOGS) -->
                    <div class="lg:col-span-4 space-y-6">

                        <!-- CARD: DISTRIBUSI USER (DONUT CHART) -->
                        <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] flex flex-col items-center">
                            <div class="w-full text-left">
                                <h3 class="text-base font-bold text-slate-800 tracking-tight">Distribusi User</h3>
                                <p class="text-xs text-slate-400 font-medium mb-3">Berdasarkan role</p>
                            </div>

                            <!-- SVG DONUT CHART -->
                            <div class="relative w-48 h-48 flex items-center justify-center my-2">
                                <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                                    <!-- Background track -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="12" />
                                    
                                    <!-- Donut Segment 1: UMKM (Orange) -->
                                    <circle 
                                        cx="50" 
                                        cy="50" 
                                        r="38" 
                                        fill="none" 
                                        stroke="#ea580c" 
                                        stroke-width="12" 
                                        stroke-dasharray="160 240" 
                                        stroke-linecap="round" 
                                    />

                                    <!-- Donut Segment 2: Pemasok (Sky Blue) -->
                                    <circle 
                                        cx="50" 
                                        cy="50" 
                                        r="38" 
                                        fill="none" 
                                        stroke="#0284c7" 
                                        stroke-width="12" 
                                        stroke-dasharray="60 240" 
                                        stroke-dashoffset="-165" 
                                        stroke-linecap="round" 
                                    />
                                </svg>

                                <!-- Center text -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                    <span class="text-[10px] font-semibold text-slate-400 block leading-none">Total Users</span>
                                    <span class="text-2xl font-extrabold text-slate-900 mt-1 leading-none">{{ $totalUmkm + $totalSuppliers }}</span>
                                </div>
                            </div>

                            <!-- Dot indicators -->
                            <div class="flex items-center justify-center gap-2 mt-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#ea580c] shadow-sm" title="UMKM"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shadow-sm" title="Pemasok"></span>
                            </div>
                        </div>

                        <!-- CARD: AKTIVITAS TERBARU (LOGS LIST) -->
                        <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-800 tracking-tight">Aktivitas Terbaru</h3>
                                <p class="text-xs text-slate-400 font-medium">Real-time updates</p>
                            </div>

                            <!-- LOGS LIST -->
                            <div class="space-y-3 pt-1">
                                @foreach(array_slice($systemLogs, 0, 4) as $log)
                                    <div class="flex items-start gap-3 pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0 shadow-inner">
                                            {{ strtoupper(substr($log['actor'], 0, 2)) }}
                                        </div>
                                        <div class="overflow-hidden flex-1">
                                            <h4 class="text-xs font-bold text-slate-900 leading-snug truncate">{{ $log['actor'] }}</h4>
                                            <p class="text-[11px] text-slate-500 font-medium truncate">{{ $log['action'] }}</p>
                                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5 flex items-center gap-1">
                                                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ $log['timestamp'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- FULL WIDTH CTA BUTTON -->
                            <button 
                                type="button"
                                @click="adminTab = 'monitoring'" 
                                class="w-full py-3 px-4 bg-orange-50 hover:bg-orange-100 text-[#ea580c] font-bold text-xs rounded-2xl transition flex items-center justify-center gap-2 cursor-pointer border border-orange-100/80">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span>Lihat Semua Log</span>
                            </button>
                        </div>

                    </div>

                </div>

            </div>

            <!-- ========================================================= -->
            <!-- 2. MANAJEMEN PENGGUNA & VERIFIKASI AKUN                   -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'users'" x-cloak class="space-y-6">
                <div>
                    <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">VERIFIKASI & MODERASI AKUN</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Manajemen Pengguna System</h2>
                </div>

                <!-- SEARCH & ROLE FILTER -->
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] flex flex-col md:flex-row justify-between gap-4">
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            x-model="userSearch"
                            placeholder="Cari berdasarkan nama akun atau email..."
                            class="w-full pl-11 pr-4 py-2 text-xs font-semibold bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-500 text-slate-900"
                        />
                    </div>

                    <div class="flex items-center gap-2 text-xs font-bold">
                        <span class="text-slate-500">Tipe Akun:</span>
                        <select 
                            x-model="userFilterRole"
                            class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none text-slate-900">
                            <option value="ALL">Semua Akun</option>
                            <option value="UMKM">UMKM Kuliner</option>
                            <option value="PEMASOK">Pemasok Bahan Baku</option>
                        </select>
                    </div>
                </div>

                <!-- TABLE MODERASI AKUN -->
                <div class="bg-white rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left font-medium">
                            <thead class="bg-slate-50 font-bold uppercase text-slate-500 text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="p-4">ID User</th>
                                    <th class="p-4">Nama Usaha / Akun</th>
                                    <th class="p-4">Tipe Akun</th>
                                    <th class="p-4">Kontak & Email</th>
                                    <th class="p-4">Status Akun</th>
                                    <th class="p-4 text-center">Aksi Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-sans">
                                @forelse($users as $u)
                                    <tr 
                                        x-show="(!userSearch || ('{{ strtolower($u['name']) }}'.includes(userSearch.toLowerCase()) || '{{ strtolower($u['email']) }}'.includes(userSearch.toLowerCase()))) && (userFilterRole === 'ALL' || userFilterRole === '{{ $u['type'] }}')"
                                        class="hover:bg-slate-50/60 transition">
                                        <td class="p-4 text-orange-600 font-extrabold font-mono">{{ $u['id'] }}</td>
                                        <td class="p-4 font-bold text-sm text-slate-900">{{ $u['name'] }}</td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $u['type'] === 'UMKM' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                                {{ $u['type'] }}
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <span class="block text-slate-900 font-semibold">{{ $u['email'] }}</span>
                                            <span class="text-[11px] text-slate-500 font-sans block">{{ $u['phone'] }}</span>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-block {{ $u['status'] === 'AKTIF' ? 'bg-emerald-100 text-emerald-800' : ($u['status'] === 'PENDING_VERIFIKASI' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                {{ $u['status'] }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-center space-x-1.5 whitespace-nowrap">
                                            @if($u['status'] === 'PENDING_VERIFIKASI')
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="AKTIF">
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-[10px] font-bold inline-flex items-center gap-1 cursor-pointer transition shadow-sm">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span>Verifikasi & Aktifkan</span>
                                                    </button>
                                                </form>
                                            @elseif($u['status'] === 'AKTIF')
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="NONAKTIF">
                                                    <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 px-3 py-1.5 rounded-xl text-[10px] font-bold inline-flex items-center gap-1 cursor-pointer transition">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        <span>Nonaktifkan</span>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="AKTIF">
                                                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-xl text-[10px] font-bold transition cursor-pointer shadow-sm">
                                                        Aktifkan Kembali
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-slate-400 font-sans">Belum ada data pengguna mitra.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 3. MASTER DATA KOMODITAS & SATUAN                         -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'master'" x-cloak class="space-y-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">STANDARISASI DATA SISTEM</span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Master Data Komoditas & Satuan</h2>
                    </div>
                    <a href="{{ route('admin.commodities.index') }}" class="bg-[#ea580c] hover:bg-orange-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition self-start sm:self-auto flex items-center gap-2">
                        <span>🌾 Ke Tabel Komoditas Lengkap</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <!-- TABEL MASTER KATEGORI -->
                <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="font-bold text-lg text-slate-900">Kategori Komoditas Bahan Baku</h3>
                            <p class="text-xs font-medium text-slate-400">Standar kategori yang digunakan di katalog UMKM & Pemasok</p>
                        </div>
                        <button 
                            type="button"
                            @click="isCategoryModalOpen = true" 
                            class="bg-[#ea580c] hover:bg-orange-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm cursor-pointer transition">
                            + Tambah Kategori Baru
                        </button>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 font-sans text-xs">
                        @forelse($categories as $cat)
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/60 space-y-2 flex justify-between items-start">
                                <div>
                                    <span class="text-[10px] text-orange-600 font-mono font-extrabold block">{{ $cat['code'] }}</span>
                                    <h4 class="font-extrabold text-base text-slate-900">{{ $cat['name'] }}</h4>
                                    <p class="text-[11px] text-slate-500 font-medium">{{ $cat['description'] }}</p>
                                    <span class="text-[10px] text-slate-400 block pt-1 font-semibold">Dipakai oleh {{ $cat['totalCommodities'] }} Komoditas</span>
                                </div>
                                <form action="{{ route('admin.categories.destroy', $cat['id']) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold p-1 cursor-pointer" title="Hapus Kategori">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-slate-400 sm:col-span-2">Belum ada kategori komoditas tersimpan.</p>
                        @endforelse
                    </div>
                </div>

                <!-- TABEL MASTER SATUAN -->
                <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="font-bold text-lg text-slate-900">Satuan Bahan Baku</h3>
                            <p class="text-xs font-medium text-slate-400">Simbol unit pengukuran baku (kg, liter, gram, ikat, dll)</p>
                        </div>
                        <button 
                            type="button"
                            @click="isUnitModalOpen = true" 
                            class="bg-slate-900 hover:bg-black text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm cursor-pointer transition">
                            + Tambah Satuan Baru
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left font-medium">
                            <thead class="bg-slate-50 font-bold uppercase text-slate-500 text-[10px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="p-3">Nama Satuan</th>
                                    <th class="p-3">Simbol Unit</th>
                                    <th class="p-3">Tipe Ukuran</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-sans">
                                @forelse($units as $u)
                                    <tr>
                                        <td class="p-3 font-bold text-slate-900">{{ $u['name'] }}</td>
                                        <td class="p-3 text-orange-600 font-extrabold font-mono">{{ $u['symbol'] }}</td>
                                        <td class="p-3 text-slate-600">{{ $u['type'] }}</td>
                                        <td class="p-3 text-center">
                                            <form action="{{ route('admin.units.destroy', $u['id']) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold p-1 cursor-pointer" title="Hapus Satuan">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-4 text-center text-slate-400">Belum ada satuan baku tersimpan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 4. MONITORING SISTEM & TRANSAKSI                          -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'monitoring'" x-cloak class="space-y-6">
                <div>
                    <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">AUDIT & SYSTEM LOGS</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Monitoring Sistem & Aktivitas Transaksi</h2>
                </div>

                <div class="bg-white p-6 rounded-[1.75rem] border border-gray-100 shadow-[0_10px_35px_rgba(0,0,0,0.03)] space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-900">Log Aktivitas Real-Time Sistem</h3>
                        <span class="bg-emerald-50 text-emerald-700 text-[11px] font-bold px-3 py-1 rounded-full border border-emerald-200">
                            Status Audit: AKTIF
                        </span>
                    </div>

                    <div class="bg-slate-900 text-slate-200 p-5 rounded-2xl border border-slate-800 font-mono text-xs space-y-3 shadow-inner">
                        @foreach($systemLogs as $log)
                            <div class="border-b border-slate-800 pb-3 space-y-1 last:border-0 last:pb-0">
                                <div class="flex justify-between text-orange-400 text-[11px]">
                                    <span>{{ $log['id'] }} — {{ $log['timestamp'] }}</span>
                                    <span class="text-emerald-400">STATUS: AUDITED</span>
                                </div>
                                <p class="text-white font-bold">{{ $log['actor'] }}</p>
                                <p class="text-slate-400 text-[11px]">{{ $log['action'] }}: <span class="text-amber-300">{{ $log['detail'] }}</span></p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL TAMBAH KATEGORI                                     -->
            <!-- ========================================================= -->
            <div 
                x-show="isCategoryModalOpen" 
                x-cloak 
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div 
                    @click.away="isCategoryModalOpen = false"
                    class="bg-white max-w-md w-full rounded-[1.75rem] border border-gray-100 p-6 space-y-5 shadow-2xl">
                    <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                        <h3 class="text-xl font-extrabold text-slate-900">Tambah Kategori Master</h3>
                        <button type="button" @click="isCategoryModalOpen = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl leading-none cursor-pointer">&times;</button>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                        @csrf
                        <div>
                            <label class="block text-slate-700 mb-1">Nama Kategori Komoditas:</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Contoh: Bumbu Dapur" 
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                            />
                        </div>
                        <div>
                            <label class="block text-slate-700 mb-1">Deskripsi Ringkas:</label>
                            <textarea 
                                name="description" 
                                rows="3" 
                                placeholder="Tuliskan komoditas apa saja yang masuk kategori ini..." 
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="isCategoryModalOpen = false" 
                                class="w-1/2 border border-slate-200 text-slate-700 py-3 rounded-xl font-bold hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="w-1/2 bg-[#ea580c] hover:bg-orange-600 text-white py-3 rounded-xl font-bold shadow-md transition cursor-pointer">
                                Simpan Kategori →
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL TAMBAH SATUAN                                       -->
            <!-- ========================================================= -->
            <div 
                x-show="isUnitModalOpen" 
                x-cloak 
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div 
                    @click.away="isUnitModalOpen = false"
                    class="bg-white max-w-md w-full rounded-[1.75rem] border border-gray-100 p-6 space-y-5 shadow-2xl">
                    <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                        <h3 class="text-xl font-extrabold text-slate-900">Tambah Satuan Master</h3>
                        <button type="button" @click="isUnitModalOpen = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl leading-none cursor-pointer">&times;</button>
                    </div>

                    <form action="{{ route('admin.units.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                        @csrf
                        <div>
                            <label class="block text-slate-700 mb-1">Nama Satuan:</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Contoh: Kilogram" 
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                            />
                        </div>
                        <div>
                            <label class="block text-slate-700 mb-1">Simbol / Singkatan Unit:</label>
                            <input 
                                type="text" 
                                name="symbol" 
                                required 
                                placeholder="Contoh: kg" 
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                            />
                        </div>
                        <div>
                            <label class="block text-slate-700 mb-1">Deskripsi / Tipe Ukuran:</label>
                            <input 
                                type="text" 
                                name="description" 
                                placeholder="Contoh: Berat, Volume, Kuantitas" 
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500" 
                            />
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="isUnitModalOpen = false" 
                                class="w-1/2 border border-slate-200 text-slate-700 py-3 rounded-xl font-bold hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="w-1/2 bg-[#ea580c] hover:bg-orange-600 text-white py-3 rounded-xl font-bold shadow-md transition cursor-pointer">
                                Simpan Satuan →
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>

    </div>

    @include('partials.action-confirm-modal')

</body>
</html>
