@extends('layouts.admin')

@section('content')
<div x-data="{ showModal: false, editMode: false, currentCategory: { id: '', name: '', description: '' } }" class="space-y-6 pb-8">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <p class="text-[11px] font-bold uppercase tracking-widest text-[#ea580c]">Standarisasi Master Data</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Master Kategori Komoditas</h1>
            <p class="text-xs sm:text-sm text-slate-500 max-w-2xl">Kelola daftar kategori bahan baku acuan (Unggas &amp; Daging, Bumbu Dapur, Sayuran Segar, Telur, dan Sembako).</p>
        </div>
        <div class="shrink-0">
            <button type="button"
                    @click="editMode = false; currentCategory = { id: '', name: '', description: '' }; showModal = true"
                    class="inline-flex items-center gap-2 bg-[#ea580c] hover:bg-[#c2410c] text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Kategori Baru</span>
            </button>
        </div>
    </div>

    {{-- Categories Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($categories->isEmpty())
            <div class="px-6 py-16 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Belum ada data kategori master</p>
                    <p class="text-xs text-slate-500 mt-1">Tambahkan kategori bahan baku pertama untuk acuan pemasok dan UMKM.</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-semibold uppercase text-slate-500 tracking-wider">
                            <th class="py-3.5 px-6">Nama Kategori Master</th>
                            <th class="py-3.5 px-6">Slug</th>
                            <th class="py-3.5 px-6">Deskripsi</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-7 h-7 rounded-lg bg-orange-50 text-[#ea580c] flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </span>
                                        <span class="font-semibold text-slate-900 text-[13px]">{{ $cat->name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-medium">
                                        {{ $cat->slug }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 max-w-sm truncate">
                                    {{ $cat->description ?? '-' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="editMode = true; currentCategory = {{ json_encode(['id' => $cat->id, 'name' => $cat->name, 'description' => $cat->description]) }}; showModal = true"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 rounded-xl transition font-semibold text-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat->id) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/70 rounded-xl transition font-semibold text-xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40">
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Modal Form --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="showModal"
                 @click.away="showModal = false"
                 x-transition
                 class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200/80 space-y-5">
                <div class="flex justify-between items-center pb-3.5 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900" x-text="editMode ? 'Edit Kategori Master' : 'Tambah Kategori Baru'"></h3>
                    <button type="button"
                            @click="showModal = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                            aria-label="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="editMode ? '{{ url('/admin/categories') }}/' + currentCategory.id : '{{ route('admin.categories.store') }}'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Nama Kategori Master <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="name"
                               x-model="currentCategory.name"
                               required
                               placeholder="Contoh: Daging & Unggas"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl font-medium text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Deskripsi</label>
                        <textarea name="description"
                                  x-model="currentCategory.description"
                                  rows="3"
                                  placeholder="Rincian cakupan komoditas dalam kategori ini..."
                                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200/90 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#ea580c] focus:ring-2 focus:ring-orange-500/20 transition"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2 border-t border-slate-100">
                        <button type="button"
                                @click="showModal = false"
                                class="w-1/2 py-2.5 bg-white border border-slate-200/90 rounded-xl font-semibold text-xs text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="w-1/2 py-2.5 bg-[#ea580c] hover:bg-[#c2410c] text-white rounded-xl font-semibold text-xs transition shadow-xs cursor-pointer">
                            Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
