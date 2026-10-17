<section class="bg-slate-900 text-white rounded-3xl p-10 sm:p-14 lg:p-20 text-center border border-slate-800 shadow-sm">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6 tracking-tight leading-tight">
            Mulai Pengadaan Bahan Baku Terjadwal untuk Dapur Anda
        </h2>

        <p class="text-slate-300 mb-10 text-base lg:text-lg leading-relaxed max-w-2xl mx-auto font-normal">
            Pastikan dapur Anda selalu siap setiap hari tanpa repot belanja subuh. Hubungi peternak dan pemasok lokal terpercaya di SORAi.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register.umkm') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-base shadow-xs transition-colors inline-flex items-center justify-center gap-2 cursor-pointer">
                <span>Daftar Sebagai Dapur UMKM</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>

            <a href="{{ route('register.supplier') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-base border border-slate-700 transition-colors cursor-pointer">
                Daftar Sebagai Pemasok
            </a>
        </div>

        <div class="mt-12 pt-8 border-t border-slate-800 flex flex-wrap items-center justify-center gap-8 text-xs sm:text-sm text-slate-400">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Pendaftaran Gratis
            </span>
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Verifikasi 1x24 Jam
            </span>
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Garansi Mutu Bahan
            </span>
        </div>
    </div>
</section>


