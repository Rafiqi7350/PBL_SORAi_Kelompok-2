<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Verifikasi Akun — SORAI</title>

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
                }
            };
        }
    </script>
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-900 font-sans flex flex-col justify-between antialiased selection:bg-[#ea580c] selection:text-white">

    <!-- Main Content: Simple & Modern Pending Card -->
    <main class="w-full max-w-lg mx-auto px-4 py-8 sm:py-12 my-auto flex-grow flex flex-col justify-center animate-page-enter"
          x-data="pendingStatusPage('{{ $email }}', '{{ $status }}')">

        <div class="w-full bg-white rounded-3xl p-7 sm:p-9 shadow-sm border border-slate-200/80 text-center space-y-6">

            <!-- Brand Logo -->
            <div class="flex justify-center">
                <a href="{{ url('/') }}" class="inline-block transition-transform hover:scale-105 duration-200">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo SORAI" class="w-12 h-12 object-contain mx-auto">
                </a>
            </div>

            <!-- STATE 1: PENDING (Sedang Ditinjau) -->
            <div x-show="status !== 'active'" class="space-y-6">

                <!-- Minimalist Modern Loader Icon -->
                <div class="w-16 h-16 mx-auto rounded-2xl bg-orange-50 border border-orange-100 flex items-center justify-center text-[#ea580c] shadow-2xs relative">
                    <svg class="w-8 h-8 animate-spin text-[#ea580c]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-[#ea580c]"></span>
                    </span>
                </div>

                <!-- Title & Status Pill -->
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 border border-orange-200/80 text-[#ea580c] font-semibold text-[11px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ea580c] animate-pulse"></span>
                        <span>Menunggu Verifikasi Admin</span>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Pendaftaran Sedang Ditinjau
                    </h1>

                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Data pendaftaran usaha Anda telah kami terima. Akun sedang diverifikasi untuk memastikan keamanan kemitraan bahan baku.
                    </p>
                </div>

                @if(session('success'))
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-center gap-2">
                        <span class="font-bold">✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Clean Summary Box -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200/70 p-4 text-left text-xs space-y-2.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                        <span class="text-slate-500 font-medium">Email Akun:</span>
                        <span class="font-bold text-slate-900 font-mono text-[11px]" x-text="email || 'Belum diisi'">{{ $email ?? 'Belum diisi' }}</span>
                    </div>

                    @if($businessName)
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <span class="text-slate-500 font-medium">Nama Usaha:</span>
                            <span class="font-bold text-slate-900">{{ $businessName }}</span>
                        </div>
                    @endif

                    @if($role)
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <span class="text-slate-500 font-medium">Tipe Akun:</span>
                            <span class="font-semibold text-slate-800">
                                {{ $role === 'supplier' ? 'Pemasok Bahan Baku' : 'UMKM Kuliner' }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-0.5 text-[11px]">
                        <span class="text-slate-400">Pemeriksaan Otomatis:</span>
                        <span class="font-mono text-slate-500 font-medium" x-text="lastChecked">Baru saja</span>
                    </div>
                </div>

                <!-- SLA & Bantuan Cepat Minimalis -->
                <div class="flex items-center justify-between gap-2 p-3 bg-slate-50 border border-slate-200/70 rounded-xl text-[11px] text-slate-600">
                    <div class="flex items-center gap-1.5 text-slate-500">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Estimasi: <strong>15–30 Menit</strong></span>
                    </div>
                    <a href="https://wa.me/6281234567890?text=Halo%20Admin%20SORAI,%20saya%20sudah%20mendaftar%20akun%20dan%20membutuhkan%20verifikasi%20segera." 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="font-semibold text-[#ea580c] hover:underline flex items-center gap-1">
                        <span>Bantuan WhatsApp</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

                <!-- CTA Kembali ke Beranda -->
                <div class="pt-1">
                    <a href="{{ url('/') }}"
                       class="w-full bg-slate-900 hover:bg-slate-800 active:scale-[0.99] text-white font-bold text-xs py-3 rounded-full shadow-2xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Kembali ke Halaman Utama</span>
                    </a>
                    <p class="text-[11px] text-slate-400 mt-2">
                        Status diperbarui otomatis tanpa perlu me-refresh browser.
                    </p>
                </div>

            </div>

            <!-- STATE 2: ACTIVE (Sudah Disetujui) -->
            <div x-show="status === 'active'" x-cloak class="space-y-6">

                <!-- Success Check Icon -->
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shadow-2xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold text-[11px]">
                        <span>✓</span>
                        <span>Akun Telah Aktif</span>
                    </div>

                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Akun Berhasil Disetujui!
                    </h2>

                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Pendaftaran Anda telah disetujui oleh tim SORAI. Akun Anda siap digunakan untuk bertransaksi pasokan bahan baku.
                    </p>
                </div>

                <div class="pt-2">
                    <a :href="loginUrl"
                       class="w-full bg-[#ea580c] hover:bg-[#c2410c] active:scale-[0.99] text-white font-bold text-xs py-3.5 rounded-full shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Masuk ke Portal Sekarang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer Simple -->
    <footer class="w-full py-5 text-center text-xs text-slate-400 font-medium">
        &copy; {{ date('Y') }} SORAI — Portal Kemitraan UMKM &amp; Pemasok Bahan Baku Lokal.
    </footer>

</body>
</html>
