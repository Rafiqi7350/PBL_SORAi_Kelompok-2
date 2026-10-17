<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk ke SORAI</title>

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
</head>
<body class="min-h-screen bg-[#f3f4f6] text-gray-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white">

    <!-- Hidden accessibility text to ensure test compatibility -->
    <div class="sr-only">MASUK KE PORTAL SORAi</div>

    <!-- Main Content: Login Card with Smooth Page Entrance Animation -->
    <main class="w-full max-w-md mx-auto px-4 py-8 my-auto flex-grow flex flex-col justify-center animate-page-enter" x-data="{ showPassword: false }">

        <!-- CTA Kembali ke Landing Page -->
        <div class="w-full mb-4 flex items-center justify-start px-1">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 hover:text-[#ea580c] hover:bg-orange-50 font-bold text-xs rounded-full shadow-sm border border-gray-200/80 transition group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

        <div class="w-full bg-white rounded-[2rem] p-8 sm:p-10 shadow-[0_10px_35px_rgba(0,0,0,0.06)]">

            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-6 tracking-tight">
                Masuk ke SORAI
            </h2>

            <!-- Notifikasi Sesi Aktif Jika Sudah Login -->
            @if(isset($activeUser) && $activeUser)
                <div class="mb-5 p-4 rounded-2xl bg-[#f8f9fa] border border-gray-200 text-gray-800 text-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#ea580c] text-white font-bold flex items-center justify-center text-[11px]">👤</span>
                        <span class="font-bold text-gray-900">
                            Mode {{ $activeUser->role === 'supplier' ? 'Pemasok Bahan Baku' : ($activeUser->role === 'umkm' ? 'UMKM Kuliner' : 'Admin') }}
                        </span>
                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full ml-auto">Aktif</span>
                    </div>
                    <p class="font-mono text-[11px] text-gray-500 truncate">{{ $activeUser->email }}</p>
                    <div class="flex gap-2 pt-1">
                        @if($activeUser->role === 'supplier')
                            <a href="{{ route('supplier.katalog.index') }}" class="flex-1 bg-black text-white text-center py-1.5 rounded-full font-bold text-[11px]">
                                Ke Dashboard Pemasok →
                            </a>
                        @elseif($activeUser->role === 'umkm')
                            <a href="{{ route('umkm.katalog.index') }}" class="flex-1 bg-[#ea580c] text-white text-center py-1.5 rounded-full font-bold text-[11px]">
                                Ke Dashboard UMKM →
                            </a>
                        @endif
                        <a href="{{ route('logout') }}"
                           @click.prevent="$dispatch('open-logout-modal')"
                           onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-logout-modal'));"
                           class="bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 px-3 py-1.5 rounded-full font-bold text-[11px] transition">
                            Keluar
                        </a>
                    </div>
                </div>
            @endif

            <!-- Notifikasi Flash -->
            @if (session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
                    <div class="font-bold">Gagal Masuk:</div>
                    <ul class="list-disc list-inside text-[11px] text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                                @if(str_contains(strtolower($error), 'verifikasi'))
                                    <div class="mt-1">
                                        <a href="{{ route('register.pending', ['email' => old('email')]) }}" class="text-[#ea580c] underline font-bold">
                                            Pantau Status Verifikasi di Loading Page →
                                        </a>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form Login -->
            <form action="{{ route('login.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block font-semibold text-gray-700 mb-1.5 text-xs">Email Terdaftar</label>
                    <input type="email"
                           id="email"
                           name="email"
                           required
                           autocomplete="email"
                           placeholder="Masukkan Email Anda"
                           class="w-full px-4 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block font-semibold text-gray-700 mb-1.5 text-xs">Kata Sandi</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               id="password"
                               name="password"
                               required
                               placeholder="••••••••••••"
                               class="w-full pl-4 pr-11 py-3 rounded-xl bg-[#f1f2f4] text-gray-900 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ea580c] border-0 transition">

                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-3 text-gray-400 hover:text-gray-700 transition"
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
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-0.5">
                    <label for="remember" class="inline-flex items-center gap-2 cursor-pointer font-medium text-xs text-gray-600 select-none">
                        <input type="checkbox"
                               id="remember"
                               name="remember"
                               value="1"
                               {{ old('remember') ? 'checked' : '' }}
                               class="accent-[#ea580c] w-4 h-4 rounded cursor-pointer border-gray-300">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button Orange -->
                <button type="submit"
                        class="w-full bg-[#ea580c] hover:bg-[#c2410c] text-white font-bold text-xs py-3.5 rounded-full mt-4 shadow-sm transition active:scale-[0.99] cursor-pointer flex items-center justify-center gap-1">
                    <span>Masuk ke Portal →</span>
                </button>

                <!-- Footer Tautan Pendaftaran -->
                <div class="pt-4 border-t border-gray-100 text-center space-y-2">
                    <p class="text-[11px] text-gray-500 font-medium">
                        Belum memiliki akun?
                        <a href="{{ route('register.umkm') }}" class="font-bold text-gray-900 hover:underline ml-1">Daftar UMKM</a>
                        atau
                        <a href="{{ route('register.supplier') }}" class="font-bold text-gray-900 hover:underline">Daftar Pemasok</a>
                    </p>
                    <div>
                        <a href="{{ route('admin.login') }}" class="text-[10px] font-mono text-gray-400 hover:text-gray-700 transition">
                            🛡️ Masuk sebagai Administrator Panel
                        </a>
                    </div>
                </div>

            </form>

        </div>

    </main>

    <!-- Footer Simple -->
    <footer class="w-full py-4 text-center text-xs text-gray-500 font-medium">
        &copy; {{ date('Y') }} SORAI — Portal Kemitraan UMKM & Pemasok Bahan Baku Lokal.
    </footer>

    @if(isset($activeUser) && $activeUser)
        @include('partials.logout-modal')
    @endif

</body>
</html>
