<section id="beranda" class="relative w-full overflow-hidden bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 text-white pt-10 sm:pt-14 lg:pt-16 pb-20 sm:pb-28 lg:pb-36">
    <!-- Background Organic Geometric Swoosh (Vibrant clean tones, no dark brown tint) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <svg class="absolute -right-20 -top-20 w-[650px] lg:w-[850px] h-full opacity-25 mix-blend-overlay" viewBox="0 0 600 600" fill="none">
            <path d="M150,0 C320,120 220,380 600,600 L600,0 Z" fill="url(#hero-swoosh-grad)" />
            <defs>
                <linearGradient id="hero-swoosh-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.8" />
                    <stop offset="100%" stop-color="#fef08a" stop-opacity="0.3" />
                </linearGradient>
            </defs>
        </svg>
    </div>

    <!-- Hero Content Container (Left Aligned, Compact Spacing) -->
    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left">
        <div class="max-w-3xl lg:max-w-4xl">

            <h1 class="text-3xl sm:text-5xl lg:text-[3.25rem] font-extrabold text-white tracking-tight leading-[1.15] mb-4 sm:mb-5">
                HUBUNGKAN UMKM KULINER DENGAN PEMASOK <span class="text-[#fef08a]">BAHAN BAKU LOKAL</span>
            </h1>

            <p class="text-white/95 text-base sm:text-lg lg:text-xl font-normal leading-relaxed max-w-2xl mb-6 sm:mb-8">
                Solusi pengadaan bahan baku segar harian untuk dapur usaha kuliner, resto, warung, dan katering langsung dari peternak, petani, serta pemasok lokal tangan pertama.
            </p>

            <!-- Two Registration Action Buttons (Compact Gap) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-start gap-3 sm:gap-4">
                <!-- Button 1: Daftar Sebagai UMKM (Transparent -> Solid White & Orange on Hover) -->
                <a href="{{ route('register.umkm') }}"
                   class="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-white/10 hover:bg-white text-white hover:text-orange-600 border-2 border-white/80 hover:border-white font-extrabold text-sm sm:text-base backdrop-blur-xs shadow-md hover:shadow-2xl hover:-translate-y-1 active:translate-y-0 transition-all duration-300 ease-out cursor-pointer">
                    <svg class="w-5 h-5 text-white group-hover:text-orange-600 group-hover:scale-110 group-hover:-rotate-6 transition-all duration-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Daftar Sebagai UMKM</span>
                </a>

                <!-- Button 2: Daftar Sebagai Pemasok (Transparent -> Solid White & Orange on Hover) -->
                <a href="{{ route('register.supplier') }}"
                   class="group w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-white/10 hover:bg-white text-white hover:text-orange-600 border-2 border-white/80 hover:border-white font-extrabold text-sm sm:text-base backdrop-blur-xs shadow-md hover:shadow-2xl hover:-translate-y-1 active:translate-y-0 transition-all duration-300 ease-out cursor-pointer">
                    <svg class="w-5 h-5 text-white group-hover:text-orange-600 group-hover:scale-110 group-hover:translate-x-1 transition-all duration-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Daftar Sebagai Pemasok</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Organic Swoosh Wave (Raised curve with balanced height) -->
    <div class="absolute bottom-0 inset-x-0 overflow-hidden leading-none z-10 pointer-events-none">
        <svg class="relative block w-full h-16 sm:h-24 lg:h-32 text-slate-50 fill-current" viewBox="0 0 1440 180" preserveAspectRatio="none">
            <path d="M0,80 C320,160 760,40 1440,10 L1440,180 L0,180 Z"></path>
        </svg>
    </div>
</section>




