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

    <script>
        function supplierRegistration() {
            return {
                form: {
                    email: @json(old('email', '')),
                    password: '',
                    password_confirmation: '',
                    company_name: @json(old('company_name', '')),
                    phone: @json(old('phone', '')),
                    commodity_id: @json(old('commodity_id', $commodities->first()?->id ?? '')),
                    max_capacity: @json(old('max_capacity', '')),
                    delivery_area: @json(old('delivery_area', '')),
                    nib_number: @json(old('nib_number', '')),
                    warehouse_address: @json(old('warehouse_address', ''))
                },
                touched: {
                    email: false,
                    password: false,
                    password_confirmation: false,
                    company_name: false,
                    phone: false,
                    commodity_id: false,
                    max_capacity: false,
                    delivery_area: false,
                    nib_number: false,
                    warehouse_address: false
                },
                errors: {},
                popupWarning: {
                    show: false,
                    title: '',
                    message: '',
                    errorsList: [],
                    timeout: null
                },

                validateField(field) {
                    delete this.errors[field];

                    if (field === 'email') {
                        const val = (this.form.email || '').trim();
                        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!val) {
                            this.errors.email = 'Email wajib diisi.';
                        } else if (!emailRegex.test(val)) {
                            this.errors.email = 'Format email tidak valid. Masukkan email resmi yang benar (contoh: nama@domain.com).';
                        }
                    }

                    if (field === 'password') {
                        if (!this.form.password) {
                            this.errors.password = 'Kata sandi wajib diisi.';
                        } else if (this.form.password.length < 8) {
                            this.errors.password = 'Kata sandi minimal 8 karakter.';
                        }
                        if (this.touched.password_confirmation && this.form.password_confirmation) {
                            this.validateField('password_confirmation');
                        }
                    }

                    if (field === 'password_confirmation') {
                        if (!this.form.password_confirmation) {
                            this.errors.password_confirmation = 'Konfirmasi kata sandi wajib diisi.';
                        } else if (this.form.password !== this.form.password_confirmation) {
                            this.errors.password_confirmation = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
                        }
                    }

                    if (field === 'company_name') {
                        if (!(this.form.company_name || '').trim()) {
                            this.errors.company_name = 'Nama Kelompok Tani / Distributor wajib diisi.';
                        }
                    }

                    if (field === 'phone') {
                        const val = (this.form.phone || '').trim();
                        if (!val) {
                            this.errors.phone = 'Nomor WhatsApp operasional wajib diisi.';
                        } else if (!/^[0-9+\s-]{9,20}$/.test(val)) {
                            this.errors.phone = 'Nomor WhatsApp tidak valid (minimal 9 digit angka).';
                        }
                    }

                    if (field === 'commodity_id') {
                        if (!this.form.commodity_id) {
                            this.errors.commodity_id = 'Pilih kategori komoditas utama.';
                        }
                    }

                    if (field === 'max_capacity') {
                        if (!(this.form.max_capacity || '').trim()) {
                            this.errors.max_capacity = 'Kapasitas pasok maksimal wajib diisi.';
                        }
                    }

                    if (field === 'delivery_area') {
                        if (!(this.form.delivery_area || '').trim()) {
                            this.errors.delivery_area = 'Jangkauan area pengiriman wajib diisi.';
                        }
                    }

                    if (field === 'nib_number') {
                        if (!(this.form.nib_number || '').trim()) {
                            this.errors.nib_number = 'Nomor NIB / Legalitas usaha wajib diisi.';
                        }
                    }

                    if (field === 'warehouse_address') {
                        if (!(this.form.warehouse_address || '').trim()) {
                            this.errors.warehouse_address = 'Alamat gudang / lokasi dapur utama wajib diisi.';
                        }
                    }
                },

                touchField(field) {
                    this.touched[field] = true;
                    this.validateField(field);

                    if (this.errors[field]) {
                        this.triggerPopup('Peringatan: Field Tidak Sesuai Syarat', this.errors[field], [this.errors[field]]);
                    }
                },

                triggerPopup(title, message, errorsList) {
                    if (this.popupWarning.timeout) {
                        clearTimeout(this.popupWarning.timeout);
                    }
                    this.popupWarning.title = title;
                    this.popupWarning.message = message;
                    this.popupWarning.errorsList = errorsList || [];
                    this.popupWarning.show = true;

                    this.popupWarning.timeout = setTimeout(() => {
                        this.popupWarning.show = false;
                    }, 6000);
                },

                closePopup() {
                    if (this.popupWarning.timeout) {
                        clearTimeout(this.popupWarning.timeout);
                    }
                    this.popupWarning.show = false;
                },

                validateAll() {
                    const self = this;
                    Object.keys(this.touched).forEach(function(f) {
                        self.touched[f] = true;
                    });
                    [
                        'email',
                        'password',
                        'password_confirmation',
                        'company_name',
                        'phone',
                        'commodity_id',
                        'max_capacity',
                        'delivery_area',
                        'nib_number',
                        'warehouse_address'
                    ].forEach(function(f) {
                        self.validateField(f);
                    });
                    return Object.keys(this.errors).length === 0;
                },

                submitForm(e) {
                    if (!this.validateAll()) {
                        e.preventDefault();
                        const errorMessages = Object.values(this.errors);
                        this.triggerPopup(
                            'Peringatan: Formulir Belum Memenuhi Syarat!',
                            'Terdapat beberapa data yang belum diisi atau tidak sesuai ketentuan. Mohon periksa kembali:',
                            errorMessages
                        );

                        this.$nextTick(() => {
                            const firstErrorKey = Object.keys(this.errors)[0];
                            const targetEl = document.getElementById(firstErrorKey);
                            if (targetEl) {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                targetEl.focus();
                            }
                        });
                    }
                }
            };
        }
    </script>
