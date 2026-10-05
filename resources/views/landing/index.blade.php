<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAi - Portal Dapur UMKM & Pengadaan Bahan Baku Lokal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite & Alpine -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-[#FAF2DE] text-gray-900 font-sans antialiased selection:bg-[#F5B800] selection:text-black min-h-screen flex flex-col justify-between" x-data="{ mobileMenuOpen: false }">

    <!-- 1. NAVBAR / HEADER -->
    @include('landing.partials.navbar')

    <!-- MAIN CONTENT -->
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 my-6 sm:my-8 flex-grow">

        <!-- 2. HERO SECTION -->
        @include('landing.partials.hero')

        <!-- 3. KEUNGGULAN / FEATURES SECTION & CARDS -->
        @include('landing.partials.features')

        <!-- 4. ALUR KERJA / HOW IT WORKS SECTION & CARDS -->
        @include('landing.partials.how-it-works')

        <!-- 5. KATALOG BAHAN BAKU / CATEGORIES SECTION & CARDS -->
        @include('landing.partials.categories')

        <!-- 6. FAQ SECTION & ACCORDION ITEMS -->
        @include('landing.partials.faq')


    </main>

    <!-- 8. FOOTER -->
    @include('landing.partials.footer')

</body>
</html>
