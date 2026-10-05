<section id="faq" class="mt-16 sm:mt-24 w-full max-w-6xl mx-auto" x-data="{ activeFaq: null }">
    <div class="text-center mb-10 sm:mb-12">
        <span class="px-4 py-1.5 rounded-full bg-white text-[#DC4E00] font-mono text-xs font-bold tracking-widest uppercase shadow-sm">
            PERTANYAAN UMUM
        </span>
        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 mt-3 tracking-tight">
            Sering Ditanyakan
        </h2>
    </div>

    <div class="space-y-4">
        @include('landing.partials.faq-item', [
            'id' => 1,
            'question' => 'Bagaimana sistem garansi mutu di SORAi jika bahan tidak segar?',
            'answer' => 'Saat bahan baku tiba di dapur subuh hari, tim dapur dapat langsung mengecek kualitas fisik. Jika ada item yang tidak sesuai spesifikasi, foto bukti dapat diunggah melalui portal dan tagihan akan otomatis direvisi secara transparan.'
        ])

        @include('landing.partials.faq-item', [
            'id' => 2,
            'question' => 'Siapa saja yang bisa mendaftar sebagai Pemasok Lokal?',
            'answer' => 'Peternak ayam/sapi, petani lokal, penggilingan bumbu, agen sembako, dan distributor bahan baku daerah dapat mendaftar. Tim SORAi akan melakukan verifikasi standar kualitas sebelum akun aktif.'
        ])

        @include('landing.partials.faq-item', [
            'id' => 3,
            'question' => 'Apakah ada minimal jumlah pembelian untuk UMKM Kuliner?',
            'answer' => 'SORAi dirancang untuk pengadaan rutin UMKM skala kecil hingga menengah. Pembelian tidak didasarkan pada minimum partai besar, melainkan berdasarkan komitmen rencana pengadaan mingguan.'
        ])
    </div>
</section>
