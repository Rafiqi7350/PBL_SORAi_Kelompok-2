<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI Admin - Master Data Komoditas</title>
    
    <!-- Google Fonts Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#FDF5E6] text-gray-900 antialiased min-h-screen flex flex-col">

    <!-- Admin Top Navbar -->
    <header class="p-4 px-6 md:px-12 w-full pt-6">
        <div class="bg-[#1E222D] text-white rounded-full px-6 py-3.5 flex items-center justify-between shadow-xl border-2 border-black">
            <!-- Brand & Admin Badge -->
            <div class="flex items-center gap-3">
                <div class="bg-[#F4511E] rounded-full w-10 h-10 flex items-center justify-center font-black text-lg text-white shadow-md">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-black text-xl tracking-tight text-white">SORAI ADMIN</span>
                    <span class="text-[10px] font-extrabold tracking-widest text-[#FFB703] uppercase">Portal Kelola Data Acuan Pemerintah</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('admin.commodities.index') }}" class="px-5 py-2 text-xs font-black uppercase transition rounded-full {{ request()->routeIs('admin.commodities.*') ? 'bg-[#FFB703] text-[#1E222D]' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                    🌾 Master Komoditas
                </a>
                <a href="{{ route('admin.categories.index') }}" class="px-5 py-2 text-xs font-black uppercase transition rounded-full {{ request()->routeIs('admin.categories.*') ? 'bg-[#FFB703] text-[#1E222D]' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                    🏷️ Master Kategori
                </a>
                <a href="{{ route('admin.units.index') }}" class="px-5 py-2 text-xs font-black uppercase transition rounded-full {{ request()->routeIs('admin.units.*') ? 'bg-[#FFB703] text-[#1E222D]' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                    📏 Master Satuan Baku
                </a>
            </nav>

            <!-- Actions / Status -->
            <div class="flex items-center gap-3">
                <span class="text-xs font-mono font-bold bg-red-950 text-rose-300 px-3 py-1.5 rounded-full border border-rose-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> 🛡️ Admin Mode
                </span>
                <a href="{{ route('umkm.katalog.index') }}" class="px-4 py-1.5 rounded-full font-bold text-xs bg-gray-800 text-gray-200 hover:bg-gray-700 transition">
                    Lihat Frontend UMKM &rarr;
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col w-full px-6 md:px-12 py-6">
        @if(session('success'))
            <div class="bg-emerald-50 border-2 border-emerald-500 text-emerald-900 px-5 py-3.5 rounded-2xl shadow-sm mb-6 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs">✓</span>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border-2 border-rose-500 text-rose-900 px-5 py-4 rounded-2xl shadow-sm mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-rose-500 text-white rounded-full w-5 h-5 flex items-center justify-center font-bold text-xs">✕</span>
                    <strong class="font-bold text-sm">Terjadi Kesalahan Validasi:</strong>
                </div>
                <ul class="list-disc list-inside text-xs font-semibold space-y-1 ml-6 text-rose-800">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs font-bold text-gray-500">
        SORAI Portal Kemitraan UMKM - Modul Administrator Panel &copy; {{ date('Y') }}
    </footer>

</body>
</html>
