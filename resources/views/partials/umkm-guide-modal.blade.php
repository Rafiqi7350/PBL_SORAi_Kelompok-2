<div id="umkm-guide-modal-root"
     x-data="{
         showGuide: false,
         dontShowAgain: false,
         currentStep: 1,
         totalSteps: 2,
         init() {
             var dismissed = false;
             try {
                 dismissed = localStorage.getItem('sorai_umkm_guide_dismissed') === 'true';
             } catch (e) {}

             // Jika pengguna belum mencentang 'Jangan tampilkan lagi', munculkan modal otomatis
             if (!dismissed) {
                 var self = this;
                 setTimeout(function() {
                     self.showGuide = true;
                 }, 400);
             }
         },
         openGuide() {
             this.currentStep = 1;
             this.showGuide = true;
         },
         nextStep() {
             if (this.currentStep < this.totalSteps) {
                 this.currentStep++;
             } else {
                 this.closeGuide();
             }
         },
         prevStep() {
             if (this.currentStep > 1) {
                 this.currentStep--;
             }
         },
         closeGuide() {
             if (this.dontShowAgain) {
                 try {
                     localStorage.setItem('sorai_umkm_guide_dismissed', 'true');
                 } catch (e) {}
             }
             this.showGuide = false;
         }
     }"
     @open-umkm-guide.window="openGuide()"
     @keydown.escape.window="closeGuide()"
     x-cloak>

    <!-- Modal Backdrop -->
    <div x-show="showGuide"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         role="dialog"
         aria-modal="true"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/55 backdrop-blur-sm">

        <!-- Modal Dialog Card -->
        <div x-show="showGuide"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             @click.away="closeGuide()"
             class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 flex flex-col max-h-[85vh] overflow-hidden relative">

            <!-- MODAL HEADER -->
            <div class="px-6 sm:px-8 pt-6 pb-4 border-b border-slate-100 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#ea580c] to-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#ea580c] bg-orange-50 border border-orange-200/60 px-2 py-0.5 rounded-full">
                                Panduan Interaktif Dapur
                            </span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight mt-1">
                            Selamat Datang di Portal Dapur UMKM
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            Panduan praktis alur kemitraan bahan baku dan ringkasan fitur portal dapur Anda.
                        </p>
                    </div>
                </div>

                <button type="button"
                        @click="closeGuide()"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer shrink-0 -mr-2 -mt-1"
                        aria-label="Tutup Panduan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- STEPPER PROGRESS & STEP TITLE BAR -->
            <div class="px-6 sm:px-8 py-2.5 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-700" x-text="'Langkah ' + currentStep + ' dari ' + totalSteps">Langkah 1 dari 2</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-xs font-semibold text-[#ea580c]" x-show="currentStep === 1">Alur Proses Bisnis UMKM</span>
                    <span class="text-xs font-semibold text-[#ea580c]" x-show="currentStep === 2">Penjelasan Fungsi Setiap Tab</span>
                </div>

                <!-- Interactive Step Indicator Dots -->
                <div class="flex items-center gap-1.5">
                    <button type="button"
                            @click="currentStep = 1"
                            :class="currentStep === 1 ? 'w-6 bg-[#ea580c]' : 'w-2 bg-slate-300 hover:bg-slate-400'"
                            class="h-2 rounded-full transition-all duration-200 cursor-pointer"
                            aria-label="Menuju Langkah 1"></button>
                    <button type="button"
                            @click="currentStep = 2"
                            :class="currentStep === 2 ? 'w-6 bg-[#ea580c]' : 'w-2 bg-slate-300 hover:bg-slate-400'"
                            class="h-2 rounded-full transition-all duration-200 cursor-pointer"
                            aria-label="Menuju Langkah 2"></button>
                </div>
            </div>

            <!-- MODAL BODY CONTENT (SCROLLABLE) -->
            <div class="p-6 sm:p-8 overflow-y-auto flex-1 text-slate-800">

                <!-- STEP 1: ALUR PROSES BISNIS UMKM -->
                <div x-show="currentStep === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-2"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="space-y-6">

                    <div class="p-3.5 rounded-2xl bg-orange-50/70 border border-orange-200/70 flex items-start gap-3">
                        <span class="w-7 h-7 rounded-xl bg-orange-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
                            i
                        </span>
                        <p class="text-xs text-orange-950 leading-relaxed">
                            SORAI menghubungkan dapur UMKM langsung dengan <strong>kelompok tani dan distributor lokal</strong> untuk menjamin kestabilan pasokan harian, transparansi harga grosir, serta proteksi pembayaran bersama (escrow).
                        </p>
                    </div>

                    <!-- Connected Vertical Timeline -->
                    <div class="relative pl-7 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gradient-to-b before:from-orange-500 before:via-orange-300 before:to-slate-200">
                        <!-- Step 1 -->
                        <div class="relative group">
                            <div class="absolute -left-7 sm:-left-8 top-0.5 w-6 sm:w-7 h-6 sm:h-7 rounded-full bg-orange-500 text-white font-bold text-xs flex items-center justify-center ring-4 ring-orange-100 shadow-xs">
                                1
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Pilih Komoditas &amp; Ajukan Pasokan Rutin</h4>
                                    <span class="text-[10px] font-semibold text-orange-700 bg-orange-50 border border-orange-200/80 px-2 py-0.5 rounded-md">Katalog Bahan Baku</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Jelajahi komoditas segar berkualitas (daging, cabai, sayuran) dengan harga grosir lokal. Tentukan kuantitas per kirim, jadwal kedatangan harian, dan jam tiba di dapur Anda.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative group">
                            <div class="absolute -left-7 sm:-left-8 top-0.5 w-6 sm:w-7 h-6 sm:h-7 rounded-full bg-orange-500 text-white font-bold text-xs flex items-center justify-center ring-4 ring-orange-100 shadow-xs">
                                2
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Toleransi Revisi Pengajuan (&lt; 30 Menit)</h4>
                                    <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-md">Batas 30 Menit</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Setelah pengajuan dikirim, Anda memiliki waktu <strong>&lt; 30 menit</strong> di menu <em>Riwayat Permintaan</em> untuk mengubah data jika ada koreksi. Setelah 30 menit, data terkunci otomatis untuk persiapan panen dan armada.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative group">
                            <div class="absolute -left-7 sm:-left-8 top-0.5 w-6 sm:w-7 h-6 sm:h-7 rounded-full bg-orange-500 text-white font-bold text-xs flex items-center justify-center ring-4 ring-orange-100 shadow-xs">
                                3
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Pengiriman Armada &amp; Verifikasi Fisik Dapur</h4>
                                    <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-200/80 px-2 py-0.5 rounded-md">Pesanan &amp; Pengiriman</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Saat armada tiba, lakukan penimbangan dan cek fisik mutu bahan. Anda dapat memvalidasi penerimaan penuh atau terima sebagian jika pemasok mengirim pasokan bertahap (Backorder Kloter 2).
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="relative group">
                            <div class="absolute -left-7 sm:-left-8 top-0.5 w-6 sm:w-7 h-6 sm:h-7 rounded-full bg-orange-500 text-white font-bold text-xs flex items-center justify-center ring-4 ring-orange-100 shadow-xs">
                                4
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Pelunasan Aman via Midtrans (Rekening Bersama)</h4>
                                    <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md">Pelunasan Aman</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Bayar faktur melalui Midtrans (Virtual Account / QRIS). Dana Anda aman di rekening penampungan resmi SORAI dan hanya diteruskan ke pemasok setelah Anda memvalidasi kedatangan fisik barang dapur.
                                </p>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="relative group">
                            <div class="absolute -left-7 sm:-left-8 top-0.5 w-6 sm:w-7 h-6 sm:h-7 rounded-full bg-orange-500 text-white font-bold text-xs flex items-center justify-center ring-4 ring-orange-100 shadow-xs">
                                5
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Rating Kualitas, Evaluasi SLA &amp; Analisis Belanja</h4>
                                    <span class="text-[10px] font-semibold text-purple-700 bg-purple-50 border border-purple-200/80 px-2 py-0.5 rounded-md">Evaluasi &amp; Tren</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Beri ulasan rating bintang 1–5 untuk pemasok dan pantau laporan penghematan belanja bulanan dapur Anda serta performa ketepatan armada pengiriman di dashboard analisis.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PENJELASAN FUNGSI SETIAP TAB -->
                <div x-show="currentStep === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-x-2"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="space-y-4">

                    <p class="text-xs text-slate-500">
                        Berikut ikhtisar peran dan fungsi dari setiap menu navigasi di Portal UMKM:
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Tab 1: Katalog Bahan Baku -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Katalog Bahan Baku</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Jelajahi komoditas lokal segar, bandingkan harga acuan pemerintah, dan ajukan kesepakatan pasokan rutin.
                                </p>
                            </div>
                        </div>

                        <!-- Tab 2: Riwayat Permintaan -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Riwayat Permintaan</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Pantau persetujuan kemitraan dan manfaatkan toleransi (&lt; 30 menit) untuk menyunting kuantitas atau jadwal.
                                </p>
                            </div>
                        </div>

                        <!-- Tab 3: Perencanaan Pasokan -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Perencanaan Pasokan</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Kolaborasi estimasi kebutuhan dapur bulanan ke depan agar petani mitra mengalokasikan stok panen khusus.
                                </p>
                            </div>
                        </div>

                        <!-- Tab 4: Pesanan & Pengiriman -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Pesanan &amp; Pengiriman</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Kelola armada harian, verifikasi fisik barang dapur, persetujuan kloter 2 (backorder), dan bayar invoice.
                                </p>
                            </div>
                        </div>

                        <!-- Tab 5: Analisis Pengadaan -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Analisis Pengadaan</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Grafik tren belanja dapur, rasio penghematan biaya dibanding harga pasar, serta ringkasan kontrak aktif.
                                </p>
                            </div>
                        </div>

                        <!-- Tab 6: Pemantauan Mutu & SLA -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-orange-300 hover:shadow-xs transition duration-150 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 border border-orange-100 text-[#ea580c] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 tracking-tight">Pemantauan &amp; SLA</h4>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Evaluasi skor kepatuhan SLA pemasok, akurasi waktu jam tiba armada, dan kualitas kesegaran bahan baku.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- MODAL FOOTER WITH STEP NAVIGATION -->
            <div class="px-6 sm:px-8 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Checkbox Opsi 'Jangan Tampilkan Lagi' -->
                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none text-xs text-slate-600 hover:text-slate-900 font-medium">
                    <input type="checkbox"
                           x-model="dontShowAgain"
                           class="w-4 h-4 rounded border-slate-300 text-[#ea580c] focus:ring-[#ea580c] cursor-pointer">
                    <span>Jangan tampilkan panduan ini lagi</span>
                </label>

                <!-- Action / Stepper Buttons -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <!-- Tombol Lewati / Tutup (Jika di Langkah 1) -->
                    <button type="button"
                            x-show="currentStep === 1"
                            @click="closeGuide()"
                            class="px-4 py-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 text-xs font-semibold transition cursor-pointer">
                        Lewati
                    </button>

                    <!-- Tombol Sebelumnya (Jika di Langkah > 1) -->
                    <button type="button"
                            x-show="currentStep > 1"
                            @click="prevStep()"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer flex items-center justify-center gap-1.5 shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Sebelumnya</span>
                    </button>

                    <!-- Tombol Selanjutnya (Jika bukan langkah terakhir) -->
                    <button type="button"
                            x-show="currentStep < totalSteps"
                            @click="nextStep()"
                            class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Selanjutnya</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <!-- Tombol Selesai (Jika di langkah terakhir) -->
                    <button type="button"
                            x-show="currentStep === totalSteps"
                            @click="closeGuide()"
                            class="px-5 py-2 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white text-xs font-bold transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                        <span>Mengerti &amp; Masuk ke Portal</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
