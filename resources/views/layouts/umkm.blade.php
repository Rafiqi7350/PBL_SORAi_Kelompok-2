<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SORAI — Portal Dapur UMKM & Pengadaan Bahan Baku Lokal')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(!empty(config('midtrans.client_key')))
        <script type="text/javascript"
                src="{{ config('midtrans.snap_url') }}"
                data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endif

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
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 flex flex-col justify-between antialiased selection:bg-orange-100 selection:text-orange-900"
      x-data="{
          mobileNavOpen: false,
          activeTab: (new URLSearchParams(window.location.search).get('tab') || 'katalog'),
          setTab(t) {
              this.activeTab = t;
              this.mobileNavOpen = false;
              const url = new URL(window.location);
              url.searchParams.set('tab', t);
              window.history.replaceState({}, '', url);
          }
      }">

    @php
        $authUser = auth()->user();
        $umkmBusinessName = $authUser?->umkmProfile?->business_name
            ?? $authUser?->umkmProfile?->owner_name
            ?? ($authUser?->role === 'supplier' ? ($authUser?->supplierProfile?->company_name ?? 'Mode Pemasok') : 'Dapur UMKM');
        $umkmInitial = strtoupper(substr(trim($umkmBusinessName), 0, 1)) ?: 'U';
    @endphp

    {{-- Top Navigation Header --}}
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        {{-- Tier 1: Brand & Workspace Utility Bar --}}
        <div class="border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between gap-4">

                {{-- Brand Lockup --}}
                <a href="{{ route('umkm.katalog.index') }}"
                   @click.prevent="setTab('katalog')"
                   class="flex items-center gap-3 min-w-0 group">
                    <div class="w-8 h-8 rounded-lg bg-[#ea580c] text-white flex items-center justify-center font-extrabold text-sm shadow-xs group-hover:bg-[#c2410c] transition-colors shrink-0">
                        S
                    </div>
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="font-extrabold text-[17px] text-slate-900 tracking-tight leading-none">SORAI</span>
                        <span class="hidden sm:inline-block text-slate-200 font-light text-lg leading-none select-none">/</span>
                        <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100/90 text-[11px] font-semibold text-slate-600 tracking-tight leading-none whitespace-nowrap">
                            Portal Dapur UMKM
                        </span>
                    </div>
                </a>

                {{-- Right Account & Utility Controls --}}
                <div class="flex items-center gap-3 shrink-0">
                    <span class="hidden lg:inline-flex items-center text-xs font-medium text-slate-400 tabular-nums">
                        {{ now()->locale('id')->translatedFormat('l, d M Y') }}
                    </span>

                    <span class="hidden lg:block h-4 w-px bg-slate-200"></span>

                    {{-- UMKM Business Identity --}}
                    <div class="hidden md:flex items-center gap-2.5 min-w-0">
                        <div class="relative shrink-0">
                            <span class="w-8 h-8 rounded-lg bg-[#161922] text-white text-xs font-bold flex items-center justify-center tracking-tight">
                                {{ $umkmInitial }}
                            </span>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white" title="Mitra UMKM Aktif"></span>
                        </div>
                        <div class="min-w-0 leading-tight">
                            <p class="text-xs font-semibold text-slate-800 max-w-[160px] lg:max-w-[210px] truncate">{{ $umkmBusinessName }}</p>
                            <p class="text-[11px] font-medium text-slate-400 max-w-[160px] lg:max-w-[210px] truncate">{{ $authUser?->email ?? 'Mitra Dapur UMKM' }}</p>
                        </div>
                    </div>

                    <span class="hidden md:block h-5 w-px bg-slate-200/90"></span>

                    {{-- Logout Button --}}
                    <a href="{{ route('logout') }}"
                       @click.prevent="$dispatch('open-logout-modal')"
                       onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                       class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-rose-50/90 text-slate-600 hover:text-rose-600 border border-slate-200/90 hover:border-rose-200 text-xs font-semibold transition-colors cursor-pointer shrink-0"
                       title="Keluar dari Akun">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Keluar</span>
                    </a>

                    {{-- Mobile Menu Toggle --}}
                    <button type="button"
                            @click="mobileNavOpen = !mobileNavOpen"
                            class="md:hidden inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            aria-label="Toggle Menu Navigasi">
                        <svg x-show="!mobileNavOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileNavOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tier 2: Desktop Primary Tab Navigation --}}
        <div class="hidden md:block">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-11 overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    <nav class="flex items-center gap-1 lg:gap-4 h-full mx-auto shrink-0" aria-label="Navigasi Portal UMKM">
                        <button type="button"
                                @click="setTab('katalog')"
                                class="relative h-full inline-flex items-center shrink-0 cursor-pointer group">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] transition-colors whitespace-nowrap"
                                  :class="activeTab === 'katalog' ? 'text-slate-950 font-semibold' : 'text-slate-500 font-medium group-hover:text-slate-900 group-hover:bg-slate-100/75'">
                                <svg class="w-4 h-4 shrink-0 transition-colors"
                                     :class="activeTab === 'katalog' ? 'text-[#ea580c]' : 'text-slate-400 group-hover:text-slate-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span>Katalog Bahan Baku</span>
                            </span>
                            <span class="absolute bottom-0 left-2.5 right-2.5 h-[2px] rounded-full transition-colors duration-150"
                                  :class="activeTab === 'katalog' ? 'bg-[#ea580c]' : 'bg-transparent'"></span>
                        </button>

                        <button type="button"
                                @click="setTab('supply-planning')"
                                class="relative h-full inline-flex items-center shrink-0 cursor-pointer group">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] transition-colors whitespace-nowrap"
                                  :class="activeTab === 'supply-planning' ? 'text-slate-950 font-semibold' : 'text-slate-500 font-medium group-hover:text-slate-900 group-hover:bg-slate-100/75'">
                                <svg class="w-4 h-4 shrink-0 transition-colors"
                                     :class="activeTab === 'supply-planning' ? 'text-[#ea580c]' : 'text-slate-400 group-hover:text-slate-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Supply Planning</span>
                            </span>
                            <span class="absolute bottom-0 left-2.5 right-2.5 h-[2px] rounded-full transition-colors duration-150"
                                  :class="activeTab === 'supply-planning' ? 'bg-[#ea580c]' : 'bg-transparent'"></span>
                        </button>

                        <button type="button"
                                @click="setTab('pemesanan')"
                                class="relative h-full inline-flex items-center shrink-0 cursor-pointer group">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] transition-colors whitespace-nowrap"
                                  :class="activeTab === 'pemesanan' ? 'text-slate-950 font-semibold' : 'text-slate-500 font-medium group-hover:text-slate-900 group-hover:bg-slate-100/75'">
                                <svg class="w-4 h-4 shrink-0 transition-colors"
                                     :class="activeTab === 'pemesanan' ? 'text-[#ea580c]' : 'text-slate-400 group-hover:text-slate-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>Pesanan &amp; Pengiriman</span>
                            </span>
                            <span class="absolute bottom-0 left-2.5 right-2.5 h-[2px] rounded-full transition-colors duration-150"
                                  :class="activeTab === 'pemesanan' ? 'bg-[#ea580c]' : 'bg-transparent'"></span>
                        </button>

                        <button type="button"
                                @click="setTab('dashboard')"
                                class="relative h-full inline-flex items-center shrink-0 cursor-pointer group">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] transition-colors whitespace-nowrap"
                                  :class="activeTab === 'dashboard' ? 'text-slate-950 font-semibold' : 'text-slate-500 font-medium group-hover:text-slate-900 group-hover:bg-slate-100/75'">
                                <svg class="w-4 h-4 shrink-0 transition-colors"
                                     :class="activeTab === 'dashboard' ? 'text-[#ea580c]' : 'text-slate-400 group-hover:text-slate-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Dashboard Analisis</span>
                            </span>
                            <span class="absolute bottom-0 left-2.5 right-2.5 h-[2px] rounded-full transition-colors duration-150"
                                  :class="activeTab === 'dashboard' ? 'bg-[#ea580c]' : 'bg-transparent'"></span>
                        </button>

                        <button type="button"
                                @click="setTab('monitoring')"
                                class="relative h-full inline-flex items-center shrink-0 cursor-pointer group">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] transition-colors whitespace-nowrap"
                                  :class="activeTab === 'monitoring' ? 'text-slate-950 font-semibold' : 'text-slate-500 font-medium group-hover:text-slate-900 group-hover:bg-slate-100/75'">
                                <svg class="w-4 h-4 shrink-0 transition-colors"
                                     :class="activeTab === 'monitoring' ? 'text-[#ea580c]' : 'text-slate-400 group-hover:text-slate-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Monitoring &amp; SLA</span>
                            </span>
                            <span class="absolute bottom-0 left-2.5 right-2.5 h-[2px] rounded-full transition-colors duration-150"
                                  :class="activeTab === 'monitoring' ? 'bg-[#ea580c]' : 'bg-transparent'"></span>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        {{-- Mobile Dropdown Drawer --}}
        <div x-cloak
             x-show="mobileNavOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-slate-200 bg-white px-4 py-4 space-y-3 shadow-lg">

            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                <div class="relative shrink-0">
                    <span class="w-9 h-9 rounded-lg bg-[#161922] text-white text-xs font-bold flex items-center justify-center">
                        {{ $umkmInitial }}
                    </span>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-slate-50"></span>
                </div>
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="text-xs font-bold text-slate-900 truncate">{{ $umkmBusinessName }}</p>
                    <p class="text-[11px] font-medium text-slate-500 truncate mt-0.5">{{ $authUser?->email ?? 'Mitra UMKM Aktif' }}</p>
                </div>
            </div>

            <nav class="grid grid-cols-1 gap-1" aria-label="Menu Mobile UMKM">
                <button type="button"
                        @click="setTab('katalog')"
                        :class="activeTab === 'katalog' ? 'bg-orange-50/90 text-[#c2410c] border-orange-200/80 font-semibold' : 'text-slate-700 border-transparent hover:bg-slate-50 font-medium'"
                        class="w-full px-3.5 py-2.5 rounded-xl border text-xs flex items-center gap-2.5 transition cursor-pointer text-left">
                    <svg class="w-4 h-4 shrink-0" :class="activeTab === 'katalog' ? 'text-[#ea580c]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Katalog Bahan Baku</span>
                </button>

                <button type="button"
                        @click="setTab('supply-planning')"
                        :class="activeTab === 'supply-planning' ? 'bg-orange-50/90 text-[#c2410c] border-orange-200/80 font-semibold' : 'text-slate-700 border-transparent hover:bg-slate-50 font-medium'"
                        class="w-full px-3.5 py-2.5 rounded-xl border text-xs flex items-center gap-2.5 transition cursor-pointer text-left">
                    <svg class="w-4 h-4 shrink-0" :class="activeTab === 'supply-planning' ? 'text-[#ea580c]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Supply Planning</span>
                </button>

                <button type="button"
                        @click="setTab('pemesanan')"
                        :class="activeTab === 'pemesanan' ? 'bg-orange-50/90 text-[#c2410c] border-orange-200/80 font-semibold' : 'text-slate-700 border-transparent hover:bg-slate-50 font-medium'"
                        class="w-full px-3.5 py-2.5 rounded-xl border text-xs flex items-center gap-2.5 transition cursor-pointer text-left">
                    <svg class="w-4 h-4 shrink-0" :class="activeTab === 'pemesanan' ? 'text-[#ea580c]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Pesanan &amp; Pengiriman</span>
                </button>

                <button type="button"
                        @click="setTab('dashboard')"
                        :class="activeTab === 'dashboard' ? 'bg-orange-50/90 text-[#c2410c] border-orange-200/80 font-semibold' : 'text-slate-700 border-transparent hover:bg-slate-50 font-medium'"
                        class="w-full px-3.5 py-2.5 rounded-xl border text-xs flex items-center gap-2.5 transition cursor-pointer text-left">
                    <svg class="w-4 h-4 shrink-0" :class="activeTab === 'dashboard' ? 'text-[#ea580c]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Dashboard Analisis</span>
                </button>

                <button type="button"
                        @click="setTab('monitoring')"
                        :class="activeTab === 'monitoring' ? 'bg-orange-50/90 text-[#c2410c] border-orange-200/80 font-semibold' : 'text-slate-700 border-transparent hover:bg-slate-50 font-medium'"
                        class="w-full px-3.5 py-2.5 rounded-xl border text-xs flex items-center gap-2.5 transition cursor-pointer text-left">
                    <svg class="w-4 h-4 shrink-0" :class="activeTab === 'monitoring' ? 'text-[#ea580c]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Monitoring &amp; SLA</span>
                </button>
            </nav>

            <div class="pt-2 border-t border-slate-100">
                <a href="{{ route('logout') }}"
                   @click.prevent="mobileNavOpen = false; $dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="w-full py-2.5 px-3.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold flex items-center justify-center gap-2 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar dari Akun</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Banner ketika akun supplier meninjau sisi UMKM --}}
    @if(auth()->check() && auth()->user()->role === 'supplier')
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-amber-50 border border-amber-200/90 rounded-2xl px-4 py-3 text-xs font-medium text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Anda sedang meninjau portal UMKM menggunakan akun Pemasok (<strong class="font-bold">{{ auth()->user()->supplierProfile?->company_name ?? auth()->user()->email }}</strong>).</span>
                </div>
                <a href="{{ route('supplier.katalog.index') }}" class="inline-flex items-center justify-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-xl text-[11px] font-semibold transition shrink-0">
                    <span>Kembali ke Panel Pemasok</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    @endif

    {{-- Main Workspace --}}
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @include('partials.flash-notification')
        @yield('content')
    </main>

    {{-- Modal Logout --}}
    @include('partials.logout-modal')

</body>
</html>
