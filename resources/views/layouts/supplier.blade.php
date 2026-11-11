<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SORAI — Portal Pemasok B2B')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root:root, html, body {
            --font-sans: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-display: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-mono: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-family: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after,
        input, button, select, textarea, option, table, th, td, code, pre,
        .font-sans, .font-serif, .font-mono, .font-mono-code, .display-poster {
            font-family: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif !important;
        }
        .font-mono, .font-mono-code, .tabular-nums {
            font-variant-numeric: tabular-nums;
        }
        [x-cloak] { display: none !important; }
        @media (max-width: 1023px) {
            #supplier-sidebar:not(.is-mobile-open) {
                transform: translateX(-100%);
            }
        }
        @media (min-width: 1024px) {
            html.supplier-sidebar-hidden #supplier-sidebar {
                transform: translateX(-100%);
            }
            html.supplier-sidebar-hidden #supplier-workspace {
                padding-left: 0;
            }
        }
        html.supplier-sidebar-hidden .sidebar-icon-open { display: none; }
        html:not(.supplier-sidebar-hidden) .sidebar-icon-closed { display: none; }
    </style>
    <script>
        try {
            if (window.localStorage.getItem('sorai_supplier_sidebar_hidden') === 'true') {
                document.documentElement.classList.add('supplier-sidebar-hidden');
            }
        } catch (e) {}
    </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen selection:bg-orange-100 selection:text-orange-900"
      x-data="{
          mobileNavOpen: false,
          sidebarHidden: (() => { try { return window.localStorage.getItem('sorai_supplier_sidebar_hidden') === 'true'; } catch (e) { return false; } })(),
          toggleSidebar() {
              this.sidebarHidden = !this.sidebarHidden;
              try { window.localStorage.setItem('sorai_supplier_sidebar_hidden', this.sidebarHidden ? 'true' : 'false'); } catch (e) {}
              document.documentElement.classList.toggle('supplier-sidebar-hidden', this.sidebarHidden);
          }
      }">

    @php
        $supplierCompany = auth()->user()?->supplierProfile?->company_name ?? 'Pemasok';
        $activeSectionLabel = match (true) {
            request()->routeIs('supplier.dashboard') => 'Dashboard & Analisis',
            request()->routeIs('supplier.pengajuan-masuk.*') => 'Pengajuan Masuk',
            request()->routeIs('supplier.pengiriman.*') => 'Pesanan & Pengiriman',
            request()->routeIs('supplier.katalog.create') => 'Kelola Stok / Tambah Komoditas',
            request()->routeIs('supplier.katalog.edit') => 'Kelola Stok / Edit Komoditas',
            request()->routeIs('supplier.katalog.*') => 'Kelola Stok',
            request()->routeIs('supplier.supply-planning.*') || request()->routeIs('supplier.demand-planning.*') => 'Perencanaan Stok & Demand',
            request()->routeIs('supplier.contract-monitoring.*') => 'Contract Monitoring',
            request()->routeIs('supplier.riwayat-transaksi.*') => 'Riwayat Transaksi',
            default => 'Panel Pemasok',
        };
    @endphp

    {{-- Mobile Drawer Backdrop --}}
    <div x-cloak
         x-show="mobileNavOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileNavOpen = false"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-30 lg:hidden"></div>

    {{-- Sidebar Navigation --}}
    <aside id="supplier-sidebar"
           class="fixed inset-y-0 left-0 z-40 w-64 bg-[#161922] text-white flex flex-col justify-between border-r border-slate-800/80 transform transition-transform duration-200 ease-out"
           :class="{ 'is-mobile-open translate-x-0': mobileNavOpen }">

        {{-- Brand Header --}}
        <div class="px-5 pt-6 pb-5 border-b border-white/[0.06] flex items-center justify-between gap-3">
            <a href="{{ route('supplier.dashboard') }}" class="flex items-center gap-3 min-w-0">
                <div class="bg-[#ea580c] text-white rounded-xl w-9 h-9 flex items-center justify-center font-extrabold text-base shadow-sm shrink-0">
                    S
                </div>
                <div class="min-w-0">
                    <h1 class="font-extrabold text-lg tracking-tight text-white leading-none">SORAI</h1>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest block mt-1">Portal Pemasok</span>
                </div>
            </a>

            <div class="flex items-center gap-1 shrink-0">
                <button type="button"
                        @click="toggleSidebar()"
                        class="hidden lg:inline-flex items-center justify-center p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
                        title="Sembunyikan Sidebar"
                        aria-label="Sembunyikan Sidebar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>

                <button type="button"
                        @click="mobileNavOpen = false"
                        class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
                        aria-label="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Navigation Groups --}}
        <div class="flex-1 overflow-y-auto px-3.5 py-5 space-y-6">

            {{-- Group 1: Operasional & Order --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Operasional
                </p>
                <nav class="space-y-1">
                    <a href="{{ route('supplier.dashboard') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.dashboard') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="truncate">Dashboard &amp; Analisis</span>
                    </a>

                    <a href="{{ route('supplier.pengajuan-masuk.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.pengajuan-masuk.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate">Pengajuan Masuk</span>
                    </a>

                    <a href="{{ route('supplier.pengiriman.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.pengiriman.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="truncate">Pesanan &amp; Pengiriman</span>
                    </a>
                </nav>
            </div>

            {{-- Group 2: Katalog & Pasokan --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Katalog &amp; Pasokan
                </p>
                <nav class="space-y-1">
                    <a href="{{ route('supplier.katalog.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.katalog.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="truncate">Kelola Stok</span>
                    </a>

                    <a href="{{ route('supplier.supply-planning.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.supply-planning.*') || request()->routeIs('supplier.demand-planning.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="truncate">Perencanaan Demand</span>
                    </a>
                </nav>
            </div>

            {{-- Group 3: Kemitraan & Keuangan --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Kemitraan &amp; Keuangan
                </p>
                <nav class="space-y-1">
                    <a href="{{ route('supplier.contract-monitoring.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.contract-monitoring.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">Contract Monitoring</span>
                    </a>

                    <a href="{{ route('supplier.riwayat-transaksi.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors {{ request()->routeIs('supplier.riwayat-transaksi.*') ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span class="truncate">Riwayat Transaksi</span>
                    </a>
                </nav>
            </div>

        </div>

        {{-- Profile & Logout Footer --}}
        <div class="p-3.5 border-t border-white/[0.06] space-y-2.5 bg-[#12151d]">
            <div class="bg-white/[0.04] p-3 rounded-xl flex items-center gap-3 border border-white/[0.07]">
                <div class="w-9 h-9 rounded-lg bg-[#ea580c]/20 text-orange-300 border border-[#ea580c]/30 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ strtoupper(substr($supplierCompany, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white truncate">{{ $supplierCompany }}</p>
                    <p class="text-[10px] text-emerald-400 font-medium inline-flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Pemasok Terverifikasi</span>
                    </p>
                </div>
            </div>

            <a href="{{ route('logout') }}"
               @click.prevent="$dispatch('open-logout-modal')"
               onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
               class="w-full py-2.5 px-3.5 bg-white/[0.05] hover:bg-rose-600/90 text-slate-300 hover:text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition-colors border border-white/[0.06] hover:border-rose-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    {{-- Main Workspace Column --}}
    <div id="supplier-workspace"
         class="lg:pl-64 transition-[padding] duration-200 ease-out flex flex-col min-h-screen min-w-0">

        {{-- Mobile Top Bar --}}
        <header class="sticky top-0 z-20 lg:hidden bg-white/95 backdrop-blur-md border-b border-slate-200 px-4 h-14 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                        @click="mobileNavOpen = true"
                        class="p-2 -ml-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer"
                        aria-label="Buka Menu Navigasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-[#ea580c] text-white text-xs font-extrabold flex items-center justify-center shrink-0">S</span>
                    <span class="text-sm font-bold text-slate-900 truncate">{{ $activeSectionLabel }}</span>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-slate-500 truncate max-w-[140px]">{{ $supplierCompany }}</span>
        </header>

        {{-- Desktop Top Context Bar --}}
        <header class="hidden lg:flex items-center justify-between px-6 xl:px-8 h-14 bg-white/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-20">
            <div class="flex items-center gap-3 text-xs min-w-0">
                <button type="button"
                        @click="toggleSidebar()"
                        :title="sidebarHidden ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                        :aria-expanded="(!sidebarHidden).toString()"
                        aria-controls="supplier-sidebar"
                        data-cy="toggle-supplier-sidebar"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4v16"/>
                        <path class="sidebar-icon-closed" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 9l3 3-3 3"/>
                        <path class="sidebar-icon-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 9l-3 3 3 3"/>
                    </svg>
                </button>

                <a x-show="sidebarHidden"
                   x-cloak
                   href="{{ route('supplier.dashboard') }}"
                   class="inline-flex items-center gap-2 pr-2 border-r border-slate-200 shrink-0">
                    <span class="w-6 h-6 rounded-md bg-[#ea580c] text-white text-[11px] font-extrabold flex items-center justify-center">S</span>
                    <span class="font-bold text-slate-900 tracking-tight">SORAI</span>
                </a>

                <div class="flex items-center gap-2 min-w-0">
                    <a href="{{ route('supplier.dashboard') }}" class="font-medium text-slate-400 hover:text-slate-700 transition-colors shrink-0">
                        Portal Pemasok
                    </a>
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="font-semibold text-slate-800 truncate">{{ $activeSectionLabel }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-slate-500 tabular-nums">
                    {{ now()->locale('id')->translatedFormat('l, d M Y') }}
                </span>
                <span class="h-4 w-px bg-slate-200"></span>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="max-w-[200px] truncate">{{ $supplierCompany }}</span>
                </span>
            </div>
        </header>

        {{-- Page Body --}}
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            @include('partials.flash-notification')
            @yield('content')
        </main>
    </div>

    @include('partials.logout-modal')

</body>
</html>
