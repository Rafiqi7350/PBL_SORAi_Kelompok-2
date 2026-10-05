<section id="beranda" class="bg-[#DC4E00] rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 lg:p-14 shadow-xl text-white relative overflow-hidden transition-all">
    <div class="relative z-10 max-w-4xl">
        <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-[#F5B800] text-black text-xs sm:text-sm font-mono font-bold tracking-wider uppercase shadow-sm mb-6 sm:mb-8">
            EKOSISTEM PENGADAAN RUTIN B2B
        </div>

        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.12] mb-6 sm:mb-8 uppercase">
            HUBUNGKAN UMKM KULINER DENGAN PEMASOK <span class="text-[#F5B800]">BAHAN BAKU LOKAL</span>
        </h1>

        <p class="text-white/90 text-sm sm:text-base lg:text-lg max-w-3xl leading-relaxed mb-8 sm:mb-10 font-medium">
            Bukan sekadar marketplace eceran satu kali beli. SORAi membantu UMKM kuliner merencanakan kebutuhan rutin, mengamankan stok pemasok lokal, dan memantau pemenuhan kontrak secara transparan.
        </p>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
            <a href="#" class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-[#F5B800] hover:bg-[#e0a800] text-black font-extrabold text-sm sm:text-base shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all gap-2 group cursor-pointer">
                <span>Daftar Sebagai UMKM Kuliner</span>
                <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>

            <a href="{{ route('register.supplier') }}" class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-white hover:bg-gray-50 text-gray-900 font-extrabold text-sm sm:text-base shadow-md hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer">
                Daftar Sebagai Pemasok Lokal
            </a>
        </div>
    </div>
</section>
