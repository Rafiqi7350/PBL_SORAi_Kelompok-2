<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SORAI — Control Panel Administrator Systems</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root:root, html, body {
            --font-sans: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-display: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-mono: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-family: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        *, *::before, *::after,
        input, button, select, textarea, option, table, th, td, code, pre,
        .font-sans, .font-serif, .font-mono, .font-mono-code, .display-poster {
            font-family: 'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif !important;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-900 antialiased selection:bg-orange-100 selection:text-orange-900">

    <div class="min-h-screen w-full flex flex-col lg:flex-row" x-data="{ showPass: false }">

        {{-- Left Brand & Control Context Panel --}}
        <div class="lg:w-[46%] bg-[#161922] text-white p-8 sm:p-12 lg:p-16 flex flex-col justify-between relative border-b lg:border-b-0 lg:border-r border-slate-800">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-[#ea580c] text-white flex items-center justify-center font-extrabold text-base shadow-sm group-hover:bg-[#c2410c] transition-colors">
                        S
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight text-white leading-none block">SORAI</span>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mt-1 block">Admin Control Hub</span>
                    </div>
                </a>

                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.12] text-slate-300 hover:text-white text-xs font-semibold border border-white/[0.08] transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Portal Utama</span>
                </a>
            </div>

            <div class="my-10 lg:my-0 max-w-md space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/[0.06] border border-white/[0.08] text-orange-300 text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                    <span>Centralized Platform Governance</span>
                </div>

                <div class="space-y-3">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight leading-tight">
                        Kendali Penuh Ekosistem Rantai Pasok SORAI
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Platform administrasi terpusat untuk memverifikasi mitra UMKM &amp; pemasok, menstandarisasi master komoditas pangan, serta memantau audit log sistem secara real-time.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3 pt-2 border-t border-white/[0.08]">
                    <div class="bg-white/[0.04] border border-white/[0.06] rounded-xl p-3">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Moderasi</p>
                        <p class="text-xs font-bold text-white mt-1">Verifikasi Mitra</p>
                    </div>
                    <div class="bg-white/[0.04] border border-white/[0.06] rounded-xl p-3">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Master Data</p>
                        <p class="text-xs font-bold text-white mt-1">Acuan HET &amp; Unit</p>
                    </div>
                    <div class="bg-white/[0.04] border border-white/[0.06] rounded-xl p-3">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Audit Trail</p>
                        <p class="text-xs font-bold text-white mt-1">Log Transaksi</p>
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 font-medium">
                &copy; {{ now()->year }} SORAI B2B Supply Chain Platform. Akses internal terotentikasi.
            </p>
        </div>

        {{-- Right Authentication Form Panel --}}
        <div class="flex-1 p-6 sm:p-10 lg:p-16 flex flex-col justify-center items-center bg-slate-50">
            <div class="w-full max-w-md space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8 space-y-6">

                    <div class="space-y-1.5">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-orange-50 border border-orange-200/70 text-[#c2410c] text-[10px] font-bold uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c]"></span>
                            <span>Admin Control Access</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight pt-1">
                            Masuk ke Control Panel
                        </h2>
                        <p class="text-xs text-slate-500">
                            Gunakan kredensial administrator untuk mengelola platform.
                        </p>
                    </div>

                    {{-- Notifikasi Sesi Aktif User Non-Admin --}}
                    @if(isset($activeUser) && $activeUser)
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/90 text-amber-950 text-xs space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-[11px] text-amber-900 uppercase tracking-wide">Perhatian Sesi Aktif</span>
                                <span class="text-[10px] font-bold bg-amber-200/80 text-amber-900 px-2 py-0.5 rounded-full">{{ strtoupper($activeUser->role) }}</span>
                            </div>
                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                Anda saat ini terhubung sebagai <strong class="font-semibold">{{ $activeUser->email }}</strong>. Masuk dengan form ini akan mengganti sesi ke akun Admin.
                            </p>
                        </div>
                    @endif

                    {{-- Flash Success Message --}}
                    @if (session('success'))
                        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/90 text-emerald-900 text-xs font-medium flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    {{-- Flash Warning Message --}}
                    @if (session('warning'))
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/90 text-amber-900 text-xs font-medium flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <span>{{ session('warning') }}</span>
                        </div>
                    @endif

                    {{-- Validation Errors --}}
                    @if ($errors->any())
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200/90 text-rose-900 text-xs space-y-1">
                            <div class="flex items-center gap-2 font-bold text-rose-700">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Gagal Masuk</span>
                            </div>
                            <ul class="list-disc list-inside text-[11px] font-medium text-rose-700 space-y-0.5 pl-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.login.store') }}" method="POST" class="space-y-4 text-xs">
                        @csrf

                        <div>
                            <label for="email" class="block font-semibold text-slate-700 mb-1.5">Email / Username</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
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
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('email') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 text-slate-900 font-medium transition"
                                />
                            </div>
                            @error('email')
                                <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block font-semibold text-slate-700 mb-1.5">Password</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
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
                                    class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('password') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 text-slate-900 font-medium transition"
                                />
                                <button
                                    type="button"
                                    @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 transition focus:outline-none cursor-pointer"
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

                        <div class="flex items-center justify-between pt-0.5">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-slate-600">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    {{ old('remember') ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-[#ea580c] focus:ring-[#ea580c] border-slate-300 cursor-pointer"
                                />
                                <span class="text-xs font-medium">Ingat sesi admin di perangkat ini</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white py-3 rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer">
                            Masuk ke Control Hub
                        </button>
                    </form>

                </div>

                <div class="text-center space-y-1.5">
                    <p class="text-[11px] font-medium text-slate-400">
                        Khusus staf administrator platform SORAI
                    </p>
                    <div>
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-600 hover:text-[#ea580c] transition">
                            Masuk sebagai Mitra UMKM / Pemasok
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
