<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Verifikasi Akun — SORAI</title>

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
        function pendingStatusPage(initialEmail, initialStatus) {
            return {
                email: initialEmail || '',
                inputEmail: initialEmail || '',
                status: initialStatus || 'pending',
                checking: false,
                lastChecked: 'Baru saja',
                loginUrl: '{{ route("login") }}' + (initialEmail ? '?email=' + encodeURIComponent(initialEmail) : ''),
                timer: null,

                init: function() {
                    if (this.email) {
                        this.startPolling();
                    }
                },

                startPolling: function() {
                    var self = this;
                    // Auto check every 4 seconds
                    this.timer = setInterval(function() {
                        self.checkStatus(true);
                    }, 4000);
                },

                checkStatus: function(silent) {
                    var self = this;
                    if (!this.email) return;
                    if (!silent) this.checking = true;

                    fetch('{{ route("api.check-status") }}?email=' + encodeURIComponent(this.email))
                        .then(function(res) {
                            if (!res.ok) throw new Error('Network error');
                            return res.json();
                        })
                        .then(function(data) {
                            self.checking = false;
                            self.lastChecked = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                            if (data.exists) {
                                self.status = data.status;
                                if (data.is_active) {
                                    self.loginUrl = data.login_url;
                                    if (self.timer) {
                                        clearInterval(self.timer);
                                    }
                                }
                            }
                        })
                        .catch(function(err) {
                            self.checking = false;
                        });
                },

                submitCheckEmail: function() {
                    if (this.inputEmail && this.inputEmail.trim()) {
                        this.email = this.inputEmail.trim();
                        this.loginUrl = '{{ route("login") }}?email=' + encodeURIComponent(this.email);
                        this.checkStatus(false);
                        if (!this.timer) {
                            this.startPolling();
                        }
                    }
                }
            };
        }
    </script>
