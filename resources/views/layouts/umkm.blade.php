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
        <div class="bg-white rounded-full px-6 py-3 flex items-center justify-between shadow-sm">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-[#FFB703] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg text-black">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-extrabold text-xl tracking-tight text-[#1E222D]">SORAI</span>
                    <span class="text-[10px] font-bold tracking-widest text-gray-500 uppercase">Portal Dapur UMKM</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.katalog.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Katalog Bahan Baku
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full flex items-center gap-2 {{ request()->routeIs('umkm.pemesanan.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Pemesanan & Supply Planning
                    <span class="bg-[#FF8FA3] text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">2</span>
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.dashboard.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Dashboard Analisis
                </a>
                <a href="#" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('umkm.contract.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-700 hover:text-black hover:bg-gray-100' }}">
                    Contract Monitoring
                </a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button class="px-6 py-2.5 rounded-full font-semibold text-sm bg-gray-100 text-gray-900 hover:bg-gray-200 transition flex items-center gap-2">
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


</body>
</html>