</head>
<body class="bg-[#FAF2DE] text-gray-900 font-sans antialiased selection:bg-[#F5B800] selection:text-black min-h-screen flex flex-col justify-between py-6 sm:py-10 px-4">

    <!-- Header Navigasi -->
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

    <main class="w-full max-w-4xl mx-auto flex-grow" x-data="supplierRegistration()">

        <!-- Pop-up Notifikasi Peringatan Real-Time -->
        <div x-cloak
             x-show="popupWarning.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
             class="fixed top-6 right-4 sm:right-8 z-50 max-w-md w-full">
            <div class="bg-white border-2 border-[#DC4E00] shadow-2xl rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 relative overflow-hidden">
                <div class="w-2.5 bg-[#DC4E00] absolute inset-y-0 left-0"></div>

                <div class="w-10 h-10 rounded-xl bg-amber-100 text-[#DC4E00] flex-shrink-0 flex items-center justify-center font-extrabold text-xl shadow-sm">
                    ⚠️
                </div>

                <div class="flex-grow pr-6">
                    <h4 class="font-extrabold text-sm text-gray-900 tracking-tight" x-text="popupWarning.title"></h4>
                    <p class="text-xs text-gray-600 mt-1 font-medium leading-relaxed" x-text="popupWarning.message"></p>

                    <template x-if="popupWarning.errorsList && popupWarning.errorsList.length > 1">
                        <ul class="mt-2.5 space-y-1 bg-red-50/80 p-2.5 rounded-lg border border-red-200">
                            <template x-for="(err, idx) in popupWarning.errorsList" :key="idx">
                                <li class="text-[11px] font-semibold text-red-700 flex items-start gap-1.5">
                                    <span class="text-red-500">•</span>
                                    <span x-text="err"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                <button type="button"
                        @click="closePopup()"
                        class="text-gray-400 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100 transition absolute top-3 right-3"
                        title="Tutup Peringatan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-10 lg:p-12 shadow-2xl border-2 border-gray-900/10">

            <div class="text-center mb-8 sm:mb-10">
                <span class="font-mono text-xs sm:text-sm font-bold text-[#DC4E00] tracking-widest uppercase block mb-2">
                    REGISTRASI AKUN BARU
                </span>
                <h1 class="text-2xl sm:text-4xl lg:text-4xl font-extrabold text-gray-900 tracking-tight uppercase leading-tight">
                    DAFTAR SEBAGAI <span class="text-[#DC4E00]">PEMASOK BAHAN BAKU</span>
                </h1>
                <p class="text-gray-500 text-sm sm:text-base mt-2 font-medium max-w-xl mx-auto">
                    Hubungkan pasokan komoditas segar Anda langsung ke ratusan ekosistem dapur UMKM kuliner di wilayah operasional Anda.
                </p>
            </div>

            <form action="{{ route('register.supplier.store') }}" method="POST" @submit="submitForm" novalidate class="space-y-6">
                @csrf

                <!-- Informasi Akun (Email) -->
                <div>
                    <label for="email" class="font-bold text-sm text-gray-900 mb-1.5 flex items-center justify-between">
                        <span>Email Pemasok Resmi</span>
                        <span class="text-xs font-normal text-gray-500">Format: supplier@domain.com</span>
                    </label>
                    <div class="relative">
                        <input type="email"
                               id="email"
                               name="email"
                               x-model="form.email"
                               @input="validateField('email')"
                               @blur="touchField('email')"
                               placeholder="supplier@dapursambal.com"
                               :class="{
                                   'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.email,
                                   'border-emerald-500 bg-emerald-50/30': touched.email && !errors.email && form.email,
                                   'border-gray-900 bg-[#FFF9E6]': !errors.email && !(touched.email && form.email)
                               }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">

                        <div class="absolute right-3.5 top-3.5 text-xs font-bold" x-show="touched.email && !errors.email && form.email">
                            <span class="text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-md">✓ Valid</span>
                        </div>
                    </div>
                    <template x-if="errors.email">
                        <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.email"></p>
                    </template>
                    @error('email')
                        <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <!-- Password & Konfirmasi Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    <div>
                        <label for="password" class="font-bold text-sm text-gray-900 mb-1.5 block">Kata Sandi (Password)</label>
                        <input type="password"
                               id="password"
                               name="password"
                               x-model="form.password"
                               @input="validateField('password')"
                               @blur="touchField('password')"
                               placeholder="Minimal 8 karakter"
                               :class="{
                                   'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.password,
                                   'border-emerald-500 bg-emerald-50/30': touched.password && !errors.password && form.password.length >= 8,
                                   'border-gray-900 bg-[#FFF9E6]': !errors.password && !(touched.password && form.password.length >= 8)
                               }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.password">
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.password"></p>
                        </template>
                        @error('password')
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="font-bold text-sm text-gray-900 mb-1.5 block">Konfirmasi Kata Sandi</label>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               x-model="form.password_confirmation"
                               @input="validateField('password_confirmation')"
                               @blur="touchField('password_confirmation')"
                               placeholder="Ulangi kata sandi Anda"
                               :class="{
                                   'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.password_confirmation,
                                   'border-emerald-500 bg-emerald-50/30': touched.password_confirmation && !errors.password_confirmation && form.password_confirmation && (form.password === form.password_confirmation),
                                   'border-gray-900 bg-[#FFF9E6]': !errors.password_confirmation && !(touched.password_confirmation && (form.password === form.password_confirmation))
                               }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">

                        <div x-show="touched.password_confirmation && form.password_confirmation && form.password === form.password_confirmation" class="text-xs font-bold text-emerald-600 mt-1 flex items-center gap-1">
                            <span>✓ Kata sandi cocok</span>
                        </div>

                        <template x-if="errors.password_confirmation">
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.password_confirmation"></p>
                        </template>
                        @error('password_confirmation')
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Identitas Kelompok / Distributor -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    <div>
                        <label for="company_name" class="font-bold text-sm text-gray-900 mb-1.5 block">Nama Kelompok Tani / Distributor</label>
                        <input type="text"
                               id="company_name"
                               name="company_name"
                               x-model="form.company_name"
                               @input="validateField('company_name')"
                               @blur="touchField('company_name')"
                               placeholder="Dapur Sambal Malang"
                               :class="{
                                   'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.company_name,
                                   'border-gray-900 bg-[#FFF9E6]': !errors.company_name
                               }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.company_name">
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.company_name"></p>
                        </template>
                        @error('company_name')
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="font-bold text-sm text-gray-900 mb-1.5 block">Nomor WhatsApp Operasional</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               x-model="form.phone"
                               @input="validateField('phone')"
                               @blur="touchField('phone')"
                               placeholder="081234567890"
                               :class="{
                                   'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.phone,
                                   'border-gray-900 bg-[#FFF9E6]': !errors.phone
                               }"
                               class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                        <template x-if="errors.phone">
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.phone"></p>
                        </template>
                        @error('phone')
                            <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
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
                                    @change="validateField('commodity_id')"
                                    @blur="touchField('commodity_id')"
                                    :class="{
                                        'border-red-500 ring-2 ring-red-200': errors.commodity_id,
                                        'border-gray-900': !errors.commodity_id
                                    }"
                                    class="w-full px-4 py-3 rounded-xl bg-white border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 shadow-sm cursor-pointer">
                                <option value="" disabled>-- Pilih Kategori Komoditas --</option>
                                @foreach($commodities as $commodity)
                                    <option value="{{ $commodity->id }}">
                                        {{ $commodity->name }}
                                    </option>
                                @endforeach
                            </select>
                            <template x-if="errors.commodity_id">
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.commodity_id"></p>
                            </template>
                            @error('commodity_id')
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="max_capacity" class="font-bold text-sm text-gray-900 mb-1.5 block">Kapasitas Pasok Maksimal</label>
                            <input type="text"
                                   id="max_capacity"
                                   name="max_capacity"
                                   x-model="form.max_capacity"
                                   @input="validateField('max_capacity')"
                                   @blur="touchField('max_capacity')"
                                   placeholder="1.500 kg / Minggu"
                                   :class="{
                                       'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.max_capacity,
                                       'border-gray-900 bg-white': !errors.max_capacity
                                   }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.max_capacity">
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.max_capacity"></p>
                            </template>
                            @error('max_capacity')
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="delivery_area" class="font-bold text-sm text-gray-900 mb-1.5 block">Jangkauan Area Pengiriman Armada</label>
                            <input type="text"
                                   id="delivery_area"
                                   name="delivery_area"
                                   x-model="form.delivery_area"
                                   @input="validateField('delivery_area')"
                                   @blur="touchField('delivery_area')"
                                   placeholder="Kota Malang, Kab. Malang, Batu"
                                   :class="{
                                       'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.delivery_area,
                                       'border-gray-900 bg-white': !errors.delivery_area
                                   }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.delivery_area">
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.delivery_area"></p>
                            </template>
                            @error('delivery_area')
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nib_number" class="font-bold text-sm text-gray-900 mb-1.5 block">Nomor NIB / Legalitas Usaha</label>
                            <input type="text"
                                   id="nib_number"
                                   name="nib_number"
                                   x-model="form.nib_number"
                                   @input="validateField('nib_number')"
                                   @blur="touchField('nib_number')"
                                   placeholder="NIB-9120003810"
                                   :class="{
                                       'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.nib_number,
                                       'border-gray-900 bg-white': !errors.nib_number
                                   }"
                                   class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all">
                            <template x-if="errors.nib_number">
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.nib_number"></p>
                            </template>
                            @error('nib_number')
                                <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="warehouse_address" class="font-bold text-sm text-gray-900 mb-1.5 block">Alamat Gudang / Lokasi Dapur Utama</label>
                    <textarea id="warehouse_address"
                              name="warehouse_address"
                              x-model="form.warehouse_address"
                              @input="validateField('warehouse_address')"
                              @blur="touchField('warehouse_address')"
                              rows="3"
                              placeholder="Jl. Soekarno Hatta No. 12, Kota Malang"
                              :class="{
                                  'border-red-500 bg-red-50/60 ring-2 ring-red-200': errors.warehouse_address,
                                  'border-gray-900 bg-[#FFF9E6]': !errors.warehouse_address
                              }"
                              class="w-full px-4 py-3 rounded-xl border-2 focus:border-[#DC4E00] focus:outline-none font-medium text-gray-900 placeholder:text-gray-400 shadow-sm transition-all"></textarea>
                    <template x-if="errors.warehouse_address">
                        <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1" x-text="'⚠️ ' + errors.warehouse_address"></p>
                    </template>
                    @error('warehouse_address')
                        <p class="text-xs font-bold text-red-600 mt-1 flex items-center gap-1">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full py-4 px-6 rounded-full bg-[#F5B800] hover:bg-[#e0a800] text-black font-extrabold text-base sm:text-lg shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all cursor-pointer border-2 border-gray-900 flex items-center justify-center gap-2 mt-8">
                    <span>Selesaikan Pendaftaran Pemasok Lokal →</span>
                </button>

                <div class="pt-4 text-center text-xs sm:text-sm text-gray-600 font-semibold border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div>
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-[#DC4E00] hover:underline font-bold ml-1">
                            Masuk di sini →
                        </a>
                    </div>
                    <div>
                        Bukan Pemasok?
                        <a href="{{ route('register.umkm') }}" class="text-gray-800 hover:underline font-bold ml-1">
                            Daftar UMKM Kuliner →
                        </a>
                    </div>
                </div>

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
