<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAI - Portal Pemasok Bahan Baku</title>
    
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

    <!-- Top Navbar -->
    <header class="p-4 px-6 md:px-12 w-full pt-6">
        <div class="bg-[#1E222D] text-white rounded-full px-6 py-3 flex items-center justify-between shadow-lg">
            <!-- Logo & Badge -->
            <div class="flex items-center gap-3">
                <div class="bg-[#FFB703] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg text-black">
                    S
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-extrabold text-xl tracking-tight text-white">SORAI</span>
                    <span class="text-[10px] font-bold tracking-widest text-[#FFB703] uppercase">Portal Pemasok / Supplier</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden lg:flex items-center gap-2">
                <a href="{{ route('supplier.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full {{ request()->routeIs('supplier.katalog.*') ? 'bg-[#FFB703] font-bold text-[#1E222D]' : 'font-semibold text-gray-300 hover:text-white hover:bg-gray-800' }}">
                    Kelola Katalog & Stok
                </a>
                <a href="{{ route('umkm.katalog.index') }}" class="px-5 py-2 text-sm transition rounded-full font-semibold text-gray-300 hover:text-white hover:bg-gray-800 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#FFB703]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Lihat Katalog UMKM
                </a>
            </nav>

            <!-- Profile / Actions -->
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-block text-xs font-mono font-bold text-gray-400 bg-gray-800 px-3 py-1.5 rounded-full border border-gray-700">
                    🟢 Mode Pemasok
                </span>
                <a href="{{ route('register.supplier') }}" class="px-5 py-2 rounded-full font-semibold text-xs bg-[#E07A5F] text-white hover:bg-[#d0694e] transition">
                    + Pemasok Baru
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
        @if(session('error'))
            <div class="bg-rose-50 border-2 border-rose-500 text-rose-900 px-5 py-3.5 rounded-2xl shadow-sm mb-6 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <span class="bg-rose-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs">✕</span>
                    <span class="font-bold text-sm">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs font-semibold text-gray-500">
        SORAI Portal Kemitraan UMKM & Pemasok Bahan Baku Lokal &copy; {{ date('Y') }}
    </footer>

</body>
</html>
