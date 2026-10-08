<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI — Portal Dapur UMKM & Pengadaan Bahan Baku Lokal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN v3 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'sorai-orange': '#ea580c',
              'sorai-orange-hover': '#c2410c',
              'sorai-bg': '#f3f4f6',
              'sorai-input': '#f1f2f4',
              'cobalt-signal': '#ea580c',
            },
            fontFamily: {
              sans: ['Plus Jakarta Sans', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
            }
          }
        }
      }
    </script>

    <!-- Styles & Scripts via Vite & Alpine -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f3f4f6;
            color: #111827;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f3f4f6] text-gray-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white"
      x-data="{ 
          activeTab: (new URLSearchParams(window.location.search).get('tab') || 'katalog'),
          setTab(t) {
              this.activeTab = t;
              const url = new URL(window.location);
              url.searchParams.set('tab', t);
              window.history.replaceState({}, '', url);
          }
      }">

    <!-- SISI UMKM KULINER: HEADER NAVIGASI FLOATING PILL -->
    <header class="sticky top-4 z-40 px-4 sm:px-6 max-w-7xl mx-auto w-full my-3">
        <div class="bg-white rounded-[2rem] shadow-[0_4px_20px_rgba(0,0,0,0.06)] p-3 sm:px-6 sm:py-2.5 flex flex-col lg:flex-row items-center justify-between gap-3 lg:gap-4">

            <!-- LOGO & LOGOUT (MOBILE VIEW HEADER) -->
            <div class="flex items-center justify-between w-full lg:w-auto shrink-0 px-2 lg:px-0">
                <!-- BRAND LOGO -->
                <a href="{{ route('umkm.katalog.index') }}" class="flex items-center gap-2.5 cursor-pointer group">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#ea580c] flex items-center justify-center font-bold text-white text-sm shadow-sm group-hover:scale-105 transition-transform">
                        S
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-lg text-black tracking-tight leading-none">SORAI</span>
                        <span class="text-[9px] font-mono text-gray-500 font-semibold tracking-wider uppercase mt-0.5">PORTAL DAPUR UMKM</span>
                    </div>
                </a>

                <!-- MOBILE ACTION BUTTONS (Logout) -->
                <div class="flex lg:hidden items-center gap-2">
                    <a href="{{ route('logout') }}"
                       @click.prevent="$dispatch('open-logout-modal')"
                       onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                       class="bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                       title="Keluar dari Akun">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </a>
                </div>
            </div>

            <!-- NAVIGASI MENU UMKM DI DALAM HEADER PILL -->
            <nav class="flex flex-wrap lg:flex-nowrap justify-center lg:justify-start items-center gap-1.5 font-semibold text-[11px] sm:text-xs w-full lg:w-auto">
                <button 
                    type="button"
                    @click="setTab('katalog')" 
                    :class="activeTab === 'katalog' ? 'bg-[#ea580c] text-white font-bold shadow-sm' : 'hover:bg-gray-100 text-gray-600 hover:text-black'"
                    class="px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-full transition flex items-center gap-1.5 cursor-pointer shrink-0">
                    <span>Katalog Bahan Baku</span>
                </button>

                <button 
                    type="button"
                    @click="setTab('pemesanan')" 
                    :class="activeTab === 'pemesanan' ? 'bg-[#ea580c] text-white font-bold shadow-sm' : 'hover:bg-gray-100 text-gray-600 hover:text-black'"
                    class="px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-full transition flex items-center gap-1.5 relative cursor-pointer shrink-0">
                    <span>Pemesanan & Supply Planning</span>
                    <span :class="activeTab === 'pemesanan' ? 'bg-white text-[#ea580c]' : 'bg-[#ea580c] text-white'" class="font-mono text-[9px] px-1.5 py-0.5 rounded-full font-bold animate-pulse">
                        2
                    </span>
                </button>

                <button 
                    type="button"
                    @click="setTab('dashboard')" 
                    :class="activeTab === 'dashboard' ? 'bg-[#ea580c] text-white font-bold shadow-sm' : 'hover:bg-gray-100 text-gray-600 hover:text-black'"
                    class="px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-full transition flex items-center gap-1.5 cursor-pointer shrink-0">
                    <span>Dashboard Analisis</span>
                </button>

                <button 
                    type="button"
                    @click="setTab('monitoring')" 
                    :class="activeTab === 'monitoring' ? 'bg-[#ea580c] text-white font-bold shadow-sm' : 'hover:bg-gray-100 text-gray-600 hover:text-black'"
                    class="px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-full transition flex items-center gap-1.5 cursor-pointer shrink-0">
                    <span>Contract Monitoring</span>
                </button>
            </nav>

            <!-- DESKTOP ACTION BUTTONS -->
            <div class="hidden lg:flex items-center gap-2 shrink-0">
                <a href="{{ route('logout') }}"
                   @click.prevent="$dispatch('open-logout-modal')"
                   onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                   class="bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 px-3.5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                   title="Keluar dari Akun">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar</span>
                </a>
            </div>
        </div>



        @if(auth()->check() && auth()->user()->role === 'supplier')
            <div class="mt-2.5 bg-amber-50 border border-amber-200 rounded-2xl p-3 text-xs font-semibold text-gray-800 flex flex-col sm:flex-row items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span>Anda sedang melihat sisi UMKM dalam mode <strong>Akun Pemasok</strong> ({{ auth()->user()->supplierProfile?->company_name ?? auth()->user()->email }}).</span>
                </div>
                <a href="{{ route('supplier.katalog.index') }}" class="bg-black text-white px-3 py-1 rounded-full text-[11px] font-bold hover:bg-gray-800 transition">
                    Kembali ke Panel Pemasok →
                </a>
            </div>
        @endif
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 py-6">
        @yield('content')
    </main>



    <!-- Modal Logout -->
    @include('partials.logout-modal')

</body>
</html>
