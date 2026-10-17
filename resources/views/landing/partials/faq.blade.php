<section id="faq" class="w-full max-w-4xl mx-auto" x-data="{ activeFaq: null }">
    <div class="text-center mb-12 lg:mb-16">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200/80 text-orange-700 text-xs font-semibold uppercase tracking-wider">
            Pertanyaan Umum
        </span>
        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 mt-4 tracking-tight">
            Sering Ditanyakan
        </h2>
        <p class="text-slate-600 text-base sm:text-lg mt-3">
            Informasi penting seputar kemitraan pengadaan bahan baku di SORAi.
        </p>
    </div>

    <div class="space-y-4">
        @include('landing.partials.faq-item', [
            'id' => 1,
            'question' => 'Bagaimana sistem garansi mutu di SORAi jika bahan tidak segar?',
            'answer' => 'Saat bahan baku tiba di dapur subuh hari, tim dapur dapat langsung mengecek kualitas fisik. Jika ada item yang tidak sesuai spesifikasi, foto bukti dapat diunggah melalui portal dan tagihan akan otomatis direvisi secara transparan tanpa proses rumit.'
        ])

        @include('landing.partials.faq-item', [
            'id' => 2,
            'question' => 'Siapa saja yang bisa mendaftar sebagai Pemasok Lokal?',
            'answer' => 'Peternak ayam/sapi, petani lokal, kelompok tani, penggilingan bumbu, agen sembako, dan distributor bahan baku daerah dapat bergabung. Tim kurasi SORAi akan melakukan verifikasi standar kualitas sebelum akun aktif.'
        ])

        @include('landing.partials.faq-item', [
            'id' => 3,
            'question' => 'Apakah ada minimal jumlah pembelian untuk UMKM Kuliner?',
            'answer' => 'SORAi dirancang untuk pengadaan rutin UMKM skala mikro hingga menengah. Pembelian tidak menuntut partai kontainer besar, melainkan berdasarkan komitmen rencana kebutuhan harian atau mingguan yang realistis sesuai dapur Anda.'
        ])

        @include('landing.partials.faq-item', [
            'id' => 4,
            'question' => 'Kapan jam pengiriman pesanan dilakukan?',
            'answer' => 'Pengiriman reguler dilakukan pada dini hari hingga subuh (antara pukul 04:00 - 05:30 WIB) agar bahan baku sudah berada di lokasi dapur sebelum aktivitas produksi memasak dimulai.'
        ])
    </div>
</section>

