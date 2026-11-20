<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun UMKM Kuliner — SORAI</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon.png') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f8fafc;
            color: #0f172a;
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
                showConfirmModal: false,
                errors: {},
                validateField: function(field) {
                    delete this.errors[field];
                    if (field === 'email') {
                        var val = (this.form.email || '').trim();
                        var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!val) this.errors.email = 'Email wajib diisi.';
                        else if (!emailRegex.test(val)) this.errors.email = 'Format email tidak valid (masukkan format resmi: nama@domain.com).';
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
                openConfirmModal: function() {
                    if (this.validateAll()) {
                        this.showConfirmModal = true;
                    }
                },
                submitFinalForm: function() {
                    this.$refs.registrationForm.submit();
                }
            };
        }
    </script>
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white"
      x-data="registerUmkmForm()"
      :class="{ 'overflow-hidden': showConfirmModal }"
      @keydown.escape.window="showConfirmModal = false">

    <!-- Main Content: Registration Card with Refined Margin & Padding -->
    <main class="w-full max-w-xl mx-auto px-4 py-8 sm:py-12 my-auto flex-grow flex flex-col justify-center animate-page-enter">
<body class="min-h-screen bg-[#f8fafc] text-slate-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white">

    <!-- Main Content: Registration Card with Refined Margin & Padding -->
    <main class="w-full max-w-xl mx-auto px-4 py-8 sm:py-12 my-auto flex-grow flex flex-col justify-center animate-page-enter" x-data="registerUmkmForm()">

        <!-- CTA Kembali ke Landing Page -->
        <div class="w-full mb-4 flex items-center justify-start">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white text-slate-600 hover:text-[#ea580c] hover:bg-orange-50 font-semibold text-xs rounded-full shadow-2xs border border-slate-200/80 transition group">
                <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

        <div class="w-full bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/80 space-y-6">

            <!-- Brand Logo & Header -->
            <div class="text-center space-y-3">
                <div class="flex justify-center">
                    <a href="{{ url('/') }}" class="inline-block transition-transform hover:scale-105 duration-200">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo SORAI" class="w-12 h-12 object-contain mx-auto">
                    </a>
                </div>

                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-orange-600 uppercase font-mono tracking-wider block">
                        Pendaftaran Akun Baru
                    </span>
                    <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight">
                        Daftar Sebagai UMKM Kuliner
                    </h1>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Akses pasokan bahan baku segar langsung dari kelompok tani dan distributor lokal untuk kelancaran dapur usaha Anda.
                    </p>
                </div>

                <div class="pt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 border border-orange-200/70 text-[11px] text-orange-900 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                        <span>Verifikasi Cepat 15–30 Menit &bull; Rekening Bersama Aman</span>
                    </span>
                </div>
            </div>

            @if(session('success'))
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-2">
                    <span class="font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Mohon periksa kembali formulir Anda:</span>
                    </div>
                    <ul class="list-disc list-inside text-[11px] text-rose-700 space-y-0.5 pl-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form x-ref="registrationForm" novalidate action="{{ route('register.umkm.store') }}" method="POST" class="space-y-4 text-xs font-medium">
                @csrf

                <!-- Section 1: Kredensial Akun -->
                <div class="space-y-3.5">
                    <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] font-bold text-[11px] flex items-center justify-center">1</span>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Kredensial Akun Resmi</span>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-slate-700 font-semibold mb-1.5">Email Akun Resmi</label>
                        <input type="email"
                               id="email"
                               name="email"
                               x-model="form.email"
                               @input="validateField('email')"
                               required
                               placeholder="Masukkan alamat email resmi Anda"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                        <template x-if="errors.email">
                            <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.email"></p>
                        </template>
                    </div>

                    <!-- Password & Konfirmasi Password -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label for="password" class="block text-slate-700 font-semibold mb-1.5">Kata Sandi</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       id="password"
                                       name="password"
                                       x-model="form.password"
                                       @input="validateField('password')"
                                       required
                                       placeholder="Masukkan kata sandi (minimal 8 karakter)"
                                       class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                                <button type="button"
                                        id="toggle-password"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 transition"
                                        tabindex="-1">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <template x-if="errors.password">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.password"></p>
                            </template>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-slate-700 font-semibold mb-1.5">Konfirmasi Kata Sandi</label>
                            <div class="relative">
                                <input :type="showConfirmPassword ? 'text' : 'password'"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       x-model="form.password_confirmation"
                                       @input="validateField('password_confirmation')"
                                       required
                                       placeholder="Masukkan ulang kata sandi Anda"
                                       class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                                <button type="button"
                                        id="toggle-password-confirmation"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 transition"
                                        tabindex="-1">
                                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <template x-if="errors.password_confirmation">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.password_confirmation"></p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Identitas Usaha Kuliner -->
                <div class="space-y-3.5 pt-2">
                    <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] font-bold text-[11px] flex items-center justify-center">2</span>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Profil Usaha Kuliner</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label for="business_name" class="block text-slate-700 font-semibold mb-1.5">Nama Usaha / Dapur Kuliner</label>
                            <input type="text"
                                   id="business_name"
                                   name="business_name"
                                   x-model="form.business_name"
                                   @input="validateField('business_name')"
                                   required
                                   placeholder="Masukkan nama usaha kuliner Anda"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                            <template x-if="errors.business_name">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.business_name"></p>
                            </template>
                        </div>

                        <div>
                            <label for="owner_name" class="block text-slate-700 font-semibold mb-1.5">Nama Pemilik / Pengelola</label>
                            <input type="text"
                                   id="owner_name"
                                   name="owner_name"
                                   x-model="form.owner_name"
                                   @input="validateField('owner_name')"
                                   required
                                   placeholder="Masukkan nama lengkap pemilik usaha"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                            <template x-if="errors.owner_name">
                                <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.owner_name"></p>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Kontak & Alamat Dapur -->
                <div class="space-y-3.5 pt-2">
                    <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
                        <span class="w-5 h-5 rounded-full bg-orange-100 text-[#ea580c] font-bold text-[11px] flex items-center justify-center">3</span>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Kontak &amp; Alamat Dapur</span>
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div>
                        <label for="phone" class="block text-slate-700 font-semibold mb-1.5">Nomor WhatsApp Operasional</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               maxlength="15"
                               x-model="form.phone"
                               @input="form.phone = form.phone.replace(/[^0-9]/g, ''); validateField('phone')"
                               required
                               placeholder="Masukkan nomor WhatsApp aktif"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition">
                        <template x-if="errors.phone">
                            <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.phone"></p>
                        </template>
                    </div>

                    <!-- Alamat Dapur -->
                    <div>
                        <label for="address" class="block text-slate-700 font-semibold mb-1.5">Alamat Dapur / Lokasi Penerimaan Pasokan</label>
                        <textarea id="address"
                                  name="address"
                                  rows="2"
                                  x-model="form.address"
                                  @input="validateField('address')"
                                  required
                                  placeholder="Masukkan alamat lengkap operasional dapur usaha"
                                  class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-[#ea580c] transition"></textarea>
                        <template x-if="errors.address">
                            <p class="text-[11px] text-rose-600 mt-1 font-medium" x-text="errors.address"></p>
                        </template>
                    </div>
                </div>

                <!-- Submit Button (Triggers Confirmation Modal) -->
                <div class="pt-2">
                    <button type="button"
                            @click="openConfirmModal()"
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition active:scale-[0.99] cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Selesaikan Pendaftaran UMKM</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

                <p class="text-center text-xs text-slate-500 font-normal pt-2">
                    Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-[#ea580c] hover:underline">Masuk di sini</a>
                </p>

                <p class="text-[11px] text-slate-400 text-center font-normal">
                    Dengan mendaftar, Anda menyetujui Ketentuan Layanan &amp; Kebijakan Privasi SORAI.
                </p>
            </form>

        </div>

        <!-- ========================================================= -->
        <!-- POP UP VERIFIKASI ULANG: APAKAH DATA ANDA SUDAH BENAR?     -->
        <!-- ========================================================= -->
        <div x-cloak x-show="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in">
            <div @click.away="showConfirmModal = false" class="bg-white max-w-lg w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5 animate-scale-up">

                <!-- Header Modal -->
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-100 text-[#ea580c] flex items-center justify-center shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                        Apakah Data Anda Sudah Benar?
                    </h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Pastikan seluruh data pendaftaran usaha kuliner Anda sudah akurat sebelum dikirim ke tim verifikator SORAI.
                    </p>
                </div>

                <!-- Ringkasan Data Pendaftaran -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-4 space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                        <span class="text-slate-500 font-medium shrink-0">Email Akun:</span>
                        <span class="font-bold text-slate-900 text-right font-mono break-all" x-text="form.email"></span>
                    </div>

                    <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                        <span class="text-slate-500 font-medium shrink-0">Nama Usaha Kuliner:</span>
                        <span class="font-bold text-slate-900 text-right" x-text="form.business_name"></span>
                    </div>

                    <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                        <span class="text-slate-500 font-medium shrink-0">Nama Pemilik / PIC:</span>
                        <span class="font-bold text-slate-900 text-right" x-text="form.owner_name"></span>
                    </div>

                    <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                        <span class="text-slate-500 font-medium shrink-0">WhatsApp Operasional:</span>
                        <span class="font-bold text-slate-900 text-right font-mono" x-text="form.phone"></span>
                    </div>

                    <div class="flex items-start justify-between gap-3 pt-0.5">
                        <span class="text-slate-500 font-medium shrink-0">Alamat Dapur:</span>
                        <span class="font-semibold text-slate-800 text-right text-[11px] leading-relaxed max-w-[260px]" x-text="form.address"></span>
                    </div>
                </div>

                <div class="bg-orange-50/70 border border-orange-200/80 rounded-xl p-3 flex items-start gap-2.5 text-[11px] text-orange-900">
                    <svg class="w-4 h-4 text-[#ea580c] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Setelah dikirim, akun Anda akan diverifikasi oleh admin dalam 15–30 menit. Anda dapat memantau status secara langsung.</span>
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <button type="button"
                            @click="showConfirmModal = false"
                            class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.99] text-slate-700 font-bold text-xs py-3 rounded-full transition cursor-pointer">
                        Periksa Kembali
                    </button>

                    <button type="button"
                            @click="submitFinalForm()"
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.99] text-white font-bold text-xs py-3 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Ya, Kirim Pendaftaran</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer Simple -->
    <footer class="w-full py-5 text-center text-xs text-slate-400 font-medium">
        &copy; {{ date('Y') }} SORAI — Portal Kemitraan UMKM &amp; Pemasok Bahan Baku Lokal.
    </footer>

    <!-- ========================================================= -->
    <!-- POP UP VERIFIKASI ULANG: APAKAH DATA ANDA SUDAH BENAR?     -->
    <!-- ========================================================= -->
    <div x-cloak 
         x-show="showConfirmModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        
        <div @click.away="showConfirmModal = false" 
             x-show="showConfirmModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="bg-white max-w-lg w-full rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5">

            <!-- Header Modal -->
            <div class="text-center space-y-2">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-orange-100 text-[#ea580c] flex items-center justify-center shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                    Apakah Data Anda Sudah Benar?
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Pastikan seluruh data pendaftaran usaha kuliner Anda sudah akurat sebelum dikirim ke tim verifikator SORAI.
                </p>
            </div>

            <!-- Ringkasan Data Pendaftaran -->
            <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-4 space-y-2.5 text-xs">
                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Email Akun:</span>
                    <span class="font-bold text-slate-900 text-right font-mono break-all" x-text="form.email"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Nama Usaha Kuliner:</span>
                    <span class="font-bold text-slate-900 text-right" x-text="form.business_name"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">Nama Pemilik / PIC:</span>
                    <span class="font-bold text-slate-900 text-right" x-text="form.owner_name"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pb-2 border-b border-slate-200/70">
                    <span class="text-slate-500 font-medium shrink-0">WhatsApp Operasional:</span>
                    <span class="font-bold text-slate-900 text-right font-mono" x-text="form.phone"></span>
                </div>

                <div class="flex items-start justify-between gap-3 pt-0.5">
                    <span class="text-slate-500 font-medium shrink-0">Alamat Dapur:</span>
                    <span class="font-semibold text-slate-800 text-right text-[11px] leading-relaxed max-w-[260px]" x-text="form.address"></span>
                </div>
            </div>

            <div class="bg-orange-50/70 border border-orange-200/80 rounded-xl p-3 flex items-start gap-2.5 text-[11px] text-orange-900">
                <svg class="w-4 h-4 text-[#ea580c] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Setelah dikirim, akun Anda akan diverifikasi oleh admin dalam 15–30 menit. Anda dapat memantau status secara langsung.</span>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="grid grid-cols-2 gap-3 pt-1">
                <button type="button"
                        @click="showConfirmModal = false"
                        class="w-full bg-slate-100 hover:bg-slate-200 active:scale-[0.99] text-slate-700 font-bold text-xs py-3 rounded-full transition cursor-pointer">
                    Periksa Kembali
                </button>

                <button type="button"
                        @click="submitFinalForm()"
                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.99] text-white font-bold text-xs py-3 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                    <span>Ya, Kirim Pendaftaran</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>

        </div>
    </div>

</body>
</html>
