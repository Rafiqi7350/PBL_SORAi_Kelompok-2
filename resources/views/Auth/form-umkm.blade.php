<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun UMKM Kuliner - SORAI</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f3f4f6;
            color: #111827;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>

    <script>
        function registerUmkmForm() {
            return {
                form: {
                    email: @json(old('email', '')),
                    password: '',
                    password_confirmation: '',
                    business_name: @json(old('business_name', '')),
                    owner_name: @json(old('owner_name', '')),
                    phone: @json(old('phone', '')),
                    address: @json(old('address', ''))
                },
                showPassword: false,
                showConfirmPassword: false,
                errors: {},
                validateField: function(field) {
                    delete this.errors[field];
                    if (field === 'email') {
                        var val = (this.form.email || '').trim();
                        var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!val) this.errors.email = 'Email wajib diisi.';
                        else if (!emailRegex.test(val)) this.errors.email = 'Format email tidak valid (contoh: nama@domain.com).';
                    }
                    if (field === 'password') {
                        if (!this.form.password) this.errors.password = 'Kata sandi wajib diisi.';
                        else if (this.form.password.length < 8) this.errors.password = 'Kata sandi minimal 8 karakter.';
                        if (this.form.password_confirmation) this.validateField('password_confirmation');
                    }
                    if (field === 'password_confirmation') {
                        if (!this.form.password_confirmation) this.errors.password_confirmation = 'Konfirmasi kata sandi wajib diisi.';
                        else if (this.form.password !== this.form.password_confirmation) this.errors.password_confirmation = 'Konfirmasi kata sandi tidak cocok.';
                    }
                    if (field === 'business_name') {
                        var val = (this.form.business_name || '').trim();
                        if (!val) this.errors.business_name = 'Nama usaha wajib diisi.';
                        else if (val.length < 3) this.errors.business_name = 'Nama usaha minimal 3 karakter.';
                    }
                    if (field === 'owner_name') {
                        var val = (this.form.owner_name || '').trim();
                        var nameRegex = /^[a-zA-Z\s'.]+$/;
                        if (!val) {
                            this.errors.owner_name = 'Nama pemilik wajib diisi.';
                        } else if (val.length < 2) {
                            this.errors.owner_name = 'Nama pemilik minimal 2 karakter.';
                        } else if (!nameRegex.test(val)) {
                            this.errors.owner_name = 'Nama pemilik hanya boleh berisi huruf dan spasi.';
                        }
                    }
                    if (field === 'phone') {
                        this.form.phone = (this.form.phone || '').replace(/[^0-9]/g, '');
                        if (!this.form.phone) {
                            this.errors.phone = 'Nomor WhatsApp wajib diisi.';
                        } else if (this.form.phone.length < 10) {
                            this.errors.phone = 'Nomor WhatsApp minimal 10 digit angka.';
                        } else if (this.form.phone.length > 15) {
                            this.errors.phone = 'Nomor WhatsApp maksimal 15 digit angka.';
                        }
                    }
                    if (field === 'address') {
                        var val = (this.form.address || '').trim();
                        if (!val) this.errors.address = 'Alamat operasional dapur wajib diisi.';
                        else if (val.length < 5) this.errors.address = 'Alamat operasional minimal 5 karakter.';
                    }
                },
                validateAll: function() {
                    var fields = ['email', 'password', 'password_confirmation', 'business_name', 'owner_name', 'phone', 'address'];
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

    <!-- Main Content: Registration Card with Smooth Entrance Animation -->
    <main class="w-full max-w-2xl mx-auto px-4 py-8 my-auto flex-grow flex flex-col justify-center animate-page-enter" x-data="registerUmkmForm()">

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
                    DAFTAR SEBAGAI <span class="text-gray-900">UMKM KULINER</span>
                </h1>
                <p class="text-xs text-gray-500 max-w-md mx-auto">
                    Akses pasokan bahan baku segar langsung dari kelompok tani dan distributor lokal untuk kelancaran dapur usaha Anda.
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

            <form novalidate action="{{ route('register.umkm.store') }}" method="POST" @submit="submitForm" class="space-y-4 text-xs font-semibold">
                @csrf

                <!-- Informasi Akun: Email -->
                <div>
                    <label for="email" class="block text-gray-700 mb-1.5">Email Akun Resmi</label>
                    <input type="email"
                           id="email"
                           name="email"
                           x-model="form.email"
                           @input="validateField('email')"
                           required
                           placeholder="contoh: dapur.sedap@gmail.com"
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

                <!-- Detail Profil UMKM -->
                <div class="pt-2 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="business_name" class="block text-gray-700 mb-1.5">Nama Usaha / Dapur Kuliner</label>
                            <input type="text"
                                   id="business_name"
                                   name="business_name"
                                   x-model="form.business_name"
                                   @input="validateField('business_name')"
                                   required
                                   placeholder="contoh: Dapur Sambal Makmur"
                                   class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                            <template x-if="errors.business_name">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.business_name"></p>
                            </template>
                        </div>

                        <div>
                            <label for="owner_name" class="block text-gray-700 mb-1.5">Nama Pemilik / Pengelola</label>
                            <input type="text"
                                   id="owner_name"
                                   name="owner_name"
                                   x-model="form.owner_name"
                                   @input="validateField('owner_name')"
                                   required
                                   placeholder="contoh: Budi Santoso"
                                   class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                            <template x-if="errors.owner_name">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.owner_name"></p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="phone" class="block text-gray-700">Nomor WhatsApp Operasional</label>
                        <span class="text-[10px] text-gray-400 font-normal">Hanya angka (10–15 digit)</span>
                    </div>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="15"
                           x-model="form.phone"
                           @input="form.phone = form.phone.replace(/[^0-9]/g, ''); validateField('phone')"
                           required
                           placeholder="contoh: 081234567890"
                           class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                    <template x-if="errors.phone">
                        <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.phone"></p>
                    </template>
                </div>

                <!-- Alamat Dapur -->
                <div>
                    <label for="address" class="block text-gray-700 mb-1.5">Alamat Dapur / Lokasi Penerimaan Pasokan</label>
                    <textarea id="address"
                              name="address"
                              rows="2"
                              x-model="form.address"
                              @input="validateField('address')"
                              required
                              placeholder="contoh: Jl. Soekarno Hatta No. 45, Lowokwaru, Kota Malang"
                              class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition"></textarea>
                    <template x-if="errors.address">
                        <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.address"></p>
                    </template>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition active:scale-[0.99] cursor-pointer mt-4 flex items-center justify-center gap-1">
                    <span>Selesaikan Pendaftaran UMKM →</span>
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
