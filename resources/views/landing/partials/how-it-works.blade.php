<section id="alur">
    <div class="text-center max-w-3xl mx-auto mb-12 lg:mb-16">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200/80 text-orange-700 text-xs font-semibold uppercase tracking-wider">
            Alur Pengadaan
        </span>
        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
            Bagaimana SORAi Membantu Dapur Anda?
        </h2>
        <p class="text-slate-600 text-base sm:text-lg mt-4 leading-relaxed max-w-2xl mx-auto">
            Hanya 3 langkah sederhana untuk memastikan seluruh stok bahan baku dapur kuliner Anda terisi rapi setiap hari.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
        @include('landing.partials.step-card', [
            'stepNumber' => 1,
            'title' => 'Rencanakan Kebutuhan Rutin',
            'description' => 'Tentukan daftar bahan (ayam, sayur, sembako) dan tetapkan siklus pengiriman harian atau mingguan sesuai volume produksi dapur Anda.',
            'badge' => 'Fleksibel & Terjadwal',
            'iconBg' => 'bg-amber-50',
            'iconColor' => 'text-amber-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>'
        ])

        @include('landing.partials.step-card', [
            'stepNumber' => 2,
            'title' => 'Pemasok Lokal Terverifikasi',
            'description' => 'Sistem SORAi mencocokkan pesanan Anda dengan peternak & pemasok terverifikasi terdekat untuk jaminan stok dan kepastian harga grosir.',
            'badge' => 'Mitra Terpercaya',
            'iconBg' => 'bg-orange-50',
            'iconColor' => 'text-orange-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
        ])

        @include('landing.partials.step-card', [
            'stepNumber' => 3,
            'title' => 'Terima Subuh & Garansi Mutu',
            'description' => 'Bahan tiba subuh hari sebelum aktivitas masak dimulai. Cek kualitas fisik secara langsung dengan perlindungan revisi invoice instan.',
            'badge' => 'Pengecekan Fisik Langsung',
            'iconBg' => 'bg-emerald-50',
            'iconColor' => 'text-emerald-600',
            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
        ])
    </div>
</section>