</head>
<body class="min-h-screen bg-[#f3f4f6] text-gray-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white">

    <!-- Header Navigasi Floating Pill -->
    <header class="w-full max-w-5xl mx-auto my-5 px-4 sm:px-6">
        <div class="bg-white rounded-full shadow-[0_4px_20px_rgba(0,0,0,0.06)] px-6 py-2.5 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#ea580c] flex items-center justify-center font-bold text-white text-sm shadow-sm group-hover:scale-105 transition-transform">
                    S
                </div>
                <span class="font-bold text-lg text-black tracking-tight leading-none">SORAI</span>
            </a>

            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="text-gray-500 hidden sm:inline">Sudah memiliki akses?</span>
                <a href="{{ route('login') }}" class="bg-[#18181b] hover:bg-black text-white px-5 py-2 rounded-full font-bold transition shadow-sm">
                    Masuk (Log in)
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content: Loading & Pending Card -->
    <main class="w-full max-w-xl mx-auto px-4 my-auto flex-grow flex items-center justify-center py-6"
          x-data="pendingStatusPage('{{ $email }}', '{{ $status }}')">

        <div class="w-full bg-white rounded-[2.5rem] p-8 sm:p-12 shadow-[0_10px_35px_rgba(0,0,0,0.06)] text-center space-y-6 transition-all duration-300">

            <!-- STATE 1: PENDING / WAITING APPROVAL -->
            <div x-show="status !== 'active'" class="space-y-6">

                <!-- Animated Loading Radar / Clock Icon -->
                <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
                    <span class="absolute inset-0 rounded-full bg-orange-400/20 animate-ping"></span>
                    <span class="absolute inset-2 rounded-full bg-orange-100"></span>
                    <div class="relative w-16 h-16 rounded-full bg-[#ea580c] text-white flex items-center justify-center shadow-md">
                        <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Status Heading & Animasi Tulisan Sedang Diproses -->
                <div class="space-y-3">
                    <!-- Animasi Banner Tulisan: Mohon Ditunggu Data Anda Sedang Diproses -->
                    <div>
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-50 border border-orange-200 text-[#ea580c] font-semibold text-xs shadow-xs animate-pulse">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#ea580c]"></span>
                            </span>
                            <span class="font-bold">Mohon ditunggu, data Anda sedang diproses</span>
                            <span class="inline-flex gap-0.5 font-black text-sm">
                                <span class="inline-block animate-bounce" style="animation-delay: 0ms">.</span>
                                <span class="inline-block animate-bounce" style="animation-delay: 150ms">.</span>
                                <span class="inline-block animate-bounce" style="animation-delay: 300ms">.</span>
                            </span>
                        </div>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-mono text-[11px] font-bold uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-[#ea580c] animate-pulse"></span>
                        <span>STATUS: MENUNGGU PERSETUJUAN ADMIN</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                        Pendaftaran Sedang Ditinjau
                    </h1>

                    <p class="text-xs text-gray-600 max-w-md mx-auto leading-relaxed">
                        Data pendaftaran Anda telah berhasil kami terima. Untuk menjaga keamanan pasokan dan keaslian mitra, akun Anda saat ini sedang menunggu persetujuan (ACC) dari Administrator SORAI.
                    </p>
                </div>

                @if(session('success'))
                    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-center gap-2">
                        <span>✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Account Information Summary Box -->
                <div class="bg-[#f8f9fa] border border-gray-100 rounded-2xl p-5 text-left text-xs space-y-2.5">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                        <span class="text-gray-500 font-medium">Email Akun:</span>
                        <span class="font-bold text-gray-900 font-mono" x-text="email || 'Belum diisi'">{{ $email ?? 'Belum diisi' }}</span>
                    </div>

                    @if($role)
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                            <span class="text-gray-500 font-medium">Tipe Pendaftaran:</span>
                            <span class="font-bold text-gray-900">
                                {{ $role === 'supplier' ? 'Pemasok Bahan Baku Lokal' : 'UMKM Kuliner' }}
                            </span>
                        </div>
                    @endif

                    @if($businessName)
                        <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                            <span class="text-gray-500 font-medium">Nama Usaha / Usaha:</span>
                            <span class="font-bold text-gray-900">{{ $businessName }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-gray-500 font-medium">Pemeriksaan Terakhir:</span>
                        <span class="font-mono text-gray-600 text-[11px]" x-text="lastChecked">Baru saja</span>
                    </div>
                </div>

                <!-- Tombol Kembali ke Halaman Utama (Landing Page) -->
                <div class="pt-2 space-y-3">
                    <a href="{{ url('/') }}"
                       class="w-full bg-[#18181b] hover:bg-black active:scale-[0.99] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-2">
                        <span>Kembali ke Halaman Utama</span>
                    </a>

                    <p class="text-[11px] text-gray-500 font-medium">
                        Sistem tetap memproses verifikasi di latar belakang. Begitu akun disetujui oleh admin, Anda dapat langsung masuk melalui portal login.
                    </p>
                </div>

            </div>

            <!-- STATE 2: APPROVED / ACTIVE (Admin has ACC-ed the account) -->
            <div x-show="status === 'active'" x-cloak class="space-y-6">

                <!-- Success Badge -->
                <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-[11px] font-bold uppercase tracking-wider">
                        <span>✓</span>
                        <span>STATUS: DISETUJUI & AKTIF</span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                        Akun Berhasil Disetujui!
                    </h2>

                    <p class="text-xs text-gray-600 max-w-md mx-auto leading-relaxed">
                        Selamat! Pendaftaran Anda telah disetujui oleh Administrator SORAI. Akun Anda telah aktif dan siap digunakan untuk bertransaksi pasokan bahan baku.
                    </p>
                </div>

                <div class="pt-2">
                    <a :href="loginUrl"
                       class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.99] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-2">
                        <span>Masuk ke Portal SORAI Sekarang</span>
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer Simple -->
    <footer class="w-full py-5 text-center text-xs text-gray-500 font-medium">
        &copy; {{ date('Y') }} SORAI — Portal Kemitraan UMKM & Pemasok Bahan Baku Lokal.
    </footer>

</body>
</html>
