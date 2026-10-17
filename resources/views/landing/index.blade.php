<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SORAi — Portal Dapur UMKM & Pengadaan Bahan Baku Lokal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-orange-600 selection:text-white min-h-screen flex flex-col justify-between" x-data="{ mobileMenuOpen: false }">

    <!-- 1. NAVBAR / HEADER -->
    @include('landing.partials.navbar')

    <!-- 2. FULL-WIDTH HERO SECTION (STELLANTIS PORTAL STYLE) -->
    @include('landing.partials.hero')

    <!-- MAIN CONTENT -->
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 sm:mt-6 lg:mt-8 mb-14 sm:mb-20 lg:mb-24 flex-grow space-y-14 sm:space-y-20 lg:space-y-24">

        <!-- 3. KEUNGGULAN / FEATURES SECTION -->
        @include('landing.partials.features')

        <!-- 4. ALUR KERJA / HOW IT WORKS SECTION -->
        @include('landing.partials.how-it-works')

        <!-- 5. KATALOG BAHAN BAKU / CATEGORIES SECTION -->
        @include('landing.partials.categories')

        <!-- 6. FAQ SECTION & ACCORDION ITEMS -->
        @include('landing.partials.faq')

        <!-- 7. CTA BANNER -->
        @include('landing.partials.cta-banner')

    </main>

    <!-- 8. FOOTER -->
    @include('landing.partials.footer')

</body>
</html>

