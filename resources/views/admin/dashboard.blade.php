<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SORAI — Control Panel Administrator Systems</title>

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
            #admin-sidebar:not(.is-mobile-open) {
                transform: translateX(-100%);
            }
        }
        @media (min-width: 1024px) {
            html.admin-sidebar-hidden #admin-sidebar {
                transform: translateX(-100%);
            }
            html.admin-sidebar-hidden #admin-workspace {
                padding-left: 0;
            }
        }
        html.admin-sidebar-hidden .admin-sidebar-icon-open { display: none; }
        html:not(.admin-sidebar-hidden) .admin-sidebar-icon-closed { display: none; }
    </style>
    <script>
        try {
            if (window.localStorage.getItem('sorai_admin_sidebar_hidden') === 'true') {
                document.documentElement.classList.add('admin-sidebar-hidden');
            }
        } catch (e) {}
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased selection:bg-orange-100 selection:text-orange-900"
      x-data="{
          mobileNavOpen: false,
          sidebarHidden: (() => { try { return window.localStorage.getItem('sorai_admin_sidebar_hidden') === 'true'; } catch (e) { return false; } })(),
          adminTab: '{{ request()->query('tab', ($errors->any() ? 'master' : $currentTab)) }}',
          chartFilter: 'minggu',
          userSearch: @js($userSearch),
          userFilterRole: @js($userFilterRole),
          userFilterStatus: @js($userFilterStatus),
          isCategoryModalOpen: {{ $errors->any() && old('symbol') === null ? 'true' : 'false' }},
          isUnitModalOpen: {{ $errors->any() && old('symbol') !== null ? 'true' : 'false' }},
          notification: '{{ session('success') ?? session('warning') ?? '' }}',
          errorNotification: '{{ session('error') ?? '' }}',
          toggleSidebar() {
              this.sidebarHidden = !this.sidebarHidden;
              try { window.localStorage.setItem('sorai_admin_sidebar_hidden', this.sidebarHidden ? 'true' : 'false'); } catch (e) {}
              document.documentElement.classList.toggle('admin-sidebar-hidden', this.sidebarHidden);
          },
          setAdminTab(t) {
              this.adminTab = t;
              this.mobileNavOpen = false;
              const url = new URL(window.location);
              url.searchParams.set('tab', t);
              window.history.replaceState({}, '', url);
          },
          get activeTabLabel() {
              return {
                  'dashboard': 'Dashboard Admin',
                  'users': 'Manajemen Pengguna',
                  'master': 'Ringkasan Master Data',
                  'monitoring': 'Monitoring Sistem'
              }[this.adminTab] || 'Dashboard Admin';
          },
          clearNotif() { this.notification = ''; this.errorNotification = ''; },
          init() {
              if (this.notification) {
                  setTimeout(() => { this.notification = ''; }, 3500);
              }
              if (this.errorNotification) {
                  setTimeout(() => { this.errorNotification = ''; }, 4500);
              }
          }
      }">

    @php
        $adminEmail = auth()->user()?->email ?? 'admin@sorai.id';
    @endphp

    {{-- Mobile Drawer Backdrop --}}
    <div x-cloak
         x-show="mobileNavOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileNavOpen = false"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-30 lg:hidden"></div>

    {{-- Sidebar Navigation --}}
    <aside id="admin-sidebar"
           class="fixed inset-y-0 left-0 z-40 w-64 bg-[#161922] text-white flex flex-col justify-between border-r border-slate-800/80 transform transition-transform duration-200 ease-out"
           :class="{ 'is-mobile-open translate-x-0': mobileNavOpen }">

        {{-- Brand Header --}}
        <div class="px-5 pt-6 pb-5 border-b border-white/[0.06] flex items-center justify-between gap-3">
            <a href="{{ route('admin.dashboard') }}"
               @click.prevent="setAdminTab('dashboard')"
               class="flex items-center gap-3 min-w-0">
                <div class="bg-[#ea580c] text-white rounded-xl w-9 h-9 flex items-center justify-center font-extrabold text-base shadow-sm shrink-0">
                    S
                </div>
                <div class="min-w-0">
                    <h1 class="font-extrabold text-lg tracking-tight text-white leading-none">SORAI</h1>
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest block mt-1">Admin Control Hub</span>
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

            {{-- Group 1: Kendali Utama --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Kendali Utama
                </p>
                <nav class="space-y-1">
                    <button type="button"
                            @click="setAdminTab('dashboard')"
                            :class="adminTab === 'dashboard' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]'"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors cursor-pointer text-left">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="truncate">Dashboard Admin</span>
                    </button>

                    <button type="button"
                            @click="setAdminTab('users')"
                            :class="adminTab === 'users' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]'"
                            class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors cursor-pointer text-left">
                        <span class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="truncate">Manajemen Pengguna</span>
                        </span>
                        @if($pendingUsersCount > 0)
                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold tabular-nums shrink-0"
                                  :class="adminTab === 'users' ? 'bg-white/20 text-white' : 'bg-amber-500/20 text-amber-300'">
                                {{ $pendingUsersCount }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>

            {{-- Group 2: Standarisasi Master --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Standarisasi Master
                </p>
                <nav class="space-y-1">
                    <button type="button"
                            @click="setAdminTab('master')"
                            :class="adminTab === 'master' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]'"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors cursor-pointer text-left">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span class="truncate">Ringkasan Master Data</span>
                    </button>

                    <a href="{{ route('admin.commodities.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors text-slate-400 hover:text-white hover:bg-white/[0.06]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="truncate">Komoditas Bahan Baku</span>
                    </a>

                    <a href="{{ route('admin.categories.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors text-slate-400 hover:text-white hover:bg-white/[0.06]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span class="truncate">Kategori Acuan</span>
                    </a>

                    <a href="{{ route('admin.units.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors text-slate-400 hover:text-white hover:bg-white/[0.06]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 18h12l3-18H3zM9 6v18M15 6v18"/>
                        </svg>
                        <span class="truncate">Satuan Baku</span>
                    </a>
                </nav>
            </div>

            {{-- Group 3: Audit & Sistem --}}
            <div class="space-y-1.5">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    Audit &amp; Sistem
                </p>
                <nav class="space-y-1">
                    <button type="button"
                            @click="setAdminTab('monitoring')"
                            :class="adminTab === 'monitoring' ? 'bg-[#ea580c] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/[0.06]'"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13px] font-semibold transition-colors cursor-pointer text-left">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="truncate">Monitoring Sistem</span>
                    </button>
                </nav>
            </div>

        </div>

        {{-- Profile & Logout Footer --}}
        <div class="p-3.5 border-t border-white/[0.06] space-y-2.5 bg-[#12151d]">
            <div class="bg-white/[0.04] p-3 rounded-xl flex items-center gap-3 border border-white/[0.07]">
                <div class="w-9 h-9 rounded-lg bg-[#ea580c]/20 text-orange-300 border border-[#ea580c]/30 flex items-center justify-center font-bold text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white truncate">Super Admin SORAI</p>
                    <p class="text-[10px] text-emerald-400 font-medium inline-flex items-center gap-1 mt-0.5 truncate max-w-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="truncate">{{ $adminEmail }}</span>
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                @csrf
                <button type="submit"
                        class="w-full py-2.5 px-3.5 bg-white/[0.05] hover:bg-rose-600/90 text-slate-300 hover:text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition-colors border border-white/[0.06] hover:border-rose-500 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Workspace Column --}}
    <div id="admin-workspace"
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
                    <span class="text-sm font-bold text-slate-900 truncate" x-text="activeTabLabel">Dashboard Admin</span>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-slate-500 truncate max-w-[140px]">Super Admin</span>
        </header>

        {{-- Desktop Top Context Bar --}}
        <header class="hidden lg:flex items-center justify-between px-6 xl:px-8 h-14 bg-white/85 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-20">
            <div class="flex items-center gap-3 text-xs min-w-0">
                <button type="button"
                        @click="toggleSidebar()"
                        :title="sidebarHidden ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                        :aria-expanded="(!sidebarHidden).toString()"
                        aria-controls="admin-sidebar"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4v16"/>
                        <path class="admin-sidebar-icon-closed" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 9l3 3-3 3"/>
                        <path class="admin-sidebar-icon-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 9l-3 3 3 3"/>
                    </svg>
                </button>

                <a x-show="sidebarHidden"
                   x-cloak
                   href="{{ route('admin.dashboard') }}"
                   @click.prevent="setAdminTab('dashboard')"
                   class="inline-flex items-center gap-2 pr-2 border-r border-slate-200 shrink-0">
                    <span class="w-6 h-6 rounded-md bg-[#ea580c] text-white text-[11px] font-extrabold flex items-center justify-center">S</span>
                    <span class="font-bold text-slate-900 tracking-tight">SORAI</span>
                </a>

                <div class="flex items-center gap-2 min-w-0">
                    <a href="{{ route('admin.dashboard') }}"
                       @click.prevent="setAdminTab('dashboard')"
                       class="font-medium text-slate-400 hover:text-slate-700 transition-colors shrink-0">
                        Control Hub Admin
                    </a>
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="font-semibold text-slate-800 truncate" x-text="activeTabLabel">Dashboard Admin</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-slate-500 tabular-nums">
                    {{ now()->locale('id')->translatedFormat('l, d M Y') }}
                </span>
                <span class="h-4 w-px bg-slate-200"></span>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Super Admin</span>
                </span>
            </div>
        </header>

        {{-- Page Body --}}
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

            {{-- Flash Validation Errors --}}
            @if($errors->any())
                <div class="bg-rose-50/90 border border-rose-200/90 text-rose-900 p-4 rounded-2xl shadow-xs space-y-1.5">
                    <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wide text-rose-700">
                        <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Terdapat Kesalahan Pengisian Formulir:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs font-medium pl-6 space-y-0.5 text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Floating Toast Notification (Success / Warning) --}}
            <div x-show="notification"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                 class="fixed top-5 right-4 sm:right-8 z-50 max-w-sm sm:max-w-md w-full pointer-events-none">
                <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3.5 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between gap-3 shadow-lg pointer-events-auto">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span x-text="notification" class="leading-snug"></span>
                    </div>
                    <button type="button"
                            @click="clearNotif()"
                            class="p-1 rounded-lg hover:bg-white/15 cursor-pointer transition shrink-0"
                            title="Tutup Notifikasi"
                            aria-label="Tutup Notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Floating Toast Error --}}
            <div x-show="errorNotification"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                 class="fixed top-5 right-4 sm:right-8 z-50 max-w-sm sm:max-w-md w-full pointer-events-none">
                <div class="bg-rose-600 border border-rose-500/40 text-white px-4 py-3.5 rounded-2xl font-medium text-xs sm:text-sm flex items-center justify-between gap-3 shadow-lg pointer-events-auto">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span x-text="errorNotification" class="leading-snug"></span>
                    </div>
                    <button type="button"
                            @click="clearNotif()"
                            class="p-1 rounded-lg hover:bg-white/15 cursor-pointer transition shrink-0"
                            title="Tutup Notifikasi"
                            aria-label="Tutup Notifikasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- 1. DASHBOARD OVERVIEW                                     --}}
            {{-- ========================================================= --}}
            <div x-show="adminTab === 'dashboard'" x-cloak class="space-y-6">

                {{-- Page Header --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Control Panel Sistem</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Dashboard Overview</h2>
                        <p class="text-xs sm:text-sm text-slate-500">Ringkasan mitra terdaftar, moderasi verifikasi, dan aktivitas sistem SORAI hari ini.</p>
                    </div>
                    <div class="inline-flex items-center gap-2.5 bg-white border border-slate-200/80 rounded-xl px-3.5 py-2 shadow-2xs self-start sm:self-auto tabular-nums">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block leading-none">Terakhir diperbarui</span>
                            <span class="text-xs font-bold text-slate-800 mt-1 block leading-none">{{ date('d M Y, H:i') }} WIB</span>
                        </div>
                    </div>
                </div>

                {{-- Top KPI Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 tabular-nums">

                    {{-- Card 1: Total UMKM --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-slate-500 block">Total UMKM</span>
                                <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight mt-1">{{ $totalUmkm }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-orange-50 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-orange-50/90 text-[#c2410c] border border-orange-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                            <span>{{ $activeUmkm }} Mitra Aktif</span>
                        </div>
                    </div>

                    {{-- Card 2: Total Pemasok --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-slate-500 block">Total Pemasok</span>
                                <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight mt-1">{{ $totalSuppliers }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                            <span>{{ $activeSuppliers }} Pemasok Aktif</span>
                        </div>
                    </div>

                    {{-- Card 3: Menunggu Verifikasi --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-slate-500 block">Menunggu Verifikasi</span>
                                <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight mt-1">{{ $pendingUsersCount }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl {{ $pendingUsersCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        @if($pendingUsersCount > 0)
                            <button type="button"
                                    @click="setAdminTab('users')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/70 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                </svg>
                                <span>Perlu Moderasi &bull; Tinjau</span>
                            </button>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Semua Terverifikasi</span>
                            </div>
                        @endif
                    </div>

                    {{-- Card 4: Kategori Komoditas --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-xs font-semibold text-slate-500 block">Kategori Komoditas</span>
                                <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight mt-1">{{ $totalCategories }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/70">
                            <span>Standar Baku &bull; {{ $totalCommodities }} Komoditas</span>
                        </div>
                    </div>

                </div>

                {{-- Main Analytics Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    {{-- Left Column (8 Cols) --}}
                    <div class="lg:col-span-8 space-y-6">

                        {{-- Wave Line Chart Card --}}
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight" x-text="chartFilter === 'minggu' ? 'Tren Aktivitas Minggu Ini' : 'Tren Aktivitas Bulanan ({{ now()->year }})'">Tren Aktivitas Minggu Ini</h3>
                                    <p class="text-xs text-slate-500 mt-0.5" x-text="chartFilter === 'minggu' ? 'Monitoring aktivitas pesanan & pendaftaran harian 7 hari terakhir' : 'Volume pertumbuhan aktivitas pendaftaran per bulan di tahun berjalan'">Monitoring aktivitas harian pengguna</p>
                                </div>
                                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/70 self-start sm:self-auto text-xs font-semibold">
                                    <button type="button"
                                            @click="chartFilter = 'minggu'"
                                            :class="chartFilter === 'minggu' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-900'"
                                            class="px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                                        Minggu
                                    </button>
                                    <button type="button"
                                            @click="chartFilter = 'bulan'"
                                            :class="chartFilter === 'bulan' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-900'"
                                            class="px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                                        Bulan
                                    </button>
                                </div>
                            </div>

                            <div class="relative w-full overflow-hidden pt-2">
                                <svg viewBox="0 0 700 240" class="w-full h-auto overflow-visible">
                                    <defs>
                                        <linearGradient id="soraiOrangeGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#ea580c" stop-opacity="0.22" />
                                            <stop offset="100%" stop-color="#ea580c" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <line x1="40" y1="20" x2="680" y2="20" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="24" class="text-[10px] fill-slate-400" text-anchor="end" x-text="chartFilter === 'minggu' ? '{{ $maxWeekly }}' : '{{ $maxGrowth }}'"></text>

                                    <line x1="40" y1="60" x2="680" y2="60" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="64" class="text-[10px] fill-slate-400" text-anchor="end" x-text="chartFilter === 'minggu' ? '{{ max(1, round($maxWeekly * 0.75)) }}' : '{{ max(1, round($maxGrowth * 0.75)) }}'"></text>

                                    <line x1="40" y1="100" x2="680" y2="100" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="104" class="text-[10px] fill-slate-400" text-anchor="end" x-text="chartFilter === 'minggu' ? '{{ max(1, round($maxWeekly * 0.5)) }}' : '{{ max(1, round($maxGrowth * 0.5)) }}'"></text>

                                    <line x1="40" y1="140" x2="680" y2="140" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1.5" />
                                    <text x="25" y="144" class="text-[10px] fill-slate-400" text-anchor="end" x-text="chartFilter === 'minggu' ? '{{ max(1, round($maxWeekly * 0.25)) }}' : '{{ max(1, round($maxGrowth * 0.25)) }}'"></text>

                                    <line x1="40" y1="200" x2="680" y2="200" stroke="#e2e8f0" stroke-width="1.5" />
                                    <text x="25" y="204" class="text-[10px] fill-slate-400" text-anchor="end">0</text>

                                    <g x-show="chartFilter === 'minggu'" x-transition>
                                        @if(!empty($weeklyCurve['area']))
                                            <path d="{!! $weeklyCurve['area'] !!}" fill="url(#soraiOrangeGrad)" />
                                            <path d="{!! $weeklyCurve['line'] !!}" fill="none" stroke="#ea580c" stroke-width="2.75" stroke-linecap="round" />
                                        @endif

                                        @foreach($weeklySvgPoints as $pt)
                                            <g class="cursor-pointer group">
                                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="4.5" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" class="group-hover:r-6 transition-all">
                                                    <title>{{ $pt['date'] }} ({{ $pt['label'] }}): {{ $pt['count'] }} aktivitas</title>
                                                </circle>
                                                <text x="{{ $pt['x'] }}" y="224" class="text-[10px] fill-slate-500 font-semibold" text-anchor="middle">{{ $pt['label'] }}</text>
                                            </g>
                                        @endforeach
                                    </g>

                                    <g x-show="chartFilter === 'bulan'" x-cloak x-transition>
                                        @if(!empty($monthlyCurve['area']))
                                            <path d="{!! $monthlyCurve['area'] !!}" fill="url(#soraiOrangeGrad)" />
                                            <path d="{!! $monthlyCurve['line'] !!}" fill="none" stroke="#ea580c" stroke-width="2.75" stroke-linecap="round" />
                                        @endif

                                        @foreach($monthlySvgPoints as $pt)
                                            <g class="cursor-pointer group">
                                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="3.5" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" class="group-hover:r-5 transition-all">
                                                    <title>{{ $pt['label'] }}: {{ $pt['count'] }} pendaftaran</title>
                                                </circle>
                                                <text x="{{ $pt['x'] }}" y="224" class="text-[9px] fill-slate-500 font-semibold" text-anchor="middle">{{ $pt['label'] }}</text>
                                            </g>
                                        @endforeach
                                    </g>
                                </svg>
                            </div>
                        </div>

                        {{-- Sub-Cards Row --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                            {{-- Komoditas per Kategori --}}
                            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Komoditas per Kategori</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">Distribusi komoditas acuan pangan</p>
                                    </div>
                                    <span class="text-[11px] font-semibold text-[#c2410c] bg-orange-50 px-2.5 py-0.5 rounded-full border border-orange-200/60 tabular-nums shrink-0">{{ $totalCommodities }} Komoditas</span>
                                </div>

                                <div class="space-y-3 pt-1">
                                    @php
                                        $catColors = ['bg-[#ea580c]', 'bg-sky-500', 'bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-indigo-500', 'bg-teal-500'];
                                    @endphp

                                    @forelse($categories as $idx => $cat)
                                        @php
                                            $color = $catColors[$idx % count($catColors)];
                                            $percent = min(100, max(8, round((($cat['totalCommodities'] ?? 0) / $maxCommodityCount) * 100)));
                                        @endphp
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="font-semibold text-slate-700 truncate max-w-[165px]">{{ $cat['name'] }}</span>
                                                <span class="font-semibold text-slate-500 text-[11px] tabular-nums">{{ $cat['totalCommodities'] }} item</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full {{ $color }} rounded-full transition-all duration-500" style="width: {{ $percent }}%;"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-4 text-center text-xs text-slate-400">
                                            Belum ada data kategori komoditas.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Pertumbuhan Pengguna --}}
                            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Pertumbuhan Pengguna</h3>
                                        <p class="text-xs text-slate-500 mt-0.5">Pendaftaran tahun {{ now()->year }}</p>
                                    </div>
                                    <span class="text-[11px] font-semibold text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full tabular-nums shrink-0">{{ $totalUsers }} Total</span>
                                </div>

                                <div class="pt-2">
                                    <svg viewBox="0 0 300 140" class="w-full h-auto">
                                        <line x1="20" y1="120" x2="290" y2="120" stroke="#e2e8f0" stroke-width="1.5" />

                                        @foreach($monthlyBars as $bar)
                                            <g class="cursor-pointer group">
                                                <rect
                                                    x="{{ $bar['x'] }}"
                                                    y="{{ $bar['y'] }}"
                                                    width="14"
                                                    height="{{ $bar['height'] }}"
                                                    rx="4"
                                                    class="{{ $bar['is_current'] ? 'fill-[#ea580c]' : ($bar['count'] > 0 ? 'fill-orange-400 hover:fill-[#ea580c]' : 'fill-slate-100') }} transition-all"
                                                >
                                                    <title>{{ $bar['label'] }}: {{ $bar['count'] }} pengguna baru</title>
                                                </rect>

                                                @if($bar['month_num'] % 2 !== 0)
                                                    <text x="{{ $bar['x'] + 7 }}" y="134" class="text-[7.5px] {{ $bar['is_current'] ? 'fill-[#ea580c] font-bold' : 'fill-slate-400 font-semibold' }}" text-anchor="middle">
                                                        {{ $bar['label'] }}
                                                    </text>
                                                @endif
                                            </g>
                                        @endforeach
                                    </svg>
                                </div>
                            </div>

                        </div>

                    </div>

                    {{-- Right Column (4 Cols) --}}
                    <div class="lg:col-span-4 space-y-6">

                        {{-- Distribusi User Donut Chart --}}
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col items-center tabular-nums">
                            <div class="w-full text-left">
                                <h3 class="text-base font-bold text-slate-900 tracking-tight">Distribusi Pengguna</h3>
                                <p class="text-xs text-slate-500 mt-0.5 mb-3">Berdasarkan peran akun terdaftar</p>
                            </div>

                            <div class="relative w-44 h-44 flex items-center justify-center my-2">
                                <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="11" />

                                    @if($totalUmkm > 0)
                                        <circle
                                            cx="50"
                                            cy="50"
                                            r="38"
                                            fill="none"
                                            stroke="#ea580c"
                                            stroke-width="11"
                                            stroke-dasharray="{{ $umkmDash }} 240"
                                            stroke-linecap="round"
                                            class="transition-all duration-700 ease-out"
                                        />
                                    @endif

                                    @if($totalSuppliers > 0)
                                        <circle
                                            cx="50"
                                            cy="50"
                                            r="38"
                                            fill="none"
                                            stroke="#0284c7"
                                            stroke-width="11"
                                            stroke-dasharray="{{ $supplierDash }} 240"
                                            stroke-dashoffset="{{ $supplierOffset }}"
                                            stroke-linecap="round"
                                            class="transition-all duration-700 ease-out"
                                        />
                                    @endif
                                </svg>

                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center select-none pointer-events-none">
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block leading-none">Total Mitra</span>
                                    <span class="text-3xl font-bold text-slate-900 mt-1.5 leading-none">{{ $totalUmkm + $totalSuppliers }}</span>
                                    <span class="text-[10px] text-emerald-600 font-semibold mt-1">Terdaftar</span>
                                </div>
                            </div>

                            <div class="w-full grid grid-cols-2 gap-2.5 mt-3 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2 bg-slate-50 rounded-xl px-3 py-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#ea580c] shrink-0"></span>
                                    <div class="min-w-0 leading-tight">
                                        <p class="text-[10px] font-semibold text-slate-500">UMKM</p>
                                        <p class="text-xs font-bold text-slate-900">{{ $totalUmkm }} ({{ $umkmPercent }}%)</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 bg-slate-50 rounded-xl px-3 py-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-sky-600 shrink-0"></span>
                                    <div class="min-w-0 leading-tight">
                                        <p class="text-[10px] font-semibold text-slate-500">Pemasok</p>
                                        <p class="text-xs font-bold text-slate-900">{{ $totalSuppliers }} ({{ $supplierPercent }}%)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Aktivitas Terbaru --}}
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 tracking-tight">Aktivitas Terbaru</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Pendaftaran &amp; status verifikasi</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/70">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Live</span>
                                </span>
                            </div>

                            <div class="space-y-3 pt-1">
                                @forelse($systemLogs as $log)
                                    <div class="flex items-start gap-3 pb-3 border-b border-slate-100 last:border-0 last:pb-0" title="{{ $log['raw_time'] }}">
                                        <div class="w-9 h-9 rounded-xl {{ $log['role'] === 'supplier' ? 'bg-sky-50 text-sky-700 border border-sky-200/70' : ($log['role'] === 'umkm' ? 'bg-orange-50 text-[#ea580c] border border-orange-200/70' : 'bg-slate-100 text-slate-700 border border-slate-200/70') }} flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($log['actor'], 0, 2)) }}
                                        </div>
                                        <div class="overflow-hidden flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <h4 class="text-xs font-semibold text-slate-900 leading-snug truncate">{{ $log['actor'] }}</h4>
                                                <span class="text-[10px] text-slate-400 font-medium whitespace-nowrap shrink-0">{{ $log['timestamp'] }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $log['action'] }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-4 text-center text-xs text-slate-400">
                                        Belum ada aktivitas pendaftaran terbaru.
                                    </div>
                                @endforelse
                            </div>

                            <button type="button"
                                    @click="setAdminTab('monitoring')"
                                    class="w-full py-2.5 px-4 bg-slate-50 hover:bg-orange-50 text-slate-700 hover:text-[#ea580c] font-semibold text-xs rounded-xl transition flex items-center justify-center gap-2 cursor-pointer border border-slate-200/80 hover:border-orange-200">
                                <span>Lihat Semua Log Audit</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>

                    </div>

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- 2. MANAJEMEN PENGGUNA & VERIFIKASI AKUN                   --}}
            {{-- ========================================================= --}}
            <div x-show="adminTab === 'users'" x-cloak class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Verifikasi &amp; Moderasi Akun</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna Sistem</h2>
                        <p class="text-xs sm:text-sm text-slate-500">Kelola persetujuan registrasi mitra UMKM maupun pemasok dan kontrol akses akun aktif.</p>
                    </div>
                    @if($pendingUsersCount > 0)
                        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-800 text-xs font-semibold tabular-nums self-start sm:self-auto">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>{{ $pendingUsersCount }} Akun Menunggu Verifikasi</span>
                        </span>
                    @endif
                </div>

                {{-- Search, Role & Account Status Filter --}}
                <form method="GET"
                      action="{{ route('admin.dashboard') }}"
                      class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                    <input type="hidden" name="tab" value="users">

                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <div class="relative flex-1 min-w-0">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="user_search"
                                x-model="userSearch"
                                value="{{ $userSearch }}"
                                placeholder="Cari berdasarkan nama usaha, akun, atau email..."
                                class="w-full pl-10 pr-4 py-2.5 text-xs font-medium bg-slate-50 rounded-xl border border-slate-200/90 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 text-slate-900 transition"
                            />
                        </div>
                        <button type="submit"
                                class="px-3.5 py-2.5 rounded-xl bg-[#161922] hover:bg-slate-800 text-white text-xs font-semibold transition shrink-0 cursor-pointer">
                            Cari
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 text-xs shrink-0">
                        <div class="flex items-center gap-2">
                            <label for="user-filter-role" class="font-semibold text-slate-500 whitespace-nowrap">Tipe Akun:</label>
                            <select
                                id="user-filter-role"
                                name="user_role"
                                x-model="userFilterRole"
                                @change="$nextTick(() => $el.form.submit())"
                                class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 font-semibold text-slate-700 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition cursor-pointer">
                                <option value="ALL" @selected($userFilterRole === 'ALL')>Semua Akun</option>
                                <option value="UMKM" @selected($userFilterRole === 'UMKM')>UMKM Kuliner</option>
                                <option value="PEMASOK" @selected($userFilterRole === 'PEMASOK')>Pemasok Bahan Baku</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="user-filter-status" class="font-semibold text-slate-500 whitespace-nowrap">Status Akun:</label>
                            <select
                                id="user-filter-status"
                                name="user_status"
                                x-model="userFilterStatus"
                                @change="$nextTick(() => $el.form.submit())"
                                class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 font-semibold text-slate-700 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition cursor-pointer">
                                <option value="ALL" @selected($userFilterStatus === 'ALL')>Semua Status</option>
                                <option value="AKTIF" @selected($userFilterStatus === 'AKTIF')>Aktif</option>
                                <option value="PENDING_VERIFIKASI" @selected($userFilterStatus === 'PENDING_VERIFIKASI')>Pending Verifikasi</option>
                                <option value="NONAKTIF" @selected($userFilterStatus === 'NONAKTIF')>Nonaktif</option>
                            </select>
                        </div>

                        @if($userSearch !== '' || $userFilterRole !== 'ALL' || $userFilterStatus !== 'ALL')
                            <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}"
                               class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 font-semibold transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                {{-- Table Moderasi Akun --}}
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200/80 font-semibold uppercase text-slate-500 text-[11px] tracking-wider">
                                    <th class="py-3.5 px-5">ID User</th>
                                    <th class="py-3.5 px-5">Nama Usaha / Akun</th>
                                    <th class="py-3.5 px-5">Tipe Akun</th>
                                    <th class="py-3.5 px-5">Kontak &amp; Email</th>
                                    <th class="py-3.5 px-5">Status Akun</th>
                                    <th class="py-3.5 px-5 text-right">Aksi Moderasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($users as $u)
                                    <tr
                                        x-show="(!userSearch || {{ \Illuminate\Support\Js::from($u['search_text']) }}.includes(userSearch.toLowerCase().trim())) && (userFilterRole === 'ALL' || userFilterRole === '{{ $u['type'] }}') && (userFilterStatus === 'ALL' || userFilterStatus === '{{ $u['status'] }}')"
                                        class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-4 px-5 font-semibold text-slate-600 tabular-nums">{{ $u['id'] }}</td>
                                        <td class="py-4 px-5 font-semibold text-[13px] text-slate-900">{{ $u['name'] }}</td>
                                        <td class="py-4 px-5">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $u['type'] === 'UMKM' ? 'bg-orange-50 text-[#c2410c] border border-orange-200/70' : 'bg-sky-50 text-sky-700 border border-sky-200/70' }}">
                                                {{ $u['type'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-5">
                                            <span class="block text-slate-900 font-medium">{{ $u['email'] }}</span>
                                            <span class="text-[11px] text-slate-500 tabular-nums block mt-0.5">{{ $u['phone'] }}</span>
                                        </td>
                                        <td class="py-4 px-5">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $u['status'] === 'AKTIF' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/70' : ($u['status'] === 'PENDING_VERIFIKASI' ? 'bg-amber-50 text-amber-700 border border-amber-200/70' : 'bg-rose-50 text-rose-700 border border-rose-200/70') }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $u['status'] === 'AKTIF' ? 'bg-emerald-500' : ($u['status'] === 'PENDING_VERIFIKASI' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                                                <span>{{ $u['status'] }}</span>
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 text-right whitespace-nowrap">
                                            @if($u['status'] === 'PENDING_VERIFIKASI')
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="AKTIF">
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer transition shadow-xs">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <span>Verifikasi &amp; Aktifkan</span>
                                                    </button>
                                                </form>
                                            @elseif($u['status'] === 'AKTIF')
                                                <form action="{{ route('admin.users.update-status', $u['raw_id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="NONAKTIF">
                                                    <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 px-3.5 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer transition">
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
                                                    <button type="submit" class="bg-[#ea580c] hover:bg-[#c2410c] text-white px-3.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer shadow-xs">
                                                        Aktifkan Kembali
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                            @if($userSearch !== '' || $userFilterRole !== 'ALL' || $userFilterStatus !== 'ALL')
                                                Tidak ada akun pengguna yang sesuai dengan filter yang dipilih.
                                            @else
                                                Belum ada data pengguna mitra.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($users->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- 3. MASTER DATA KOMODITAS & SATUAN                         --}}
            {{-- ========================================================= --}}
            <div x-show="adminTab === 'master'" x-cloak class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Standarisasi Data Sistem</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Master Data Komoditas &amp; Satuan</h2>
                        <p class="text-xs sm:text-sm text-slate-500">Kelola kategori acuan, satuan baku, serta akses katalog master komoditas B2B.</p>
                    </div>
                    <a href="{{ route('admin.commodities.index') }}"
                       class="inline-flex items-center gap-2 bg-[#161922] hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-xs font-semibold transition shadow-xs self-start sm:self-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Kelola Komoditas Master ({{ $totalCommodities }})</span>
                    </a>
                </div>

                {{-- Card: Master Kategori --}}
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900">Kategori Komoditas Bahan Baku</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Standar kategori yang digunakan di katalog UMKM &amp; Pemasok</p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <a href="{{ route('admin.categories.index') }}"
                               class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                                Kelola Penuh
                            </a>
                            <button
                                type="button"
                                @click="isCategoryModalOpen = true"
                                class="inline-flex items-center gap-1.5 bg-[#ea580c] hover:bg-[#c2410c] text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-xs cursor-pointer transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Kategori Baru</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                        @forelse($categories as $cat)
                            <div class="bg-slate-50/70 hover:bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex justify-between items-start gap-3 transition-colors">
                                <div class="min-w-0 space-y-1">
                                    <span class="inline-flex px-2 py-0.5 rounded-md bg-orange-50 text-[#c2410c] text-[10px] font-bold tabular-nums">{{ $cat['code'] }}</span>
                                    <h4 class="font-bold text-sm text-slate-900 truncate">{{ $cat['name'] }}</h4>
                                    <p class="text-[11px] text-slate-500 line-clamp-2">{{ $cat['description'] }}</p>
                                    <span class="text-[11px] text-slate-400 block pt-1 font-medium tabular-nums">Dipakai oleh {{ $cat['totalCommodities'] }} komoditas</span>
                                </div>
                                <form data-no-confirm="true" action="{{ route('admin.categories.destroy', $cat['id']) }}" method="POST" class="inline shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus Kategori">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-slate-400 sm:col-span-2 lg:col-span-3 py-4 text-center">Belum ada kategori komoditas tersimpan.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Card: Master Satuan --}}
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900">Satuan Bahan Baku</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Simbol unit pengukuran baku (kg, liter, gram, ikat, dus)</p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <a href="{{ route('admin.units.index') }}"
                               class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                                Kelola Penuh
                            </a>
                            <button
                                type="button"
                                @click="isUnitModalOpen = true"
                                class="inline-flex items-center gap-1.5 bg-[#161922] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-xs cursor-pointer transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Satuan Baru</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200/80 font-semibold uppercase text-slate-500 text-[11px] tracking-wider">
                                    <th class="py-3 px-4">Nama Satuan</th>
                                    <th class="py-3 px-4">Simbol Unit</th>
                                    <th class="py-3 px-4">Tipe Ukuran</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($units as $u)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $u['name'] }}</td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-md bg-orange-50 text-[#c2410c] font-bold text-[11px] border border-orange-200/70">{{ $u['symbol'] }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">{{ $u['type'] }}</td>
                                        <td class="py-3.5 px-4 text-right">
                                            <form data-no-confirm="true" action="{{ route('admin.units.destroy', $u['id']) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus Satuan">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-400">Belum ada satuan baku tersimpan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- 4. MONITORING SISTEM & AUDIT LOG                          --}}
            {{-- ========================================================= --}}
            <div x-show="adminTab === 'monitoring'" x-cloak class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Audit &amp; System Logs</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Monitoring Sistem &amp; Aktivitas Akun</h2>
                        <p class="text-xs sm:text-sm text-slate-500">Jejak audit pendaftaran dan status verifikasi pengguna terkini di platform SORAI.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-semibold px-3.5 py-2 rounded-xl border border-emerald-200/80 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Status Audit: Aktif</span>
                    </span>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-base text-slate-900">Log Aktivitas Real-Time Sistem</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Rekaman perubahan status verifikasi mitra terbaru</p>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-500 tabular-nums">{{ count($systemLogs) }} entri terbaru</span>
                    </div>

                    @if(empty($systemLogs))
                        <div class="px-6 py-12 text-center text-xs text-slate-400">
                            Belum ada log aktivitas sistem tercatat.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-semibold uppercase text-slate-500 tracking-wider">
                                        <th class="py-3.5 px-6">Referensi</th>
                                        <th class="py-3.5 px-6">Aktor</th>
                                        <th class="py-3.5 px-6">Aktivitas &amp; Detail</th>
                                        <th class="py-3.5 px-6">Waktu</th>
                                        <th class="py-3.5 px-6 text-right">Audit Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($systemLogs as $log)
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="py-4 px-6 font-semibold text-slate-600 tabular-nums whitespace-nowrap">{{ $log['id'] }}</td>
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="w-7 h-7 rounded-lg {{ $log['role'] === 'supplier' ? 'bg-sky-50 text-sky-700' : ($log['role'] === 'umkm' ? 'bg-orange-50 text-[#ea580c]' : 'bg-slate-100 text-slate-700') }} flex items-center justify-center font-bold text-[11px] shrink-0">
                                                        {{ strtoupper(substr($log['actor'], 0, 2)) }}
                                                    </span>
                                                    <span class="font-semibold text-slate-900 text-[13px]">{{ $log['actor'] }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <p class="font-semibold text-slate-800">{{ $log['action'] }}</p>
                                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $log['detail'] ?? ($log['status'] ?? 'Audited') }}</p>
                                            </td>
                                            <td class="py-4 px-6 whitespace-nowrap tabular-nums">
                                                <span class="block font-medium text-slate-700">{{ $log['timestamp'] }}</span>
                                                <span class="block text-[11px] text-slate-400">{{ $log['raw_time'] }}</span>
                                            </td>
                                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/70 text-[11px] font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>AUDITED</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- MODAL TAMBAH KATEGORI                                     --}}
            {{-- ========================================================= --}}
            <div x-show="isCategoryModalOpen"
                 x-cloak
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div @click.away="isCategoryModalOpen = false"
                     class="bg-white max-w-md w-full rounded-2xl border border-slate-200/80 p-6 space-y-5 shadow-xl">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3.5">
                        <h3 class="text-base font-bold text-slate-900">Tambah Kategori Master</h3>
                        <button type="button"
                                @click="isCategoryModalOpen = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                aria-label="Tutup">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form novalidate data-no-confirm="true" action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama Kategori Komoditas <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                name="name"
                                required
                                value="{{ old('name') }}"
                                placeholder="Contoh: Bumbu Dapur"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"
                            />
                            @error('name')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Deskripsi Ringkas</label>
                            <textarea
                                name="description"
                                rows="3"
                                placeholder="Tuliskan komoditas apa saja yang masuk kategori ini..."
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isCategoryModalOpen = false"
                                class="w-1/2 border border-slate-200/90 bg-white text-slate-700 py-2.5 rounded-xl font-semibold hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 rounded-xl font-semibold shadow-xs transition cursor-pointer">
                                Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- MODAL TAMBAH SATUAN                                       --}}
            {{-- ========================================================= --}}
            <div x-show="isUnitModalOpen"
                 x-cloak
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div @click.away="isUnitModalOpen = false"
                     class="bg-white max-w-md w-full rounded-2xl border border-slate-200/80 p-6 space-y-5 shadow-xl">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3.5">
                        <h3 class="text-base font-bold text-slate-900">Tambah Satuan Master</h3>
                        <button type="button"
                                @click="isUnitModalOpen = false"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                aria-label="Tutup">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form novalidate data-no-confirm="true" action="{{ route('admin.units.store') }}" method="POST" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Nama Satuan <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                name="name"
                                required
                                value="{{ old('name') }}"
                                placeholder="Contoh: Kilogram"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"
                            />
                            @error('name')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Simbol / Singkatan Unit <span class="text-rose-500">*</span></label>
                            <input
                                type="text"
                                name="symbol"
                                required
                                value="{{ old('symbol') }}"
                                placeholder="Contoh: kg"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 font-medium focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"
                            />
                            @error('symbol')
                                <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Deskripsi / Tipe Ukuran</label>
                            <input
                                type="text"
                                name="description"
                                placeholder="Contoh: Berat, Volume, Kuantitas"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"
                            />
                        </div>

                        <div class="flex gap-3 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isUnitModalOpen = false"
                                class="w-1/2 border border-slate-200/90 bg-white text-slate-700 py-2.5 rounded-xl font-semibold hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="w-1/2 bg-[#ea580c] hover:bg-[#c2410c] text-white py-2.5 rounded-xl font-semibold shadow-xs transition cursor-pointer">
                                Simpan Satuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>

    </div>

    @include('partials.action-confirm-modal')
    @include('partials.logout-modal', ['logoutRoute' => route('admin.logout')])

</body>
</html>
