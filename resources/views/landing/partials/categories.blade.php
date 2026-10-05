<section id="kategori" class="mt-16 sm:mt-24">
    <div class="bg-white rounded-[2rem] p-8 sm:p-12 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
            <div>
                <span class="px-4 py-1.5 rounded-full bg-[#FAF2DE] text-[#DC4E00] font-mono text-xs font-bold tracking-widest uppercase">KATALOG SEGAR</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-gray-900 mt-3 tracking-tight">Kategori Bahan Baku Favorit</h2>
            </div>
            <a href="#" class="px-6 py-3 rounded-full bg-[#DC4E00] text-white font-bold text-sm hover:bg-[#C83E00] transition-colors self-start md:self-auto cursor-pointer">
                Lihat Katalog Lengkap →
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @include('landing.partials.category-card', [
                'emoji' => '🍗',
                'title' => 'Daging & Unggas',
                'description' => 'Ayam potong segar, filet dada, daging sapi segar lokal & telur ayam.',
                'emojiBg' => 'bg-amber-400',
                'emojiTextColor' => 'text-black'
            ])

            @include('landing.partials.category-card', [
                'emoji' => '🥦',
                'title' => 'Sayur & Buah Dapur',
                'description' => 'Cabai merah keriting, bawang merah, bawang putih, tomat & sayur hijau.',
                'emojiBg' => 'bg-orange-500',
                'emojiTextColor' => 'text-white'
            ])

            @include('landing.partials.category-card', [
                'emoji' => '🌶️',
                'title' => 'Bumbu & Rempah',
                'description' => 'Bumbu giling segar, lengkuas, jahe, kemiri, santan murni & rempah pilihan.',
                'emojiBg' => 'bg-amber-400',
                'emojiTextColor' => 'text-black'
            ])

            @include('landing.partials.category-card', [
                'emoji' => '🛢️',
                'title' => 'Sembako & Minyak',
                'description' => 'Minyak goreng jerigen 18L, beras premium, tepung terigu & gula pasir.',
                'emojiBg' => 'bg-orange-500',
                'emojiTextColor' => 'text-white'
            ])
        </div>
    </div>
</section>
