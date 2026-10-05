<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI - Portal Kemitraan UMKM</title>
    
    <!-- Using Google Fonts for Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js for interactive elements -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#FDF5E6] text-gray-900 antialiased min-h-screen flex flex-col">

    <!-- Top Navbar -->
    <header class="p-4 px-6 md:px-12 w-full pt-6">
        <div class="bg-white rounded-full px-6 py-3 flex items-center justify-between border-2 border-black shadow-[0_4px_0_0_rgba(0,0,0,1)]">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-[#FFB703] border-2 border-black rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-extrabold text-xl tracking-tight text-[#1E222D]">SORAI</span>
                    <span class="text-[10px] font-bold tracking-widest text-gray-500 uppercase">Portal Dapur UMKM</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2 rounded-full font-bold text-sm bg-[#FFB703] border-2 border-black shadow-[0_2px_0_0_rgba(0,0,0,1)] hover:bg-[#FFB703]/90 transition">
                    Katalog Bahan Baku
                </a>
                <a href="#" class="px-5 py-2 rounded-full font-semibold text-sm text-gray-700 hover:text-black hover:bg-gray-100 transition flex items-center gap-2">
                    Pemesanan & Supply Planning
                    <span class="bg-[#FF8FA3] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">2</span>
                </a>
                <a href="#" class="px-5 py-2 rounded-full font-semibold text-sm text-gray-700 hover:text-black hover:bg-gray-100 transition">
                    Dashboard Analisis
                </a>
                <a href="#" class="px-5 py-2 rounded-full font-semibold text-sm text-gray-700 hover:text-black hover:bg-gray-100 transition">
                    Contract Monitoring
                </a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded-full font-bold text-sm bg-pink-50 text-[#C1121F] border-2 border-black shadow-[0_2px_0_0_rgba(0,0,0,1)] hover:bg-pink-100 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Keluar
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col w-full px-6 md:px-12 py-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t-2 border-gray-200 mt-auto py-6">
        <div class="text-center flex flex-col items-center justify-center">
            <p class="font-extrabold text-sm text-[#1E222D]">SORAI — PORTAL KEMITRAAN UMKM & PEMASOK BAHAN BAKU LOKAL</p>
            <p class="text-[11px] font-mono text-gray-500 mt-1">Project Based Learning Kelompok 2 (SIB-3F) Politeknik Negeri Malang 2026</p>
        </div>
    </footer>

</body>
</html>
