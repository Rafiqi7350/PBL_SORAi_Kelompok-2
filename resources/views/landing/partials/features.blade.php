<section id="fitur">
    <div class="mb-8 lg:mb-10 text-left">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-2 h-2 rounded-full bg-orange-600"></span>
            <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">Layanan Terpadu</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Keunggulan & Layanan Utama Portal
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-1.5 leading-relaxed max-w-2xl">
            Tinggalkan kerepotan belanja pasar subuh dan fluktuasi harga tak menentu dengan sistem pengadaan terstandarisasi.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
        @include('landing.partials.feature-card', [
            'title' => 'Pasokan Subuh Terjamin',
            'description' => 'Jadwal pengiriman subuh tepat waktu tiba sebelum pukul 05:00 WIB, siap diolah juru masak sebelum dapur beroperasi.',
            'badgeText' => 'Tiba Sebelum 05:00 WIB',
            'iconBg' => 'bg-orange-50',
            'iconColor' => 'text-orange-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        ])

        @include('landing.partials.feature-card', [
            'title' => 'Harga Grosir Langsung',
            'description' => 'Harga bersaing langsung dari peternak, petani, & distributor daerah tanpa perantara bertingkat untuk menjaga margin usaha.',
            'badgeText' => 'Langsung Sumber Pertama',
            'iconBg' => 'bg-emerald-50',
            'iconColor' => 'text-emerald-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 8v2m0-10e-1 10a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        ])

        @include('landing.partials.feature-card', [
            'title' => 'Jaminan Garansi Mutu',
            'description' => 'Sortir ketat dan perlindungan komoditas. Jika ada item cacat atau susut fisik, ajukan klaim foto dan tagihan langsung direvisi instan.',
            'badgeText' => 'Revisi Nota Transparan',
            'iconBg' => 'bg-blue-50',
            'iconColor' => 'text-blue-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
        ])
    </div>
</section>

