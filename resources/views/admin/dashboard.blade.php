<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body class="min-h-screen flex flex-col bg-bone-canvas text-midnight-ink selection:bg-voltage-lime selection:text-midnight-ink">

    <div 
        x-data="{
            adminTab: '{{ $currentTab }}',
            userSearch: '',
            userFilterRole: 'ALL',
            isCategoryModalOpen: false,
            isUnitModalOpen: false,
            notification: '{{ session('success') ?? session('warning') ?? '' }}',
            clearNotif() { this.notification = ''; }
        }" 
        class="min-h-screen bg-bone-canvas text-midnight-ink flex">

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

                    <!-- Tab Dashboard -->
                    <button 
                        type="button"
                        @click="adminTab = 'dashboard'"
                        :class="adminTab === 'dashboard' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white'"
                        class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-2.5">
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
                        :class="adminTab === 'users' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white'"
                        class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Manajemen Pengguna</span>
                        </div>
                        @if($pendingUsersCount > 0)
                            <span class="bg-voltage-lime text-midnight-ink font-mono text-[10px] px-2 py-0.5 rounded-pill font-extrabold border border-midnight-ink animate-pulse">
                                {{ $pendingUsersCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Tab Master Data Komoditas -->
                    <button 
                        type="button"
                        @click="adminTab = 'master'"
                        :class="adminTab === 'master' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white'"
                        class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Master Data Komoditas</span>
                        </div>
                    </button>

                    <!-- Tab Monitoring Sistem -->
                    <button 
                        type="button"
                        @click="adminTab = 'monitoring'"
                        :class="adminTab === 'monitoring' ? 'bg-sun-orange text-midnight-ink border-2 border-plaster-white shadow-md' : 'text-plaster-white/80 hover:bg-plaster-white/10 hover:text-plaster-white'"
                        class="w-full text-left px-3.5 py-3 rounded-2xl font-bold text-xs transition flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 012 2h2a2 2 0 012-2z" />
                            </svg>
                            <span>Monitoring Sistem</span>
                        </div>
                    </button>
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

            <!-- NOTIFICATION ALERT -->
            <div 
                x-show="notification" 
                x-cloak
                class="bg-emerald-300 text-midnight-ink border-2 border-midnight-ink p-4 rounded-2xl font-mono text-xs font-bold flex justify-between items-center shadow-md animate-bounce">
                <span x-text="notification"></span>
                <button @click="clearNotif()" class="font-extrabold text-sm hover:text-rose-700 cursor-pointer">&times;</button>
            </div>

            <!-- ========================================================= -->
            <!-- 1. DASHBOARD RINGKASAN ADMIN                              -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'dashboard'" x-cloak class="space-y-8">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b-2 border-midnight-ink/20 pb-4">
                    <div>
                        <span class="font-mono text-xs font-bold text-cobalt-signal uppercase">OVERVIEW CONTROL PLATFORM</span>
                        <h2 class="display-poster text-3xl font-extrabold text-midnight-ink uppercase">Dashboard Control Systems</h2>
                    </div>
                    <span class="bg-plaster-white border-2 border-midnight-ink text-xs font-mono font-bold px-4 py-2 rounded-pill shadow-sm self-start sm:self-auto flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Status Platform: NORMAL (OK)
                    </span>
                </div>

                <!-- METRIC CARDS ADMIN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-plaster-white p-5 rounded-card border-3 border-midnight-ink shadow-md space-y-1">
                        <span class="font-mono text-[11px] font-bold text-midnight-ink/60 uppercase">TOTAL UMKM TERDAFTAR</span>
                        <p class="font-display text-2xl font-extrabold text-midnight-ink">{{ $totalUmkm }} Outlet</p>
                        <p class="text-[10px] font-mono text-emerald-700 font-bold">● {{ $activeUmkm }} Terverifikasi Aktif</p>
                    </div>

                    <div class="bg-plaster-white p-5 rounded-card border-3 border-midnight-ink shadow-md space-y-1">
                        <span class="font-mono text-[11px] font-bold text-midnight-ink/60 uppercase">TOTAL PEMASOK TERDAFTAR</span>
                        <p class="font-display text-2xl font-extrabold text-midnight-ink">{{ $totalSuppliers }} Kelompok</p>
                        <p class="text-[10px] font-mono text-emerald-700 font-bold">● {{ $activeSuppliers }} Terverifikasi Aktif</p>
                    </div>

                    <div class="bg-plaster-white p-5 rounded-card border-3 border-midnight-ink shadow-md space-y-1">
                        <span class="font-mono text-[11px] font-bold text-midnight-ink/60 uppercase">MENUNGGU VERIFIKASI</span>
                        <p class="font-display text-2xl font-extrabold text-cobalt-signal">{{ $pendingUsersCount }} Akun</p>
                        <p class="text-[10px] font-mono {{ $pendingUsersCount > 0 ? 'text-amber-700' : 'text-emerald-700' }} font-bold">
                            {{ $pendingUsersCount > 0 ? '⚠️ Perlu Tindakan Moderasi' : '● Semua Akun Terverifikasi' }}
                        </p>
                    </div>

                    <div class="bg-plaster-white p-5 rounded-card border-3 border-midnight-ink shadow-md space-y-1">
                        <span class="font-mono text-[11px] font-bold text-midnight-ink/60 uppercase">KATEGORI KOMODITAS</span>
                        <p class="font-display text-2xl font-extrabold text-cobalt-signal">{{ count($categories) }} Kategori</p>
                        <p class="text-[10px] font-mono text-emerald-700 font-bold">Standar Baku Sistem</p>
                    </div>
                </div>

                <!-- RINGKASAN AKTIVITAS TERBARU -->
                <div class="bg-plaster-white p-6 rounded-card border-3 border-midnight-ink shadow-md space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-display font-extrabold text-lg text-midnight-ink uppercase">Aktivitas Sistem Terbaru</h3>
                        <button @click="adminTab = 'monitoring'" class="text-xs font-bold text-cobalt-signal hover:underline cursor-pointer">
                            Lihat Semua Log &rarr;
                        </button>
                    </div>
                    <div class="space-y-2 font-mono text-xs font-bold">
                        @foreach(array_slice($systemLogs, 0, 3) as $log)
                            <div class="bg-bone-canvas p-3 rounded-xl border border-midnight-ink/30 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                                <div>
                                    <span class="text-cobalt-signal font-extrabold mr-2">[{{ $log['id'] }}]</span>
                                    <span class="text-midnight-ink">{{ $log['actor'] }} — <span class="text-cobalt-signal">{{ $log['action'] }}</span></span>
                                    <span class="text-[11px] text-midnight-ink/60 font-sans block sm:inline sm:ml-2">({{ $log['detail'] }})</span>
                                </div>
                                <span class="text-[10px] text-midnight-ink/60 self-end sm:self-auto flex-shrink-0">{{ $log['timestamp'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. MANAJEMEN PENGGUNA & VERIFIKASI AKUN                   -->
            <!-- ========================================================= -->
            <div x-show="adminTab === 'users'" x-cloak class="space-y-6">
                <div class="border-b-2 border-midnight-ink/20 pb-4">
                    <span class="font-mono text-xs font-bold text-cobalt-signal uppercase">VERIFIKASI & MODERASI AKUN</span>
                    <h2 class="display-poster text-3xl font-extrabold text-midnight-ink uppercase">Manajemen Pengguna System</h2>
                </div>

                <!-- SEARCH & ROLE FILTER -->
                <div class="bg-plaster-white p-4 rounded-card border-3 border-midnight-ink shadow-md flex flex-col md:flex-row justify-between gap-4">
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-3.5 text-midnight-ink/60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            x-model="userSearch"
                            placeholder="Cari berdasarkan nama akun atau email..."
                            class="w-full pl-11 pr-4 py-2.5 text-xs font-bold bg-bone-canvas rounded-pill border-2 border-midnight-ink focus:outline-none focus:ring-2 focus:ring-sun-orange text-midnight-ink"
                        />
                    </div>

                    <div class="flex items-center gap-2 font-mono text-xs font-bold">
                        <span>Tipe Akun:</span>
                        <select 
                            x-model="userFilterRole"
                            class="px-3 py-2 rounded-lg bg-bone-canvas border-2 border-midnight-ink focus:outline-none text-midnight-ink">
                            <option value="ALL">Semua Akun</option>
                            <option value="UMKM">UMKM Kuliner</option>
                            <option value="PEMASOK">Pemasok Bahan Baku</option>
                        </select>
                    </div>
                </div>

                <!-- TABLE MODERASI AKUN -->
                <div class="bg-plaster-white rounded-card border-3 border-midnight-ink overflow-hidden shadow-md">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left font-bold">
                            <thead class="bg-bone-canvas font-mono uppercase border-b-2 border-midnight-ink">
                                <tr>
                                    <th class="p-4">ID User</th>
                                    <th class="p-4">Nama Usaha / Akun</th>
                                    <th class="p-4">Tipe Akun</th>
                                    <th class="p-4">Kontak WhatsApp & Email</th>
                                    <th class="p-4">Status Akun</th>
                                    <th class="p-4 text-center">Aksi Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-midnight-ink/20 font-mono">
                                @forelse($users as $u)
                                    <tr 
                                        x-show="(!userSearch || ('{{ strtolower($u['name']) }}'.includes(userSearch.toLowerCase()) || '{{ strtolower($u['email']) }}'.includes(userSearch.toLowerCase()))) && (userFilterRole === 'ALL' || userFilterRole === '{{ $u['type'] }}')"
                                        class="hover:bg-bone-canvas/30 transition">
                                        <td class="p-4 text-cobalt-signal font-extrabold">{{ $u['id'] }}</td>
                                        <td class="p-4 font-display font-extrabold text-sm text-midnight-ink">{{ $u['name'] }}</td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded-pill text-[10px] font-extrabold border border-midnight-ink {{ $u['type'] === 'UMKM' ? 'bg-amber-200' : 'bg-voltage-lime' }}">
                                                {{ $u['type'] }}
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <span class="block text-midnight-ink">{{ $u['email'] }}</span>
                                            <span class="text-[10px] text-midnight-ink/60 font-sans block">{{ $u['phone'] }}</span>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-pill text-[10px] font-extrabold border border-midnight-ink block w-fit {{ $u['status'] === 'AKTIF' ? 'bg-emerald-300' : ($u['status'] === 'PENDING_VERIFIKASI' ? 'bg-amber-300' : 'bg-rose-300') }}">
                                                {{ $u['status'] }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-center space-x-1.5 whitespace-nowrap">
                                            @if($u['status'] === 'PENDING_VERIFIKASI')
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="AKTIF">
                                                    <button type="submit" class="bg-emerald-300 hover:bg-emerald-400 border border-midnight-ink px-3 py-1 rounded-pill text-[10px] font-extrabold inline-flex items-center gap-1 cursor-pointer">
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
                                                    <button type="submit" class="bg-rose-200 hover:bg-rose-300 text-rose-900 border border-midnight-ink px-3 py-1 rounded-pill text-[10px] font-extrabold inline-flex items-center gap-1 cursor-pointer">
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
                                                    <button type="submit" class="bg-voltage-lime hover:bg-amber-300 border border-midnight-ink px-3 py-1 rounded-pill text-[10px] font-extrabold cursor-pointer">
                                                        Aktifkan Kembali
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-midnight-ink/60 font-sans">Belum ada data pengguna mitra.</td>
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
                <div class="border-b-2 border-midnight-ink/20 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="font-mono text-xs font-bold text-cobalt-signal uppercase">STANDARISASI DATA SISTEM</span>
                        <h2 class="display-poster text-3xl font-extrabold text-midnight-ink uppercase">Master Data Komoditas & Satuan</h2>
                    </div>
                    <a href="{{ route('admin.commodities.index') }}" class="bg-midnight-ink text-voltage-lime border-2 border-midnight-ink px-4 py-2 rounded-pill text-xs font-extrabold shadow-sm hover:bg-black transition self-start sm:self-auto">
                        🌾 Ke Tabel Komoditas Lengkap &rarr;
                    </a>
                </div>

                <!-- TABEL MASTER KATEGORI -->
                <div class="bg-plaster-white p-6 rounded-card border-3 border-midnight-ink shadow-md space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-midnight-ink/15 pb-3">
                        <div>
                            <h3 class="font-display font-extrabold text-lg text-midnight-ink uppercase">Kategori Komoditas Bahan Baku</h3>
                            <p class="text-xs font-bold text-midnight-ink/60">Standar kategori yang digunakan di katalog UMKM & Pemasok</p>
                        </div>
                        <button 
                            type="button"
                            @click="isCategoryModalOpen = true" 
                            class="bg-sun-orange hover:bg-amber-600 text-midnight-ink border-2 border-midnight-ink px-4 py-2 rounded-pill text-xs font-extrabold shadow-sm cursor-pointer transition">
                            + Tambah Kategori Baru
                        </button>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 font-mono text-xs font-bold">
                        @forelse($categories as $cat)
                            <div class="bg-bone-canvas p-4 rounded-2xl border-2 border-midnight-ink space-y-2 flex justify-between items-start">
                                <div>
                                    <span class="text-[10px] text-cobalt-signal font-extrabold block">{{ $cat['code'] }}</span>
                                    <h4 class="font-display font-extrabold text-base text-midnight-ink uppercase">{{ $cat['name'] }}</h4>
                                    <p class="text-[11px] font-sans text-midnight-ink/70 font-medium">{{ $cat['description'] }}</p>
                                    <span class="text-[10px] text-cobalt-signal block pt-1">Dipakai oleh {{ $cat['totalCommodities'] }} Komoditas</span>
                                </div>
                                <form action="{{ route('admin.categories.destroy', $cat['id']) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-extrabold cursor-pointer" title="Hapus Kategori">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-midnight-ink/60 sm:col-span-2">Belum ada kategori komoditas tersimpan.</p>
                        @endforelse
                    </div>
                </div>

                <!-- TABEL MASTER SATUAN -->
                <div class="bg-plaster-white p-6 rounded-card border-3 border-midnight-ink shadow-md space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-midnight-ink/15 pb-3">
                        <div>
                            <h3 class="font-display font-extrabold text-lg text-midnight-ink uppercase">Satuan Bahan Baku</h3>
                            <p class="text-xs font-bold text-midnight-ink/60">Simbol unit pengukuran baku (kg, liter, gram, ikat, dll)</p>
                        </div>
                        <button 
                            type="button"
                            @click="isUnitModalOpen = true" 
                            class="bg-voltage-lime hover:bg-amber-300 border-2 border-midnight-ink px-4 py-2 rounded-pill text-xs font-extrabold shadow-sm cursor-pointer transition">
                            + Tambah Satuan Baru
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left font-bold">
                            <thead class="bg-bone-canvas font-mono uppercase border-b-2 border-midnight-ink">
                                <tr>
                                    <th class="p-3">Nama Satuan</th>
                                    <th class="p-3">Simbol Unit</th>
                                    <th class="p-3">Tipe Ukuran</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-midnight-ink/20 font-mono">
                                @forelse($units as $u)
                                    <tr>
                                        <td class="p-3 font-display font-extrabold">{{ $u['name'] }}</td>
                                        <td class="p-3 text-cobalt-signal font-extrabold">{{ $u['symbol'] }}</td>
                                        <td class="p-3">{{ $u['type'] }}</td>
                                        <td class="p-3 text-center">
                                            <form action="{{ route('admin.units.destroy', $u['id']) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-extrabold cursor-pointer" title="Hapus Satuan">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-4 text-center text-midnight-ink/60">Belum ada satuan baku tersimpan.</td>
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
                <div class="border-b-2 border-midnight-ink/20 pb-4">
                    <span class="font-mono text-xs font-bold text-cobalt-signal uppercase">AUDIT & SYSTEM LOGS</span>
                    <h2 class="display-poster text-3xl font-extrabold text-midnight-ink uppercase">Monitoring Sistem & Aktivitas Transaksi</h2>
                </div>

                <div class="bg-plaster-white p-6 rounded-card border-3 border-midnight-ink shadow-md space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-display font-extrabold text-lg text-midnight-ink uppercase">Log Aktivitas Real-Time Sistem</h3>
                        <span class="bg-emerald-100 text-emerald-800 font-mono text-[11px] font-bold px-3 py-1 rounded-pill border border-emerald-300">
                            Status Audit: AKTIF
                        </span>
                    </div>

                    <div class="bg-midnight-ink text-plaster-white p-5 rounded-2xl border-2 border-midnight-ink font-mono text-xs space-y-3 shadow-inner">
                        @foreach($systemLogs as $log)
                            <div class="border-b border-plaster-white/10 pb-3 space-y-1">
                                <div class="flex justify-between text-voltage-lime text-[11px]">
                                    <span>{{ $log['id'] }} — {{ $log['timestamp'] }}</span>
                                    <span class="text-emerald-400">STATUS: AUDITED</span>
                                </div>
                                <p class="text-plaster-white font-extrabold">{{ $log['actor'] }}</p>
                                <p class="text-plaster-white/70 text-[11px]">{{ $log['action'] }}: <span class="text-amber-300">{{ $log['detail'] }}</span></p>
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
                class="fixed inset-0 bg-midnight-ink/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div 
                    @click.away="isCategoryModalOpen = false"
                    class="bg-plaster-white max-w-md w-full rounded-card border-4 border-midnight-ink p-6 space-y-6 shadow-2xl">
                    <div class="flex justify-between items-start border-b-2 border-midnight-ink pb-3">
                        <h3 class="font-display text-2xl font-extrabold text-midnight-ink uppercase">Tambah Kategori Master</h3>
                        <button type="button" @click="isCategoryModalOpen = false" class="text-midnight-ink font-extrabold text-2xl leading-none hover:text-rose-600 cursor-pointer">&times;</button>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                        @csrf
                        <div>
                            <label class="block text-midnight-ink mb-1">Nama Kategori Komoditas:</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Contoh: Bumbu Dapur" 
                                class="w-full px-4 py-3 rounded-lg bg-bone-canvas border-2 border-midnight-ink text-midnight-ink focus:outline-none focus:ring-2 focus:ring-sun-orange" 
                            />
                        </div>
                        <div>
                            <label class="block text-midnight-ink mb-1">Deskripsi Ringkas:</label>
                            <textarea 
                                name="description" 
                                rows="3" 
                                placeholder="Tuliskan komoditas apa saja yang masuk kategori ini..." 
                                class="w-full px-4 py-3 rounded-lg bg-bone-canvas border-2 border-midnight-ink text-midnight-ink focus:outline-none focus:ring-2 focus:ring-sun-orange"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="isCategoryModalOpen = false" 
                                class="w-1/2 border-2 border-midnight-ink text-midnight-ink py-3 rounded-pill font-extrabold hover:bg-gray-100 transition cursor-pointer">
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="w-1/2 bg-sun-orange hover:bg-amber-600 border-2 border-midnight-ink text-midnight-ink py-3 rounded-pill font-extrabold shadow-md transition cursor-pointer">
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
                class="fixed inset-0 bg-midnight-ink/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div 
                    @click.away="isUnitModalOpen = false"
                    class="bg-plaster-white max-w-md w-full rounded-card border-4 border-midnight-ink p-6 space-y-6 shadow-2xl">
                    <div class="flex justify-between items-start border-b-2 border-midnight-ink pb-3">
                        <h3 class="font-display text-2xl font-extrabold text-midnight-ink uppercase">Tambah Satuan Master</h3>
                        <button type="button" @click="isUnitModalOpen = false" class="text-midnight-ink font-extrabold text-2xl leading-none hover:text-rose-600 cursor-pointer">&times;</button>
                    </div>

                    <form action="{{ route('admin.units.store') }}" method="POST" class="space-y-4 text-xs font-bold">
                        @csrf
                        <div>
                            <label class="block text-midnight-ink mb-1">Nama Satuan:</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Contoh: Kilogram" 
                                class="w-full px-4 py-3 rounded-lg bg-bone-canvas border-2 border-midnight-ink text-midnight-ink focus:outline-none focus:ring-2 focus:ring-voltage-lime" 
                            />
                        </div>
                        <div>
                            <label class="block text-midnight-ink mb-1">Simbol / Singkatan Unit:</label>
                            <input 
                                type="text" 
                                name="symbol" 
                                required 
                                placeholder="Contoh: kg" 
                                class="w-full px-4 py-3 rounded-lg bg-bone-canvas border-2 border-midnight-ink text-midnight-ink focus:outline-none focus:ring-2 focus:ring-voltage-lime" 
                            />
                        </div>
                        <div>
                            <label class="block text-midnight-ink mb-1">Deskripsi / Tipe Ukuran:</label>
                            <input 
                                type="text" 
                                name="description" 
                                placeholder="Contoh: Berat, Volume, Kuantitas" 
                                class="w-full px-4 py-3 rounded-lg bg-bone-canvas border-2 border-midnight-ink text-midnight-ink focus:outline-none focus:ring-2 focus:ring-voltage-lime" 
                            />
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="isUnitModalOpen = false" 
                                class="w-1/2 border-2 border-midnight-ink text-midnight-ink py-3 rounded-pill font-extrabold hover:bg-gray-100 transition cursor-pointer">
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="w-1/2 bg-voltage-lime hover:bg-amber-300 border-2 border-midnight-ink text-midnight-ink py-3 rounded-pill font-extrabold shadow-md transition cursor-pointer">
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
