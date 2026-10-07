<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SORAI — Control Panel Administrator Systems</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #fef3c7;
            color: #1e2330;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }
        .display-poster {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.03em;
            line-height: 1.05;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-plaster-white selection:bg-voltage-lime selection:text-midnight-ink">

    <div class="min-h-screen w-full flex flex-col md:flex-row bg-plaster-white font-sans" x-data="{ showPass: false }">

        <!-- SISI KIRI: BANNER GRADIENT ORANYE LENGKAP DENGAN ORNAMEN LINGKARAN -->
        <div class="md:w-1/2 bg-gradient-to-br from-sun-orange via-cobalt-signal to-bordeaux-maroon p-8 lg:p-16 flex flex-col justify-center items-center text-center relative overflow-hidden border-b-4 md:border-b-0 md:border-r-4 border-midnight-ink min-h-[380px] md:min-h-screen">

            <!-- ORNAMEN DEKORATIF LINGKARAN SEPERTI DI GAMBAR -->
            <div class="absolute -top-16 -left-16 w-64 h-64 rounded-full bg-plaster-white/10 pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full bg-voltage-lime/20 pointer-events-none"></div>
            <div class="absolute top-1/2 left-10 w-24 h-24 rounded-full bg-plaster-white/10 pointer-events-none"></div>

            <div class="relative z-10 max-w-md space-y-6 flex flex-col items-center">
                <!-- LOGO BOX DENGAN AKSEN IKON SEPERTI DI GAMBAR -->
                <div class="w-24 h-24 bg-plaster-white rounded-3xl border-3 border-midnight-ink shadow-2xl flex items-center justify-center p-3 transform hover:rotate-3 transition duration-300">
                    <div class="w-full h-full bg-voltage-lime rounded-2xl border-2 border-midnight-ink flex items-center justify-center font-display font-extrabold text-midnight-ink text-3xl shadow-sm">
                        S
                    </div>
                </div>

                <div class="space-y-2">
                    <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-plaster-white uppercase tracking-tight drop-shadow-md">
                        SELAMAT DATANG
                    </h1>
                    <p class="font-mono font-extrabold text-voltage-lime text-lg tracking-wider">
                        SORAI Admin Panel
                    </p>
                </div>

                <p className="text-plaster-white/90 font-medium text-xs sm:text-sm leading-relaxed max-w-xs text-white/90">
                    Platform Control Panel Admin untuk memverifikasi pengguna, mengelola master data komoditas, dan memantau transaksi platform SORAI.
                </p>

                <!-- Tombol Navigasi Cepat Kembali -->
                <div class="pt-2">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 hover:bg-white/25 text-white font-bold text-xs border border-white/30 backdrop-blur-sm transition">
                        <span>← Kembali ke Portal Utama</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: FORM LOGIN BERSIH PERSIS DENGAN GAMBAR -->
        <div class="md:w-1/2 p-8 lg:p-16 flex flex-col justify-center items-center bg-bone-canvas/30 min-h-full">
            <div class="w-full max-w-sm space-y-8">

                <div class="space-y-1 text-center md:text-left">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cobalt-signal/10 border border-cobalt-signal/30 text-cobalt-signal font-mono text-[11px] font-bold uppercase mb-2">
                        <span class="w-2 h-2 rounded-full bg-cobalt-signal animate-pulse"></span>
                        Admin Control Access
                    </div>
                    <h2 class="font-display font-extrabold text-2xl sm:text-3xl text-midnight-ink uppercase">
                        Masuk Ke Dashboard
                    </h2>
                    <p class="text-xs font-bold text-midnight-ink/60">
                        Masuk sebagai Admin untuk mengelola platform
                    </p>
                </div>

                <!-- Notifikasi Sesi Aktif User Non-Admin (Jika ada yang sedang login) -->
                @if(isset($activeUser) && $activeUser)
                    <div class="p-3.5 rounded-xl bg-amber-50 border-2 border-amber-300 text-amber-950 text-xs font-medium space-y-1.5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[11px] text-amber-900 uppercase">Perhatian Sesi Aktif</span>
                            <span class="text-[10px] font-bold bg-amber-200 px-2 py-0.5 rounded-full">{{ strtoupper($activeUser->role) }}</span>
                        </div>
                        <p class="text-[11px] text-amber-800">
                            Anda saat ini terhubung sebagai <strong class="font-bold">{{ $activeUser->email }}</strong>. Masuk dengan form ini akan mengganti sesi ke akun Admin.
                        </p>
                    </div>
                @endif

                <!-- Flash Success Message -->
                @if (session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-50 border-2 border-emerald-500 text-emerald-900 text-xs font-bold flex items-center gap-2.5 shadow-sm">
                        <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs flex-shrink-0">✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Flash Warning Message -->
                @if (session('warning'))
                    <div class="p-3.5 rounded-xl bg-amber-50 border-2 border-amber-500 text-amber-900 text-xs font-bold flex items-center gap-2.5 shadow-sm">
                        <span class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs flex-shrink-0">!</span>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                <!-- Flash / Validation Errors -->
                @if ($errors->any())
                    <div class="p-3.5 rounded-xl bg-rose-50 border-2 border-rose-500 text-rose-900 text-xs font-bold space-y-1 shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs flex-shrink-0">✕</span>
                            <span>Gagal Masuk:</span>
                        </div>
                        <ul class="list-disc list-inside text-[11px] font-semibold text-rose-800 space-y-0.5 pl-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.login.store') }}" method="POST" class="space-y-5 text-xs font-bold">
                    @csrf

                    <div>
                        <label for="email" class="block text-midnight-ink/80 mb-1.5 font-sans">Email / Username</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3.5 text-midnight-ink/40 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                id="email"
                                name="email"
                                required 
                                value="{{ old('email', 'admin@sorai.id') }}" 
                                placeholder="admin@sorai.id"
                                autocomplete="email"
                                class="w-full pl-10 pr-4 py-3 rounded-xl bg-plaster-white border-2 {{ $errors->has('email') ? 'border-rose-500 ring-2 ring-rose-200' : 'border-midnight-ink' }} focus:outline-none focus:ring-2 focus:ring-sun-orange text-midnight-ink font-bold shadow-sm transition" 
                            />
                        </div>
                        @error('email')
                            <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-midnight-ink/80 mb-1.5 font-sans">Password</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3.5 text-midnight-ink/40 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <input 
                                :type="showPass ? 'text' : 'password'" 
                                id="password"
                                name="password"
                                required 
                                placeholder="••••••••"
                                autocomplete="current-password"
                                class="w-full pl-10 pr-10 py-3 rounded-xl bg-plaster-white border-2 {{ $errors->has('password') ? 'border-rose-500 ring-2 ring-rose-200' : 'border-midnight-ink' }} focus:outline-none focus:ring-2 focus:ring-sun-orange text-midnight-ink font-bold shadow-sm transition" 
                            />
                            <button 
                                type="button" 
                                @click="showPass = !showPass" 
                                class="absolute right-3.5 top-3.5 text-midnight-ink/40 hover:text-midnight-ink transition focus:outline-none cursor-pointer"
                                title="Lihat / Sembunyikan Password">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-midnight-ink/80">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                                class="w-4 h-4 rounded text-cobalt-signal focus:ring-cobalt-signal border-midnight-ink cursor-pointer"
                            />
                            <span class="text-xs font-semibold">Ingat sesi admin di perangkat ini</span>
                        </label>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-cobalt-signal hover:bg-amber-700 active:scale-[0.99] text-plaster-white border-2 border-midnight-ink py-3.5 rounded-xl font-extrabold text-xs shadow-md hover:shadow-lg transition uppercase tracking-wider cursor-pointer">
                        LOGIN →
                    </button>
                </form>

                <div class="pt-4 border-t border-midnight-ink/15 space-y-2 text-center">
                    <p class="font-mono text-[10px] font-bold text-midnight-ink/50 uppercase tracking-wider">
                        Hanya untuk staff Admin SORAI Platform
                    </p>
                    <div>
                        <a href="{{ route('login') }}" class="text-[11px] font-bold text-midnight-ink/70 hover:text-cobalt-signal underline transition">
                            Masuk sebagai Mitra UMKM / Pemasok
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
