<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun Pemasok Lokal - SORAI</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN v3 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'sorai-orange': '#ea580c',
              'sorai-orange-hover': '#c2410c',
              'sorai-bg': '#f3f4f6',
              'sorai-input': '#f1f2f4',
            },
            fontFamily: {
              sans: ['Plus Jakarta Sans', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
            }
          }
        }
      }
    </script>

    <!-- Styles & Scripts via Vite & Alpine -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f3f4f6;
            color: #111827;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>

    <script>
        function registerSupplierForm() {
            return {
                form: {
                    email: @json(old('email', '')),
                    password: '',
                    password_confirmation: '',
                    company_name: @json(old('company_name', '')),
                    phone: @json(old('phone', '')),
                    commodity_id: @json(old('commodity_id', '')),
                    max_capacity: @json(old('max_capacity', '')),
                    delivery_area: @json(old('delivery_area', '')),
                    nib_number: @json(old('nib_number', '')),
                    warehouse_address: @json(old('warehouse_address', ''))
                },
                showPassword: false,
                showConfirmPassword: false,
                errors: {},
                validateField: function(field) {
                    delete this.errors[field];
                    if (field === 'email') {
                        var val = (this.form.email || '').trim();
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!val) this.errors.email = 'Email wajib diisi.';
                        else if (!emailRegex.test(val)) this.errors.email = 'Format email tidak valid.';
                    }
                    if (field === 'password') {
                        if (!this.form.password) this.errors.password = 'Kata sandi wajib diisi.';
                        else if (this.form.password.length < 8) this.errors.password = 'Kata sandi minimal 8 karakter.';
                    }
                    if (field === 'password_confirmation') {
                        if (!this.form.password_confirmation) this.errors.password_confirmation = 'Konfirmasi kata sandi wajib diisi.';
                        else if (this.form.password !== this.form.password_confirmation) this.errors.password_confirmation = 'Konfirmasi kata sandi tidak cocok.';
                    }
                    if (field === 'company_name') {
                        if (!this.form.company_name.trim()) this.errors.company_name = 'Nama kelompok/distributor wajib diisi.';
                    }
                    if (field === 'phone') {
                        if (!this.form.phone.trim()) this.errors.phone = 'Nomor WhatsApp wajib diisi.';
                    }
                    if (field === 'commodity_id') {
                        if (!this.form.commodity_id) this.errors.commodity_id = 'Pilih kategori komoditas utama.';
                    }
                    if (field === 'max_capacity') {
                        if (!this.form.max_capacity.trim()) this.errors.max_capacity = 'Kapasitas pasok wajib diisi.';
                    }
                    if (field === 'delivery_area') {
                        if (!this.form.delivery_area.trim()) this.errors.delivery_area = 'Area pengiriman wajib diisi.';
                    }
                    if (field === 'warehouse_address') {
                        if (!this.form.warehouse_address.trim()) this.errors.warehouse_address = 'Alamat gudang wajib diisi.';
                    }
                },
                validateAll: function() {
                    var fields = ['email', 'password', 'password_confirmation', 'company_name', 'phone', 'commodity_id', 'max_capacity', 'delivery_area', 'warehouse_address'];
                    for (var i = 0; i < fields.length; i++) {
                        this.validateField(fields[i]);
                    }
                    return Object.keys(this.errors).length === 0;
                },
                submitForm: function(e) {
                    if (!this.validateAll()) {
                        e.preventDefault();
                    }
                }
            };
        }
    </script>
