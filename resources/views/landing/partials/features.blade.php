<section id="fitur" class="mt-6 sm:mt-8 grid grid-cols-1 md:grid-cols-3 gap-5 lg:gap-6">
    @include('landing.partials.feature-card', [
        'title' => 'PASOKAN SUBUH TERJAMIN',
        'description' => 'Jadwal pengiriman subuh tepat waktu langsung ke lokasi dapur UMKM Anda.',
        'iconBg' => 'bg-[#F5B800]',
        'iconColor' => 'text-black',
        'icon' => '<svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>'
    ])

    @include('landing.partials.feature-card', [
        'title' => 'HARGA GROSIR SPESIAL',
        'description' => 'Harga pasokan langsung dari peternak & distributor tanpa rantai perantara.',
        'iconBg' => 'bg-[#DC4E00]',
        'iconColor' => 'text-white',
        'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 8v2m0-10e-1 10a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    ])

    @include('landing.partials.feature-card', [
        'title' => 'JAMINAN GARANSI MUTU',
        'description' => 'Sistem revisi tagihan transparan jika stok pasokan tidak sesuai spesifikasi.',
        'iconBg' => 'bg-[#DC4E00]',
        'iconColor' => 'text-white',
        'icon' => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
    ])
</section>
