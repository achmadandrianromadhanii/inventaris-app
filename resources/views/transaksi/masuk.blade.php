@extends('layouts.app')

@section('title', 'Barang Masuk')
@section('meta_description', 'Catat barang masuk inventaris Website.')

@section('content')
    @php
        $riwayatMasukData = $riwayatMasuk ?? ($riwayat ?? collect());
        $filterTanggal = $filterTanggal ?? [
            'dari' => now()->subDays(30)->format('Y-m-d'),
            'sampai' => now()->format('Y-m-d'),
        ];
        $isPaginator = method_exists($riwayatMasukData, 'links');
    @endphp

    <div class="space-y-3">
        <div>
            <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                Barang Masuk
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Catat barang masuk baru atau tambahkan stok/unit ke barang yang sudah ada.
            </p>
        </div>

        <form method="POST" action="{{ route('transaksi.simpan-masuk') }}"
            class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800"
            x-data="{
                errors: {},
                query: @js(old('nama', '')),
                results: [],
                selected: @js($barangTerpilih ?? null),
                modeBaru: @js(old('mode_barang') === 'baru'),
                tipe: @js(old('tipe', 'aset')),
                merekId: @js(old('merek_id', '')),
                lokasiId: @js(old('lokasi_id', '')),
                isiSpesifikasi: @js(old('spesifikasi') ? true : false),
                isiCatatan: @js(old('catatan') !== null && old('catatan') !== ''),
                isiSerialNumber: false,
                serialNumbers: @js(old('serial_number_list') ? explode("\n", str_replace("\r", "", old('serial_number_list'))) : []),
                snInput: '',
                sumberTujuan: @js(old('sumber_tujuan', '')),
                loading: false,

                init() {
                    if (this.selected) {
                        this.query = this.selected.nama || '';
                    }
                },

                get modeBarang() {
                    if (this.modeBaru) return 'baru';
                    if (this.selected) return 'existing';
                    return '';
                },
                get effectiveTipe() {
                    if (this.modeBaru) return this.tipe;
                    if (this.selected) return this.selected.tipe;
                    return 'aset';
                },

                async cariBarang() {
                    // [OPTIMASI DEBOUNCE]: Timer setTimeout dihapus. Penundaan (delay) 
                    // sekarang ditangani langsung secara native oleh Alpine.js di tag HTML 
                    // menggunakan '@input.debounce.750ms'. Ini mencegah eksekusi beruntun 
                    // dan menghemat memori, menjaga LCP/INP tetap stabil 100%.
                    const keyword = this.query.trim();
                    
                    if (keyword.length < 2) { 
                        this.results = []; 
                        return; 
                    }
                    
                    try {
                        const response = await fetch(`{{ route('api.barang.search') }}?q=${encodeURIComponent(keyword)}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        });
                        
                        if (!response.ok) { 
                            this.results = []; 
                            return; 
                        }
                        
                        const data = await response.json();
                        this.results = Array.isArray(data) ? data : [];
                    } catch (_) { 
                        this.results = []; 
                    }
                },

                pilihBarang(item) {
                    this.selected = item;
                    this.query = item.nama || '';
                    this.results = [];
                    this.modeBaru = false;
                },

                tambahBaru() {
                    this.modeBaru = true;
                    this.selected = null;
                    this.results = [];
                },

                resetPilihan() {
                    this.selected = null;
                    this.modeBaru = false;
                    this.query = '';
                    this.results = [];
                },
                async submitForm(e) {
                    this.loading = true;
                    this.errors = {};
                    const form = e.target;
                    const formData = new FormData(form);
                    
                    try {
                        const response = await fetch(form.action, {
                            method: form.method,
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok) {
                            window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: data.message || 'Berhasil disimpan.', tipe: 'success' } }));
                            
                            // Reset form
                            this.query = '';
                            this.selected = null;
                            this.modeBaru = false;
                            this.merekId = '';
                            this.lokasiId = '';
                            this.snInput = '';
                            this.serialNumbers = [];
                            
                            const formEls = form.querySelectorAll('input:not([type=hidden]), select, textarea');
                            formEls.forEach(el => {
                                if (el.name !== 'tipe' && el.name !== 'mode_barang') {
                                    if (el.type === 'number') el.value = el.defaultValue || 1;
                                    else el.value = '';
                                }
                            });
                            
                            // Refresh tabel riwayat secara AJAX agar tidak berkedip
                            const htmlRes = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const html = await htmlRes.text();
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newTable = doc.querySelector('#riwayat-container');
                            const oldTable = document.querySelector('#riwayat-container');
                            if (newTable && oldTable) {
                                oldTable.innerHTML = newTable.innerHTML;
                            }
                        } else if (response.status === 422) {
                            this.errors = data.errors || {};
                            const firstError = Object.values(this.errors)[0]?.[0] || 'Terdapat kesalahan pengisian.';
                            window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: firstError, tipe: 'error' } }));
                        } else {
                            window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: data.message || 'Terjadi kesalahan.', tipe: 'error' } }));
                        }
                    } catch (error) {
                        window.dispatchEvent(new CustomEvent('tampilkan-toast', { detail: { pesan: 'Gagal menghubungi server.', tipe: 'error' } }));
                    }
                    this.loading = false;
                },
            }" x-init="init()" @submit.prevent="submitForm($event)">
            @csrf

            {{-- Hidden fields --}}
            <input type="hidden" name="mode_barang" :value="modeBarang">
            <input type="hidden" name="barang_id" :value="selected ? selected.id : ''" :disabled="!selected">

            <div class="space-y-4">

                {{-- ============================================================ --}}
                {{-- 1. SEARCH BAR (always visible) --}}
                {{-- ============================================================ --}}
                <div class="relative">
                    <label for="barang_search"
                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Cari atau Tambah Barang
                    </label>

                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                            <i class="bi bi-search text-sm"></i>
                        </span>

                        <!-- [OPTIMASI DEBOUNCE]: Menggunakan '@input.debounce.750ms' bawaan Alpine.js -->
                        <!-- untuk memberi jeda aman 0.75 detik sebelum memanggil API. Pengetikan tetap responsif -->
                        <!-- namun mencegah spam request (mencegah munculnya multiple request di Network). -->
                        <input id="barang_search" type="text" x-model="query"
                            @input="modeBaru ? null : (selected = null)"
                            @input.debounce.750ms="cariBarang()"
                            autocomplete="off" placeholder="Ketik nama barang untuk mencari..."
                            :disabled="selected !== null"
                            class="block w-full rounded-lg border-gray-200 bg-gray-50 py-2 pl-8 pr-10 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                        <button x-cloak x-show="selected || modeBaru" type="button"
                            class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="resetPilihan()" title="Reset pilihan">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    @error('barang_id')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('mode_barang')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    {{-- Search results dropdown --}}
                    <div x-cloak x-show="results.length > 0 && !selected && !modeBaru" x-transition
                        class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <ul class="max-h-64 overflow-y-auto py-1">
                            <template x-for="item in results" :key="item.id">
                                <li>
                                    <button type="button"
                                        class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                        @click="pilihBarang(item)">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                                                x-text="item.nama"></p>
                                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="item.kategori"></span> ·
                                                <span x-text="item.merek"></span> ·
                                                <span x-text="item.tipe === 'aset' ? 'Aset' : 'Stok'"></span>
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right text-xs text-gray-500 dark:text-gray-400">
                                            <p><span x-text="item.kondisi"></span>%</p>
                                            <p x-text="item.tipe === 'aset'
                                                ? ((item.unit_tersedia ?? 0) + ' unit')
                                                : ((item.qty_tersedia ?? 0) + ' stok')">
                                            </p>
                                        </div>
                                    </button>
                                </li>
                            </template>
                        </ul>

                        {{-- "Tambah Baru" button at the bottom of dropdown --}}
                        <div class="border-t border-gray-200 p-1.5 dark:border-gray-700">
                            <button type="button" @click="tambahBaru()"
                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambah "<span x-text="query"></span>" sebagai Barang Baru</span>
                            </button>
                        </div>
                    </div>

                    {{-- Show "Tambah Baru" when results are empty but query has 2+ chars --}}
                    <div x-cloak x-show="results.length === 0 && query.trim().length >= 2 && !selected && !modeBaru" x-transition
                        class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <div class="p-2 text-center text-xs text-gray-500 dark:text-gray-400">
                            Tidak ada barang ditemukan.
                        </div>
                        <div class="border-t border-gray-200 p-1.5 dark:border-gray-700">
                            <button type="button" @click="tambahBaru()"
                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambah "<span x-text="query"></span>" sebagai Barang Baru</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- 2. SELECTED EXISTING ITEM INFO CARD --}}
                {{-- ============================================================ --}}
                <div x-cloak x-show="selected" x-transition
                    class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900/30 dark:bg-blue-900/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-blue-800 dark:text-blue-300"
                                x-text="selected ? selected.nama : ''"></p>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">
                                <span x-text="selected ? selected.kategori : ''"></span> ·
                                <span x-text="selected ? selected.merek : ''"></span> ·
                                <span x-text="selected ? selected.lokasi : ''"></span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                <span x-text="selected ? (selected.tipe === 'aset' ? 'Aset' : 'Stok') : '-'"></span>
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                <span x-text="selected ? selected.label_kondisi : ''"></span>
                                <span class="ml-1" x-text="selected ? selected.kondisi + '%' : ''"></span>
                            </span>

                            <button type="button" @click="resetPilihan()" title="Batalkan pilihan"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-full text-blue-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-300">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-2 text-xs text-blue-700 dark:text-blue-400">
                        <span x-cloak x-show="selected && selected.tipe === 'aset'">
                            Unit tersedia: <strong x-text="selected ? (selected.unit_tersedia ?? 0) : 0"></strong>
                        </span>
                        <span x-cloak x-show="selected && selected.tipe === 'stok'">
                            Stok tersedia: <strong x-text="selected ? (selected.qty_tersedia ?? 0) : 0"></strong>
                        </span>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- 3. NEW ITEM FORM (modeBaru) --}}
                {{-- ============================================================ --}}
                <div x-cloak x-show="modeBaru" x-transition
                    class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3 dark:border-emerald-900/30 dark:bg-emerald-900/10 lg:p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                            <i class="bi bi-plus-circle mr-1"></i>
                            Registrasi Barang Baru
                        </h3>
                        <button type="button" @click="resetPilihan()" title="Kembali ke pencarian"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-emerald-400 hover:bg-emerald-100 hover:text-emerald-600 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-300">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    <div class="space-y-3">
                        {{-- Nama Barang (auto-filled from search query) --}}
                        <div>
                            <label for="nama"
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Nama Barang <span class="text-red-500">*</span>
                            </label>
                            <input id="nama" name="nama" type="text" x-model="query"
                                :disabled="!modeBaru"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            @error('nama')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tipe Barang toggle --}}
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Tipe Barang
                            </label>

                            <div class="inline-flex w-fit gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                                <button type="button" @click="tipe = 'aset'"
                                    :class="tipe === 'aset'
                                        ? 'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100'
                                        : 'text-gray-500 dark:text-gray-300'"
                                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs">
                                    <i class="bi bi-cpu"></i>
                                    <span>Aset</span>
                                </button>

                                <button type="button" @click="tipe = 'stok'"
                                    :class="tipe === 'stok'
                                        ? 'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100'
                                        : 'text-gray-500 dark:text-gray-300'"
                                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs">
                                    <i class="bi bi-stack"></i>
                                    <span>Stok</span>
                                </button>
                            </div>

                            <input type="hidden" name="tipe" :value="tipe" :disabled="!modeBaru">
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            {{-- Left column --}}
                            <div class="space-y-3">
                                {{-- Kategori --}}
                                <div>
                                    <label for="kategori_id"
                                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Kategori <span class="text-red-500">*</span>
                                    </label>
                                    <select id="kategori_id" name="kategori_id" :disabled="!modeBaru"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="">Pilih kategori</option>
                                        @foreach ($kategori as $item)
                                            <option value="{{ $item->id }}" @selected((string) old('kategori_id') === (string) $item->id)>
                                                {{ $item->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('kategori_id')
                                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Merek --}}
                                <div>
                                    <label for="merek_id"
                                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Merek
                                    </label>
                                    <select id="merek_id" name="merek_id" x-model="merekId" :disabled="!modeBaru"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="">Pilih merek</option>
                                        @foreach ($merek as $item)
                                            <option value="{{ $item->id }}" @selected((string) old('merek_id') === (string) $item->id)>
                                                {{ $item->nama }}
                                            </option>
                                        @endforeach
                                        <option value="lainnya" @selected((string) old('merek_id') === 'lainnya')>Lainnya</option>
                                    </select>

                                    <div x-cloak x-show="merekId === 'lainnya'" x-transition class="mt-2">
                                        <input id="merek_manual" name="merek_manual" type="text" value="{{ old('merek_manual') }}"
                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            placeholder="Ketik nama merek baru...">
                                        @error('merek_manual')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @error('merek_id')
                                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Right column --}}
                            <div class="space-y-3">
                                {{-- Lokasi --}}
                                <div>
                                    <label for="lokasi_id"
                                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Lokasi
                                    </label>
                                    <select id="lokasi_id" name="lokasi_id" x-model="lokasiId" :disabled="!modeBaru"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="">Pilih lokasi</option>
                                        @foreach ($lokasi as $item)
                                            <option value="{{ $item->id }}" @selected((string) old('lokasi_id') === (string) $item->id)>
                                                {{ $item->nama }}
                                            </option>
                                        @endforeach
                                        <option value="lainnya" @selected((string) old('lokasi_id') === 'lainnya')>Lainnya</option>
                                    </select>

                                    <div x-cloak x-show="lokasiId === 'lainnya'" x-transition class="mt-2">
                                        <input id="lokasi_manual" name="lokasi_manual" type="text" value="{{ old('lokasi_manual') }}"
                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            placeholder="Ketik nama lokasi baru...">
                                        @error('lokasi_manual')
                                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @error('lokasi_id')
                                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Spesifikasi (checkbox toggle) --}}
                                <div>
                                    <label class="inline-flex cursor-pointer items-center gap-2">
                                        <input type="checkbox" x-model="isiSpesifikasi"
                                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Spesifikasi</span>
                                    </label>

                                    <div x-cloak x-show="isiSpesifikasi" x-transition class="mt-2">
                                        <textarea id="spesifikasi" name="spesifikasi" rows="3"
                                            placeholder="Masukkan spesifikasi barang..."
                                            :disabled="!modeBaru || !isiSpesifikasi"
                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('spesifikasi') }}</textarea>
                                    </div>

                                    @error('spesifikasi')
                                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Removed Tahun Pengadaan --}}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- 4. TRANSACTION DETAILS (shared, show when selected || modeBaru) --}}
                {{-- ============================================================ --}}
                <div x-cloak x-show="selected || modeBaru" x-transition class="space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Detail Transaksi Masuk
                    </h3>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        {{-- Left column --}}
                        <div class="space-y-3">
                            {{-- Jumlah Masuk --}}
                            <div>
                                <label for="jumlah_masuk"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    <span x-text="modeBaru ? (tipe === 'aset' ? 'Jumlah Unit' : 'Jumlah Total') : 'Jumlah Masuk'"></span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input id="jumlah_masuk" name="jumlah_masuk" type="number" min="1"
                                    max="1000" value="{{ old('jumlah_masuk', 1) }}"
                                    :disabled="!selected && !modeBaru"
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <p x-cloak x-show="effectiveTipe === 'aset'"
                                    class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Nomor unit akan dibuat otomatis berdasarkan kategori.
                                </p>
                                @error('jumlah_masuk')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Serial Number (aset only) --}}
                            <div x-cloak x-show="effectiveTipe === 'aset'" x-transition class="rounded-xl border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
                                <label class="mb-2 flex cursor-pointer items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                        Catat Serial Number per Unit?
                                    </span>
                                    <div class="relative inline-block w-10 shrink-0 align-middle select-none transition duration-200 ease-in">
                                        <input type="checkbox" x-model="isiSerialNumber"
                                            class="peer absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer transition-transform duration-300 ease-in-out border-gray-300 dark:border-gray-600 checked:border-indigo-600 checked:translate-x-5" />
                                        <div class="block overflow-hidden h-5 rounded-full bg-gray-200 cursor-pointer peer-checked:bg-indigo-200 dark:bg-gray-700 dark:peer-checked:bg-indigo-900/50"></div>
                                    </div>
                                </label>

                                <div x-cloak x-show="isiSerialNumber" x-transition.opacity class="mt-3">
                                    <div class="relative">
                                        <input type="text" x-model="snInput"
                                            @keydown.enter.prevent="
                                                let val = snInput.trim();
                                                if(val && !serialNumbers.includes(val)) {
                                                    serialNumbers.push(val);
                                                    snInput = '';
                                                }
                                            "
                                            placeholder="Ketik/Scan SN lalu tekan Enter..."
                                            :disabled="(!selected && !modeBaru) || effectiveTipe !== 'aset'"
                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                            <i class="bi bi-upc-scan text-lg"></i>
                                        </div>
                                    </div>
                                    
                                    <p class="mt-1.5 text-[10px] text-gray-500 dark:text-gray-400 flex items-center justify-between">
                                        <span>Gunakan Barcode Scanner / Tekan Enter</span>
                                        <span class="font-medium text-indigo-600 dark:text-indigo-400" x-text="serialNumbers.length + ' discan'"></span>
                                    </p>

                                    {{-- Render Chips --}}
                                    <div class="mt-2 flex flex-wrap gap-1.5" x-show="serialNumbers.length > 0">
                                        <template x-for="(sn, index) in serialNumbers" :key="index">
                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-900/30 dark:text-indigo-300 dark:ring-indigo-400/20 shadow-sm transition-all hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                                <span x-text="sn"></span>
                                                <button type="button" @click="serialNumbers.splice(index, 1)" class="group -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-indigo-600/20 dark:hover:bg-indigo-400/20 transition-colors">
                                                    <i class="bi bi-x text-[10px] text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-200"></i>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                    
                                    {{-- Hidden input for backend --}}
                                    <input type="hidden" name="serial_number_list" :value="isiSerialNumber ? serialNumbers.join('\n') : ''">
                                </div>
                            </div>

                            {{-- Sumber --}}
                            <div>
                                <label for="sumber_tujuan"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Sumber (Asal Barang)
                                </label>
                                <input id="sumber_tujuan" name="sumber_tujuan" type="text"
                                    x-model="sumberTujuan" maxlength="200"
                                    placeholder="Ketik manual atau pilih pil cepat di bawah"
                                    :disabled="!selected && !modeBaru"
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                
                                {{-- Quick-Pills --}}
                                <div class="mt-2 flex flex-wrap gap-1.5" x-show="selected || modeBaru">
                                    <button type="button" @click="sumberTujuan = 'Dana BOS'"
                                        class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors">
                                        <i class="bi bi-lightning-charge mr-1"></i> Dana BOS
                                    </button>
                                    <button type="button" @click="sumberTujuan = 'Pengadaan Sekolah'"
                                        class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors">
                                        <i class="bi bi-building mr-1"></i> Pengadaan Sekolah
                                    </button>
                                    <button type="button" @click="sumberTujuan = 'Donasi / Hibah'"
                                        class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors">
                                        <i class="bi bi-gift mr-1"></i> Donasi / Hibah
                                    </button>
                                    <button type="button" @click="sumberTujuan = 'Pembelian Mandiri'"
                                        class="inline-flex items-center rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[10px] font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors">
                                        <i class="bi bi-cart mr-1"></i> Pembelian Mandiri
                                    </button>
                                </div>
                                
                                @error('sumber_tujuan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Right column --}}
                        <div class="space-y-3">
                            {{-- Tanggal Masuk (Otomatis Real-time) --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Tanggal Masuk <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="bi bi-clock-history"></i>
                                    {{ now()->isoFormat('D MMMM YYYY') }} (Otomatis)
                                </div>
                            </div>

                            {{-- Kondisi Saat Itu --}}
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Kondisi Saat Itu % <span class="text-red-500">*</span>
                                </label>
                                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm font-medium text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400 flex items-center gap-2">
                                    <i class="bi bi-stars"></i>
                                    100% (Barang Masuk otomatis mulus)
                                </div>
                                <input type="hidden" name="kondisi_saat_itu" value="100" :disabled="!selected && !modeBaru">

                                @error('kondisi_saat_itu')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Catatan --}}
                            <div>
                                <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" x-model="isiCatatan"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900">
                                    <span>Catatan</span>
                                </label>
                                <textarea x-cloak x-show="isiCatatan" x-transition
                                    id="catatan" name="catatan" rows="3"
                                    :disabled="(!selected && !modeBaru) || !isiCatatan"
                                    class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan') }}</textarea>
                                @error('catatan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700">
                        <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-1.5 text-sm text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 shadow-md shadow-emerald-500/30">
                            <span x-show="!loading">
                                <i class="bi bi-check-lg"></i>
                                Simpan Barang Masuk
                            </span>

                            <span x-cloak x-show="loading" class="inline-flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <section id="riwayat-container" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Riwayat 30 Hari
                    </h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Riwayat transaksi barang masuk terbaru.
                    </p>
                </div>

                <form method="GET" action="{{ route('transaksi.masuk') }}" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="dari" value="{{ $filterTanggal['dari'] }}"
                        class="rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <input type="date" name="sampai" value="{{ $filterTanggal['sampai'] }}"
                        class="rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <button type="submit"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Terapkan
                    </button>
                </form>
            </div>

            @if ($riwayatMasukData->count() > 0)
                <div class="overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Tanggal
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Barang
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Tipe
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Jumlah
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Kondisi
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Sumber
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Admin
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatMasukData as $trx)
                                <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ optional($trx->tanggal_transaksi)->format('d M Y') }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        @if ($trx->barang)
                                            <a href="{{ route('barang.show', $trx->barang) }}"
                                                class="hover:text-blue-600 dark:hover:text-blue-400">
                                                {{ $trx->barang->nama }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <span
                                            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $trx->barang?->tipe === 'aset'
                                                ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400'
                                                : 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-900/20 dark:text-violet-400' }}">
                                            {{ $trx->barang?->tipe === 'aset' ? 'Aset' : 'Stok' }}
                                        </span>
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ $trx->jumlah }}
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        @if ($trx->kondisi_saat_itu !== null)
                                            <x-kondisi-badge :kondisi="$trx->kondisi_saat_itu" :show-value="true" />
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $trx->sumber_tujuan ?: '—' }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $trx->pengguna?->nama ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>



                @if ($isPaginator)
                    <div class="pt-3">
                        {{ $riwayatMasukData->links('components.pagination') }}
                    </div>
                @endif
            @else
                <x-empty-state icon="bi-arrow-down-circle" title="Belum ada riwayat barang masuk"
                    message="Riwayat transaksi barang masuk akan muncul di sini." />
            @endif
        </section>
    </div>
@endsection