</head>
<body class="min-h-screen bg-[#f3f4f6] text-gray-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white">

    <!-- Main Content: Registration Card -->
    <main class="w-full max-w-2xl mx-auto px-4 py-8 my-auto flex-grow flex flex-col justify-center" x-data="registerSupplierForm()">

        <!-- CTA Kembali ke Landing Page -->
        <div class="w-full mb-4 flex items-center justify-start px-1">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 hover:text-[#ea580c] hover:bg-orange-50 font-bold text-xs rounded-full shadow-sm border border-gray-200/80 transition group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

        <div class="w-full bg-white rounded-[2rem] p-8 sm:p-12 shadow-[0_10px_35px_rgba(0,0,0,0.06)] space-y-6">

            <!-- Title & Subtitle -->
            <div class="text-center space-y-2">
                <span class="text-xs font-bold text-orange-600 uppercase font-mono tracking-wider block">
                    REGISTRASI AKUN BARU
                </span>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    DAFTAR SEBAGAI <span class="text-gray-900">PEMASOK BAHAN BAKU</span>
                </h1>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    Hubungkan pasokan komoditas segar Anda langsung ke ratusan ekosistem dapur UMKM kuliner di wilayah operasional Anda.
                </p>
            </div>

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium space-y-1">
                    <div class="font-bold">Mohon periksa kembali formulir Anda:</div>
                    <ul class="list-disc list-inside text-[11px] text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.supplier.store') }}" method="POST" @submit="submitForm" class="space-y-4 text-xs font-semibold">
                @csrf

                <!-- Informasi Akun: Email -->
                <div>
                    <label for="email" class="block text-gray-700 mb-1.5">Email Pemasok Resmi</label>
                    <input type="email"
                           id="email"
                           name="email"
                           x-model="form.email"
                           @input="validateField('email')"
                           required
                           placeholder="contoh: supplier@poultrymalang.com"
                           class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                    <template x-if="errors.email">
                        <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.email"></p>
                    </template>
                </div>

                <!-- Password & Konfirmasi Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-gray-700 mb-1.5">Kata Sandi</label>
                        <input type="password"
                               id="password"
                               name="password"
                               x-model="form.password"
                               @input="validateField('password')"
                               required
                               placeholder="Minimal 8 karakter"
                               class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                        <template x-if="errors.password">
                            <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.password"></p>
                        </template>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-gray-700 mb-1.5">Konfirmasi Kata Sandi</label>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               x-model="form.password_confirmation"
                               @input="validateField('password_confirmation')"
                               required
                               placeholder="Ulangi kata sandi"
                               class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                        <template x-if="errors.password_confirmation">
                            <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.password_confirmation"></p>
                        </template>
                    </div>
                </div>

                <!-- Identitas Kelompok Tani / Distributor -->
                <div class="pt-2 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="block text-gray-700 mb-1.5">Nama Kelompok Tani / Distributor</label>
                            <input type="text"
                                   id="company_name"
                                   name="company_name"
                                   x-model="form.company_name"
                                   @input="validateField('company_name')"
                                   required
                                   placeholder="contoh: Kelompok Tani Poultry Malang"
                                   class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                            <template x-if="errors.company_name">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.company_name"></p>
                            </template>
                        </div>

                        <div>
                            <label for="phone" class="block text-gray-700 mb-1.5">Nomor WhatsApp Operasional</label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   x-model="form.phone"
                                   @input="validateField('phone')"
                                   required
                                   placeholder="contoh: 081234567890"
                                   class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                            <template x-if="errors.phone">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.phone"></p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Detail Kapasitas Pasok Pemasok -->
                <div class="bg-[#f8f9fa] rounded-2xl p-5 border border-gray-100 space-y-4">
                    <span class="text-[11px] font-bold text-orange-600 uppercase tracking-wider font-mono block">
                        DETAIL KAPASITAS PASOK
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="commodity_id" class="block text-gray-700 mb-1.5">Kategori Komoditas Utama</label>
                            <select id="commodity_id"
                                    name="commodity_id"
                                    x-model="form.commodity_id"
                                    @change="validateField('commodity_id')"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-white text-gray-900 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 shadow-sm cursor-pointer">
                                <option value="" disabled>-- Pilih Komoditas --</option>
                                @foreach($commodities as $commodity)
                                    <option value="{{ $commodity->id }}">{{ $commodity->name }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.commodity_id">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.commodity_id"></p>
                            </template>
                        </div>

                        <div>
                            <label for="max_capacity" class="block text-gray-700 mb-1.5">Kapasitas Pasok Maksimal</label>
                            <input type="text"
                                   id="max_capacity"
                                   name="max_capacity"
                                   x-model="form.max_capacity"
                                   @input="validateField('max_capacity')"
                                   required
                                   placeholder="contoh: 1.500 kg / Minggu"
                                   class="w-full px-4 py-3 rounded-xl bg-white text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 shadow-sm transition">
                            <template x-if="errors.max_capacity">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.max_capacity"></p>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="delivery_area" class="block text-gray-700 mb-1.5">Jangkauan Area Pengiriman Armada</label>
                            <input type="text"
                                   id="delivery_area"
                                   name="delivery_area"
                                   x-model="form.delivery_area"
                                   @input="validateField('delivery_area')"
                                   required
                                   placeholder="Kota Malang, Kab. Malang, Batu"
                                   class="w-full px-4 py-3 rounded-xl bg-white text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 shadow-sm transition">
                            <template x-if="errors.delivery_area">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.delivery_area"></p>
                            </template>
                        </div>

                        <div>
                            <label for="nib_number" class="block text-gray-700 mb-1.5">Nomor NIB / Legalitas Usaha (Opsional)</label>
                            <input type="text"
                                   id="nib_number"
                                   name="nib_number"
                                   x-model="form.nib_number"
                                   placeholder="contoh: NIB-9120003810"
                                   class="w-full px-4 py-3 rounded-xl bg-white text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 shadow-sm transition">
                        </div>
                    </div>
                </div>

                <!-- Alamat Gudang -->
                <div>
                    <label for="warehouse_address" class="block text-gray-700 mb-1.5">Alamat Gudang / Lokasi Sentra Pengiriman</label>
                    <textarea id="warehouse_address"
                              name="warehouse_address"
                              rows="2"
                              x-model="form.warehouse_address"
                              @input="validateField('warehouse_address')"
                              required
                              placeholder="contoh: Jl. Raya Kepanjen No. 12, Kab. Malang"
                              class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition"></textarea>
                    <template x-if="errors.warehouse_address">
                        <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.warehouse_address"></p>
                    </template>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition active:scale-[0.99] cursor-pointer mt-4 flex items-center justify-center gap-1">
                    <span>Selesaikan Pendaftaran Pemasok Lokal →</span>
                </button>

                <p class="text-center text-xs text-gray-600 font-medium pt-3">
                    Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-[#ea580c] hover:underline">Masuk di sini</a>
                </p>

                <p class="text-[11px] text-gray-400 text-center font-normal pt-1">
                    Dengan mendaftar, Anda menyetujui Ketentuan Layanan & Kebijakan Privasi SORAI.
                </p>
            </form>

        </div>

    </main>

    <!-- Footer Simple -->
    <footer class="w-full py-5 text-center text-xs text-gray-500 font-medium">
        &copy; {{ date('Y') }} SORAI — Portal Kemitraan UMKM & Pemasok Bahan Baku Lokal.
    </footer>

</body>
</html>
