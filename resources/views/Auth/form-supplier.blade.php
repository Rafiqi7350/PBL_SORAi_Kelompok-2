<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pemasok Bahan Baku - SORAi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-[#FAF2DE] text-gray-900 font-sans antialiased selection:bg-[#F5B800] selection:text-black min-h-screen flex flex-col justify-between py-6 sm:py-10 px-4">

    <header class="w-full max-w-4xl mx-auto mb-6 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-full bg-[#F5B800] text-black font-extrabold text-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                S
            </div>
            <div class="flex flex-col">
                <span class="font-extrabold text-gray-900 text-lg sm:text-xl tracking-tight leading-none group-hover:text-[#DC4E00] transition-colors">SORAi</span>
                <span class="text-[9px] sm:text-[10px] font-bold text-gray-500 tracking-wider uppercase mt-0.5">PORTAL DAPUR UMKM</span>
            </div>
        </a>

        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white hover:bg-gray-100 text-gray-800 font-bold text-xs sm:text-sm shadow-sm transition-all border border-gray-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </header>

    <main class="w-full max-w-4xl mx-auto flex-grow" x-data="{
        form: {
            email: @json(old('email', '')),
            password: '',
            company_name: @json(old('company_name', '')),
            phone: @json(old('phone', '')),
            commodity_id: @json(old('commodity_id', $commodities->first()?->id ?? '')),
            max_capacity: @json(old('max_capacity', '')),
            delivery_area: @json(old('delivery_area', '')),
            nib_number: @json(old('nib_number', '')),
            warehouse_address: @json(old('warehouse_address', ''))
        },
        errors: {},
        validate() {
            this.errors = {};

            if (!this.form.email.trim()) {
                this.errors.email = 'Email wajib diisi.';
            } else if (!/^\S+@\S+\.\S+$/.test(this.form.email.trim())) {
                this.errors.email = 'Format email tidak valid.';
            }

            if (!this.form.password) {
                this.errors.password = 'Password wajib diisi.';
            } else if (this.form.password.length < 8) {
                this.errors.password = 'Password minimal 8 karakter.';
            }

            if (!this.form.company_name.trim()) {
                this.errors.company_name = 'Nama Kelompok Tani / Distributor wajib diisi.';
            }

            if (!this.form.phone.trim()) {
                this.errors.phone = 'Nomor WhatsApp operasional wajib diisi.';
            } else if (!/^[0-9+\s-]{9,20}$/.test(this.form.phone.trim())) {
                this.errors.phone = 'Nomor WhatsApp tidak valid (minimal 9 digit angka).';
            }

            if (!this.form.commodity_id) {
                this.errors.commodity_id = 'Pilih kategori komoditas utama.';
            }

            if (!this.form.max_capacity.trim()) {
                this.errors.max_capacity = 'Kapasitas pasok maksimal wajib diisi.';
            }

            if (!this.form.delivery_area.trim()) {
                this.errors.delivery_area = 'Jangkauan area pengiriman wajib diisi.';
            }

            if (!this.form.nib_number.trim()) {
                this.errors.nib_number = 'Nomor NIB / Legalitas usaha wajib diisi.';
            }

            if (!this.form.warehouse_address.trim()) {
                this.errors.warehouse_address = 'Alamat gudang / lokasi dapur utama wajib diisi.';
            }

            return Object.keys(this.errors).length === 0;
        },
        submitForm(e) {
            if (!this.validate()) {
                e.preventDefault();
            }
        }
    }">

        <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 lg:p-12 shadow-2xl border-2 border-gray-900/10">

            <div class="text-center mb-8 sm:mb-10">
                <span class="font-mono text-xs sm:text-sm font-bold text-[#DC4E00] tracking-widest uppercase block mb-2">
                    REGISTRASI AKUN BARU
                </span>
                <h1 class="text-2xl sm:text-4xl lg:text-4xl font-extrabold text-gray-900 tracking-tight uppercase leading-tight">
                    DAFTAR SEBAGAI <span class="text-[#DC4E00]">PEMASOK BAHAN BAKU</span>
                </h1>
            </div>

            <form action="{{ route('register.supplier.store') }}" method="POST" @submit="submitForm" novalidate class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    <div>
                        <label for="email" class="font-bold text-sm text-gray-900 mb-1.5 block">Email</label>
                        <input type="email"
                               id="email"
                               name="email"
                               x-model="form.email"
                               placeholder="supplier@dapursambal.com"
                               :class="{ 'border-red-500 bg-red-50': errors.email, 'border-gray-900 bg-[#FFF9E6]': !errors.email }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.email">
                            <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.email"></p>
                        </template>
                        @error('email')
                            <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="font-bold text-sm text-gray-900 mb-1.5 block">Password</label>
                        <input type="password"
                               id="password"
                               name="password"
                               x-model="form.password"
                               placeholder="••••••••"
                               :class="{ 'border-red-500 bg-red-50': errors.password, 'border-gray-900 bg-[#FFF9E6]': !errors.password }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.password">
                            <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.password"></p>
                        </template>
                        @error('password')
                            <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    <div>
                        <label for="company_name" class="font-bold text-sm text-gray-900 mb-1.5 block">Nama Kelompok Tani / Distributor</label>
                        <input type="text"
                               id="company_name"
                               name="company_name"
                               x-model="form.company_name"
                               placeholder="Dapur Sambal Malang"
                               :class="{ 'border-red-500 bg-red-50': errors.company_name, 'border-gray-900 bg-[#FFF9E6]': !errors.company_name }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.company_name">
                            <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.company_name"></p>
                        </template>
                        @error('company_name')
                            <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="font-bold text-sm text-gray-900 mb-1.5 block">Nomor WhatsApp Operasional</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               x-model="form.phone"
                               placeholder="081234567890"
                               :class="{ 'border-red-500 bg-red-50': errors.phone, 'border-gray-900 bg-[#FFF9E6]': !errors.phone }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.phone">
                            <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.phone"></p>
                        </template>
                        @error('phone')
                            <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-[#FAF5E6] rounded-2xl p-5 sm:p-7 border border-amber-200/80 my-6 shadow-inner">
                    <div class="font-mono text-xs sm:text-sm font-bold text-[#DC4E00] tracking-wider uppercase mb-5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>DETAIL KAPASITAS PASOK PEMASOK</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                        <div>
                            <label for="commodity_id" class="font-bold text-sm text-gray-900 mb-1.5 block">Kategori Komoditas Utama</label>
                            <select id="commodity_id"
                                    name="commodity_id"
                                    x-model="form.commodity_id"
                                    :class="{ 'border-red-500': errors.commodity_id, 'border-gray-900': !errors.commodity_id }"
                                    class="w-full px-4 py-3 rounded-xl bg-white border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 shadow-sm cursor-pointer">
                                <option value="" disabled>-- Pilih Kategori Komoditas --</option>
                                @foreach($commodities as $commodity)
                                    <option value="{{ $commodity->id }}">
                                        {{ $commodity->name }}
                                    </option>
                                @endforeach
                            </select>
                            <template x-if="errors.commodity_id">
                                <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.commodity_id"></p>
                            </template>
                            @error('commodity_id')
                                <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="max_capacity" class="font-bold text-sm text-gray-900 mb-1.5 block">Kapasitas Pasok Maksimal</label>
                            <input type="text"
                                   id="max_capacity"
                                   name="max_capacity"
                                   x-model="form.max_capacity"
                                   placeholder="1.500 kg / Minggu"
                                   :class="{ 'border-red-500 bg-red-50': errors.max_capacity, 'border-gray-900 bg-white': !errors.max_capacity }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.max_capacity">
                                <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.max_capacity"></p>
                            </template>
                            @error('max_capacity')
                                <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="delivery_area" class="font-bold text-sm text-gray-900 mb-1.5 block">Jangkauan Area Pengiriman Armada</label>
                            <input type="text"
                                   id="delivery_area"
                                   name="delivery_area"
                                   x-model="form.delivery_area"
                                   placeholder="Kota Malang, Kab. Malang, Batu"
                                   :class="{ 'border-red-500 bg-red-50': errors.delivery_area, 'border-gray-900 bg-white': !errors.delivery_area }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.delivery_area">
                                <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.delivery_area"></p>
                            </template>
                            @error('delivery_area')
                                <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nib_number" class="font-bold text-sm text-gray-900 mb-1.5 block">Nomor NIB / Legalitas Usaha</label>
                            <input type="text"
                                   id="nib_number"
                                   name="nib_number"
                                   x-model="form.nib_number"
                                   placeholder="NIB-9120003810"
                                   :class="{ 'border-red-500 bg-red-50': errors.nib_number, 'border-gray-900 bg-white': !errors.nib_number }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.nib_number">
                                <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.nib_number"></p>
                            </template>
                            @error('nib_number')
                                <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="warehouse_address" class="font-bold text-sm text-gray-900 mb-1.5 block">Alamat Gudang / Lokasi Dapur Utama</label>
                    <textarea id="warehouse_address"
                              name="warehouse_address"
                              x-model="form.warehouse_address"
                              rows="3"
                              placeholder="Jl. Soekarno Hatta No. 12, Kota Malang"
                              :class="{ 'border-red-500 bg-red-50': errors.warehouse_address, 'border-gray-900 bg-[#FFF9E6]': !errors.warehouse_address }"
                              class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all"></textarea>
                    <template x-if="errors.warehouse_address">
                        <p class="text-xs font-bold text-red-600 mt-1" x-text="errors.warehouse_address"></p>
                    </template>
                    @error('warehouse_address')
                        <p class="text-xs font-bold text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full py-4 px-6 rounded-full bg-[#F5B800] hover:bg-[#e0a800] text-black font-extrabold text-base sm:text-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all cursor-pointer border-2 border-gray-900 flex items-center justify-center gap-2 mt-8">
                    <span>Selesaikan Pendaftaran Pemasok Lokal →</span>
                </button>

            </form>

        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-8 max-w-md w-full text-center shadow-2xl border-2 border-gray-900">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 font-extrabold text-3xl flex items-center justify-center mx-auto mb-4">
                        ✓
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Pendaftaran Berhasil!</h3>
                    <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                        {{ session('success') }}
                    </p>
                    <a href="{{ url('/') }}" class="inline-block w-full py-3.5 rounded-full bg-[#DC4E00] text-white font-extrabold text-sm hover:bg-[#C83E00] transition-all">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif

    </main>

    <footer class="w-full max-w-4xl mx-auto mt-8 text-center text-xs font-semibold text-gray-500">
        &copy; {{ date('Y') }} SORAi Kelompok-2. Hak Cipta Dilindungi Undang-Undang.
    </footer>

</body>
</html>
