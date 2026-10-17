<section id="kategori">
    <div class="bg-white rounded-3xl p-8 sm:p-12 lg:p-16 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 sm:mb-12 gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200/80 text-orange-700 text-xs font-semibold uppercase tracking-wider">
                    Katalog Komoditas
                </span>
                <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
                    Kategori Bahan Baku Utama
                </h2>
                <p class="text-slate-600 text-base sm:text-lg mt-3 leading-relaxed">
                    Dipasok langsung oleh peternak, petani, dan distributor resmi lokal.
                </p>
            </div>
            <a href="{{ route('register.umkm') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm transition-colors shadow-xs self-start md:self-auto cursor-pointer shrink-0">
                <span>Daftar & Pesan Bahan</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @include('landing.partials.category-card', [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/></svg>',
                'iconBg' => 'bg-red-50',
                'iconColor' => 'text-red-600',
                'title' => 'Daging & Unggas',
                'description' => 'Ayam potong karkas segar, fillet dada, paha, telur ayam ras, & daging sapi lokal.',
                'tag' => 'Potong Subuh',
                'popular' => 'Fillet Dada, Ayam Broiler'
            ])

            @include('landing.partials.category-card', [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
                'iconBg' => 'bg-emerald-50',
                'iconColor' => 'text-emerald-600',
                'title' => 'Sayur & Hortikultura',
                'description' => 'Cabai merah keriting, bawang merah Brebes, bawang putih kating, tomat, & sayuran hijau.',
                'tag' => 'Segar Kebun',
                'popular' => 'Cabai Keriting, Bawang Merah'
            ])

            @include('landing.partials.category-card', [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
                'iconBg' => 'bg-amber-50',
                'iconColor' => 'text-amber-600',
                'title' => 'Bumbu & Rempah',
                'description' => 'Bumbu giling murni tanpa pengawet, jahe emprit, lengkuas, kunyit, kemiri, & santan murni.',
                'tag' => 'Giling Murni',
                'popular' => 'Bumbu Giling, Jahe, Santan'
            ])

            @include('landing.partials.category-card', [
                'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
                'iconBg' => 'bg-blue-50',
                'iconColor' => 'text-blue-600',
                'title' => 'Sembako & Minyak',
                'description' => 'Minyak goreng jerigen 18L, beras premium kemasan 25kg, tepung terigu, & gula pasir.',
                'tag' => 'Grosir Pabrik',
                'popular' => 'Minyak 18L, Beras 25kg'
            ])
        </div>
    </div>
</section>


