@extends('layouts.supplier')

@section('title', 'Kelola Akun Pemasok — Portal Pemasok SORAI')

@section('content')
<div class="space-y-6 pb-10">

    {{-- Header --}}
    <div class="border-b border-slate-200/80 pb-5">
        <div class="space-y-1">
            <a href="{{ route('supplier.dashboard') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#ea580c] transition mb-1 group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Kembali ke Dashboard Pemasok</span>
            </a>
            <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#ea580c] block">
                Pengaturan Akun &amp; Profil Pemasok
            </span>
            <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight">
                Kelola Akun Pemasok
            </h1>
            <p class="text-xs sm:text-[13px] text-slate-500 max-w-2xl leading-relaxed">
                Kelola identitas kelompok tani atau distributor, kapasitas pasok, area pengiriman armada, serta keamanan kata sandi akun Anda.
            </p>
        </div>
    </div>

    {{-- Validation Summary --}}
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200/90 rounded-2xl p-4 text-xs text-rose-900 space-y-1">
            <div class="flex items-center gap-2 font-bold text-rose-700">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Mohon periksa kembali data yang Anda masukkan:</span>
            </div>
            <ul class="list-disc list-inside text-[11px] font-medium text-rose-700 space-y-0.5 pl-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $displayCompany = $profile?->company_name ?? 'Mitra Pemasok';
        $initial = strtoupper(substr(trim($displayCompany), 0, 1)) ?: 'S';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Left Column: Summary & Password --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Account Summary Card --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-6 space-y-5"
                 x-data="{
                     isEditingPhoto: {{ $errors->has('photo') ? 'true' : 'false' }},
                     previewUrl: null,
                     selectedFileName: '',
                     handlePhotoChange(event) {
                         const file = event.target.files?.[0];
                         if (!file) {
                             this.resetPreview();
                             return;
                         }
                         if (this.previewUrl) {
                             URL.revokeObjectURL(this.previewUrl);
                         }
                         this.previewUrl = URL.createObjectURL(file);
                         this.selectedFileName = file.name;
                     },
                     resetPreview() {
                         if (this.previewUrl) {
                             URL.revokeObjectURL(this.previewUrl);
                             this.previewUrl = null;
                         }
                         this.selectedFileName = '';
                         if (this.$refs.photoInput) {
                             this.$refs.photoInput.value = '';
                         }
                     },
                     cancelPhotoEdit() {
                         this.resetPreview();
                         this.isEditingPhoto = false;
                     }
                 }">
                <div class="flex items-center gap-3.5">
                    <div class="relative w-14 h-14 shrink-0">
                        <img x-show="previewUrl"
                             x-cloak
                             :src="previewUrl"
                             alt="Pratinjau foto profil"
                             class="w-14 h-14 rounded-2xl object-cover border border-orange-300 bg-slate-100 shadow-2xs">
                        @if($user->profile_photo_url)
                            <img x-show="!previewUrl"
                                 src="{{ $user->profile_photo_url }}"
                                 alt="Foto Profil {{ $displayCompany }}"
                                 class="w-14 h-14 rounded-2xl object-cover border border-slate-200/80 bg-slate-100 shadow-2xs">
                        @else
                            <div x-show="!previewUrl"
                                 class="w-14 h-14 rounded-2xl bg-[#161922] text-white font-bold text-lg flex items-center justify-center">
                                {{ $initial }}
                            </div>
                        @endif

                        <button type="button"
                                x-show="!isEditingPhoto"
                                @click="isEditingPhoto = true"
                                class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white hover:bg-orange-50 text-slate-600 hover:text-[#ea580c] border border-slate-200 hover:border-orange-300 shadow-xs flex items-center justify-center transition cursor-pointer"
                                title="Ganti Foto Profil"
                                aria-label="Ganti Foto Profil">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-slate-900 truncate">{{ $displayCompany }}</h2>
                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ $user->email }}</p>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200/70">
                                Pemasok Lokal
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Terverifikasi</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div x-show="!isEditingPhoto">
                    <button type="button"
                            @click="isEditingPhoto = true"
                            class="w-full py-2 px-3 rounded-xl border border-slate-200/90 bg-white hover:bg-orange-50/70 hover:border-orange-300 text-slate-700 hover:text-[#c2410c] font-semibold text-xs inline-flex items-center justify-center gap-1.5 shadow-2xs transition cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-[#ea580c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Ganti Foto Profil</span>
                    </button>
                </div>

                <div x-show="isEditingPhoto"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="border-t border-slate-100 pt-4 space-y-3 text-xs">
                    <form method="POST"
                          action="{{ route('supplier.account.photo.update') }}"
                          enctype="multipart/form-data"
                          class="space-y-3">
                        @csrf
                        @method('PUT')

                        <label for="profile_photo"
                               class="flex flex-col items-center justify-center gap-1.5 px-3 py-3 rounded-xl border border-dashed {{ $errors->has('photo') ? 'border-rose-400 bg-rose-50/50' : 'border-slate-300 hover:border-[#ea580c] bg-slate-50/70 hover:bg-orange-50/30' }} text-center cursor-pointer transition">
                            <svg class="w-5 h-5 text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span class="font-semibold text-slate-700 truncate max-w-full" x-text="selectedFileName || 'Pilih file foto profil'"></span>
                            <span class="text-[11px] text-slate-400">JPG, PNG, WEBP (Maks. 2 MB)</span>
                            <input type="file"
                                   x-ref="photoInput"
                                   id="profile_photo"
                                   name="photo"
                                   accept="image/jpeg,image/png,image/webp"
                                   required
                                   @change="handlePhotoChange($event)"
                                   class="sr-only">
                        </label>

                        @error('photo')
                            <p class="text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="cancelPhotoEdit()"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200/90 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="flex-1 py-2 px-3.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                                Simpan Foto
                            </button>
                        </div>
                    </form>

                    @if($user->profile_photo_url)
                        <form method="POST" action="{{ route('supplier.account.photo.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full py-2 px-3 rounded-xl border border-rose-200/80 bg-rose-50/50 hover:bg-rose-50 text-rose-600 font-semibold text-[11px] transition cursor-pointer">
                                Hapus Foto Profil
                            </button>
                        </form>
                    @endif
                </div>

                <div class="border-t border-slate-100 pt-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400 font-medium">Komoditas Utama</span>
                        <span class="font-semibold text-slate-800 text-right truncate max-w-[175px]">{{ $profile?->commodity?->name ?? 'Belum dipilih' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400 font-medium">Area Pengiriman</span>
                        <span class="font-semibold text-slate-800 text-right truncate max-w-[175px]">{{ $profile?->delivery_area ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400 font-medium">Nomor NIB</span>
                        <span class="font-semibold text-slate-800 tabular-nums">{{ $profile?->nib_number ?: 'Belum diisi' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-400 font-medium">Terdaftar Sejak</span>
                        <span class="font-semibold text-slate-800 tabular-nums">{{ $user->created_at?->locale('id')->translatedFormat('d M Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- Security / Password Card --}}
            @php
                $hasPasswordErrors = $errors->hasAny(['current_password', 'password', 'password_confirmation']);
                $hasProfileErrors = $errors->hasAny(['email', 'company_name', 'owner_name', 'phone', 'commodity_id', 'max_capacity', 'delivery_area', 'nib_number', 'warehouse_address']);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-6 space-y-5"
                 x-data="{
                     isEditingPassword: {{ $hasPasswordErrors ? 'true' : 'false' }},
                     showCurrent: false,
                     showNew: false,
                     showConfirm: false,
                     startPasswordEdit() {
                         this.isEditingPassword = true;
                         this.$nextTick(() => this.$refs.currentPasswordInput?.focus());
                     },
                     cancelPasswordEdit() {
                         @if($hasPasswordErrors)
                             window.location.href = '{{ route('supplier.account.edit') }}';
                         @else
                             this.$refs.passwordForm?.reset();
                             this.showCurrent = false;
                             this.showNew = false;
                             this.showConfirm = false;
                             this.isEditingPassword = false;
                         @endif
                     }
                 }">
                <div class="flex flex-wrap items-start justify-between gap-2.5 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-900">Keamanan &amp; Kata Sandi</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Ubah kata sandi secara berkala untuk menjaga keamanan akun pemasok.</p>
                        </div>
                    </div>

                    <button type="button"
                            x-show="!isEditingPassword"
                            @click="startPasswordEdit()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/90 bg-white hover:bg-orange-50/70 hover:border-orange-300 text-slate-700 hover:text-[#c2410c] font-semibold text-[11px] shadow-2xs transition cursor-pointer shrink-0"
                            title="Edit Kata Sandi"
                            aria-label="Edit Kata Sandi">
                        <svg class="w-3.5 h-3.5 text-[#ea580c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Edit</span>
                    </button>

                    <button type="button"
                            x-show="isEditingPassword"
                            x-cloak
                            @click="cancelPasswordEdit()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/90 bg-white hover:bg-rose-50/70 hover:border-rose-200 text-slate-600 hover:text-rose-600 font-semibold text-[11px] shadow-2xs transition cursor-pointer shrink-0"
                            title="Batalkan Edit Kata Sandi"
                            aria-label="Batalkan Edit Kata Sandi">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Batalkan Edit</span>
                    </button>
                </div>

                {{-- Read-only Password State --}}
                <div x-show="!isEditingPassword" class="space-y-3 text-xs">
                    <div class="flex items-center justify-between gap-3 px-3.5 py-3 rounded-xl bg-slate-50/80 border border-slate-200/70">
                        <div class="min-w-0">
                            <span class="block text-[11px] font-semibold text-slate-500">Kata Sandi Akun</span>
                            <span class="block text-sm font-bold tracking-[0.2em] text-slate-700 mt-0.5 select-none">••••••••••••</span>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200/80 text-[10px] font-semibold text-slate-500 shrink-0">
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Terkunci</span>
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Klik tombol <span class="font-semibold text-slate-600">Edit</span> dengan ikon pensil di kanan atas kartu ini untuk mengganti kata sandi akun Anda.
                    </p>
                </div>

                {{-- Editable Password Form --}}
                <form x-ref="passwordForm"
                      x-show="isEditingPassword"
                      x-cloak
                      x-transition:enter="transition ease-out duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      method="POST"
                      action="{{ route('supplier.account.password.update') }}"
                      class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block font-semibold text-slate-700 mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showCurrent ? 'text' : 'password'"
                                   x-ref="currentPasswordInput"
                                   id="current_password"
                                   name="current_password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="Masukkan kata sandi saat ini"
                                   class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('current_password') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                            <button type="button"
                                    @click="showCurrent = !showCurrent"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 transition cursor-pointer"
                                    aria-label="Tampilkan atau sembunyikan kata sandi saat ini">
                                <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showCurrent" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block font-semibold text-slate-700 mb-1.5">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   required
                                   minlength="8"
                                   autocomplete="new-password"
                                   placeholder="Minimal 8 karakter"
                                   class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('password') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                            <button type="button"
                                    @click="showNew = !showNew"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 transition cursor-pointer"
                                    aria-label="Tampilkan atau sembunyikan kata sandi baru">
                                <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showNew" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block font-semibold text-slate-700 mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   minlength="8"
                                   autocomplete="new-password"
                                   placeholder="Ulangi kata sandi baru"
                                   class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('password_confirmation') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                            <button type="button"
                                    @click="showConfirm = !showConfirm"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-700 transition cursor-pointer"
                                    aria-label="Tampilkan atau sembunyikan konfirmasi kata sandi">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showConfirm" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2.5 pt-1">
                        <button type="button"
                                @click="cancelPasswordEdit()"
                                class="px-4 py-2.5 rounded-xl border border-slate-200/90 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="flex-1 py-2.5 px-4 rounded-xl bg-[#161922] hover:bg-slate-800 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- Right Column: Supplier Account & Capacity Form --}}
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-[0_1px_2px_rgba(15,23,42,0.04)] p-6 sm:p-8"
                 x-data="{
                     isEditingProfile: {{ $hasProfileErrors ? 'true' : 'false' }},
                     startProfileEdit() {
                         this.isEditingProfile = true;
                         this.$nextTick(() => this.$refs.emailInput?.focus());
                     },
                     cancelProfileEdit() {
                         @if($hasProfileErrors)
                             window.location.href = '{{ route('supplier.account.edit') }}';
                         @else
                             this.$refs.profileForm?.reset();
                             this.isEditingProfile = false;
                         @endif
                     }
                 }">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-5 mb-6">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Informasi Akun &amp; Operasional Pemasok</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Informasi ini ditampilkan kepada mitra UMKM pada katalog komoditas, pengajuan kontrak, dan jadwal distribusi.</p>
                    </div>

                    <button type="button"
                            x-show="!isEditingProfile"
                            @click="startProfileEdit()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-slate-200/90 bg-white hover:bg-orange-50/70 hover:border-orange-300 text-slate-700 hover:text-[#c2410c] font-semibold text-xs shadow-2xs transition cursor-pointer shrink-0"
                            aria-label="Edit Data Akun">
                        <svg class="w-4 h-4 text-[#ea580c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Edit Data</span>
                    </button>

                    <div x-show="isEditingProfile"
                         x-cloak
                         class="flex items-center gap-3 shrink-0">
                        <span class="text-[11px] font-medium text-slate-400">
                            <span class="text-rose-500 font-bold">*</span> Wajib diisi
                        </span>
                        <button type="button"
                                @click="cancelProfileEdit()"
                                class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200/90 bg-white hover:bg-rose-50/70 hover:border-rose-200 text-slate-600 hover:text-rose-600 font-semibold text-xs shadow-2xs transition cursor-pointer shrink-0"
                                aria-label="Batalkan Edit Data Akun">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Batalkan Edit</span>
                        </button>
                    </div>
                </div>

                {{-- Read-only Profile Preview --}}
                <div x-show="!isEditingProfile" class="space-y-6 text-xs">
                    <div class="space-y-4">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            1. Kredensial &amp; Identitas Pemasok
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Email Pemasok Resmi</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium text-slate-800">
                                    {{ $user->email }}
                                </div>
                            </div>

                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Nama Kelompok Tani / Distributor</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium {{ filled($profile?->company_name) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->company_name) ? $profile->company_name : 'Belum diisi' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Nama Pemilik / Penanggung Jawab (PIC)</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium {{ filled($profile?->owner_name) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->owner_name) ? $profile->owner_name : 'Belum diisi' }}
                                </div>
                            </div>

                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Nomor WhatsApp Operasional</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium tabular-nums {{ filled($profile?->phone) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->phone) ? $profile->phone : 'Belum diisi' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 pt-5 border-t border-slate-100">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            2. Kapasitas Pasok &amp; Legalitas Usaha
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Kategori Komoditas Utama</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium {{ filled($profile?->commodity?->name) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ $profile?->commodity?->name ?: 'Belum dipilih' }}
                                </div>
                            </div>

                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Kapasitas Pasok Maksimal</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium {{ filled($profile?->max_capacity) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->max_capacity) ? $profile->max_capacity : 'Belum diisi' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Jangkauan Area Pengiriman Armada</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium {{ filled($profile?->delivery_area) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->delivery_area) ? $profile->delivery_area : 'Belum diisi' }}
                                </div>
                            </div>

                            <div>
                                <span class="block font-semibold text-slate-500 mb-1.5">Nomor NIB / Legalitas Usaha</span>
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium tabular-nums {{ filled($profile?->nib_number) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                    {{ filled($profile?->nib_number) ? $profile->nib_number : 'Belum diisi' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 pt-5 border-t border-slate-100">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            3. Lokasi Sentra / Gudang Pengiriman
                        </h3>

                        <div>
                            <span class="block font-semibold text-slate-500 mb-1.5">Alamat Gudang / Lokasi Sentra Pengiriman</span>
                            <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200/70 text-xs sm:text-[13px] font-medium whitespace-pre-line min-h-[76px] {{ filled($profile?->warehouse_address) ? 'text-slate-800' : 'text-slate-400 italic' }}">
                                {{ filled($profile?->warehouse_address) ? $profile->warehouse_address : 'Belum diisi' }}
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Editable Profile Form --}}
                <form x-ref="profileForm"
                      x-show="isEditingProfile"
                      x-cloak
                      x-transition:enter="transition ease-out duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      method="POST"
                      action="{{ route('supplier.account.update') }}"
                      class="space-y-6 text-xs">
                    @csrf
                    @method('PUT')

                    {{-- Section 1: Kredensial & Identitas Lembaga --}}
                    <div class="space-y-4">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            1. Kredensial &amp; Identitas Pemasok
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block font-semibold text-slate-700 mb-1.5">
                                    Email Pemasok Resmi <span class="text-rose-500">*</span>
                                </label>
                                <input type="email"
                                       x-ref="emailInput"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       autocomplete="email"
                                       placeholder="contoh: supplier@poultrymalang.com"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('email') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                @error('email')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="company_name" class="block font-semibold text-slate-700 mb-1.5">
                                    Nama Kelompok Tani / Distributor <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       id="company_name"
                                       name="company_name"
                                       value="{{ old('company_name', $profile?->company_name) }}"
                                       required
                                       minlength="3"
                                       placeholder="Contoh: Kelompok Tani Poultry Malang"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('company_name') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                @error('company_name')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="owner_name" class="block font-semibold text-slate-700 mb-1.5">
                                    Nama Pemilik / Penanggung Jawab (PIC)
                                </label>
                                <input type="text"
                                       id="owner_name"
                                       name="owner_name"
                                       value="{{ old('owner_name', $profile?->owner_name) }}"
                                       placeholder="Contoh: Haji Mansyur"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('owner_name') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                <p class="text-[11px] text-slate-400 mt-1">Jika dikosongkan, akan menggunakan nama sebelumnya atau nama usaha.</p>
                                @error('owner_name')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block font-semibold text-slate-700 mb-1.5">
                                    Nomor WhatsApp Operasional <span class="text-rose-500">*</span>
                                </label>
                                <input type="tel"
                                       id="phone"
                                       name="phone"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       maxlength="15"
                                       value="{{ old('phone', $profile?->phone) }}"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       required
                                       placeholder="Contoh: 081234567890"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('phone') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 tabular-nums placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                <p class="text-[11px] text-slate-400 mt-1">10–15 digit angka untuk komunikasi order dengan mitra UMKM.</p>
                                @error('phone')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Kapasitas Pasok & Legalitas --}}
                    <div class="space-y-4 pt-5 border-t border-slate-100">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            2. Kapasitas Pasok &amp; Legalitas Usaha
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="commodity_id" class="block font-semibold text-slate-700 mb-1.5">
                                    Kategori Komoditas Utama
                                </label>
                                <select id="commodity_id"
                                        name="commodity_id"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('commodity_id') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition cursor-pointer">
                                    <option value="">-- Pilih Kategori Komoditas --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (string) old('commodity_id', $profile?->commodity_id) === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('commodity_id')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="max_capacity" class="block font-semibold text-slate-700 mb-1.5">
                                    Kapasitas Pasok Maksimal <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       id="max_capacity"
                                       name="max_capacity"
                                       value="{{ old('max_capacity', $profile?->max_capacity) }}"
                                       required
                                       placeholder="Contoh: 1.500 kg / Minggu"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('max_capacity') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                @error('max_capacity')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="delivery_area" class="block font-semibold text-slate-700 mb-1.5">
                                    Jangkauan Area Pengiriman Armada <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       id="delivery_area"
                                       name="delivery_area"
                                       value="{{ old('delivery_area', $profile?->delivery_area) }}"
                                       required
                                       placeholder="Contoh: Kota Malang, Kab. Malang, Batu"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('delivery_area') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                @error('delivery_area')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="nib_number" class="block font-semibold text-slate-700 mb-1.5">
                                    Nomor NIB / Legalitas Usaha (Opsional)
                                </label>
                                <input type="text"
                                       id="nib_number"
                                       name="nib_number"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       maxlength="20"
                                       value="{{ old('nib_number', $profile?->nib_number) }}"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       placeholder="Contoh: 9120003810"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('nib_number') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 tabular-nums placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                                @error('nib_number')
                                    <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Lokasi Sentra / Gudang --}}
                    <div class="space-y-4 pt-5 border-t border-slate-100">
                        <h3 class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            3. Lokasi Sentra / Gudang Pengiriman
                        </h3>

                        <div>
                            <label for="warehouse_address" class="block font-semibold text-slate-700 mb-1.5">
                                Alamat Gudang / Lokasi Sentra Pengiriman <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="warehouse_address"
                                      name="warehouse_address"
                                      rows="3"
                                      required
                                      minlength="5"
                                      placeholder="Contoh: Jl. Raya Kepanjen No. 12, Kab. Malang"
                                      class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border {{ $errors->has('warehouse_address') ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-slate-200/90' }} text-xs sm:text-[13px] font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">{{ old('warehouse_address', $profile?->warehouse_address) }}</textarea>
                            @error('warehouse_address')
                                <p class="text-[11px] font-semibold text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Submit Row --}}
                    <div class="pt-5 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3">
                        <p class="text-[11px] text-slate-400">
                            Jika Anda mengganti email, gunakan email baru pada saat login berikutnya.
                        </p>
                        <div class="flex items-center justify-end gap-2.5 shrink-0">
                            <button type="button"
                                    @click="cancelProfileEdit()"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200/90 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                                Simpan Perubahan Akun
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>

</div>
@endsection

