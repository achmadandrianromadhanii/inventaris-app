@extends('layouts.app')

@section('title', 'Barang Keluar')
@section('meta_description', 'Catat barang keluar inventaris Shiro.')

@section('content')
    @php
        $riwayatData = $riwayat ?? collect();
        $filterTanggal = $filterTanggal ?? [
            'dari' => now()->subDays(30)->format('Y-m-d'),
            'sampai' => now()->format('Y-m-d'),
        ];
        $isPaginator = method_exists($riwayatData, 'links');
    @endphp

    <div class="space-y-3">
        <div>
            <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                Barang Keluar
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Catat perpindahan, pembuangan, hibah, atau proses keluar lainnya.
            </p>
        </div>

        <form method="POST" action="{{ route('transaksi.simpan-keluar') }}"
            class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 lg:p-4"
            x-data="{
                query: '',
                results: [],
                selected: @js($barangTerpilih ?? null),
                units: [],
                selectedUnitIds: @js(collect(old('unit_barang_ids', []))->map(fn($id) => (string) $id)->values()->all()),
                loadingUnits: false,
                searchTimeout: null,
                alasan: @js(old('alasan_keluar', 'pindah_lokasi')),
                lokasiTujuanMode: @js(old('_lokasi_tujuan_mode', old('lokasi_tujuan_id'))),
                loading: false,
                barangSearchUrl: @js(route('api.barang.search')),
                unitBaseUrl: @js(url('/api/unit/tersedia')),
            
                init() {
                    if (this.selected) {
                        this.query = this.selected.nama || '';
            
                        if (this.selected.tipe === 'aset') {
                            this.loadUnits();
                        }
                    }
                },
            
                hasSelectedBarang() {
                    return this.selected !== null && typeof this.selected === 'object';
                },
            
                selectedNama() {
                    return this.hasSelectedBarang() ? (this.selected.nama || '') : '';
                },
            
                selectedKategori() {
                    return this.hasSelectedBarang() ? (this.selected.kategori || '-') : '-';
                },
            
                selectedMerek() {
                    return this.hasSelectedBarang() ? (this.selected.merek || '-') : '-';
                },
            
                selectedLokasi() {
                    return this.hasSelectedBarang() ? (this.selected.lokasi || '-') : '-';
                },
            
                selectedLabelKondisi() {
                    return this.hasSelectedBarang() ? (this.selected.label_kondisi || '-') : '-';
                },
            
                selectedKondisi() {
                    return this.hasSelectedBarang() ? Number(this.selected.kondisi ?? 0) : 0;
                },
            
                selectedTipeLabel() {
                    if (!this.hasSelectedBarang()) {
                        return 'Stok';
                    }
            
                    return this.selected.tipe === 'aset' ? 'Aset' : 'Stok';
                },
            
                selectedUnitTersedia() {
                    return this.hasSelectedBarang() ? Number(this.selected.unit_tersedia ?? 0) : 0;
                },
            
                selectedUnitRusak() {
                    return this.hasSelectedBarang() ? Number(this.selected.unit_rusak ?? 0) : 0;
                },
            
                selectedQtyTersedia() {
                    return this.hasSelectedBarang() ? Number(this.selected.qty_tersedia ?? 0) : 0;
                },
            
                async cariBarang() {
                    clearTimeout(this.searchTimeout);
            
                    this.searchTimeout = setTimeout(async () => {
                        const keyword = this.query.trim();
            
                        if (keyword.length < 2) {
                            this.results = [];
                            return;
                        }
            
                        try {
                            const response = await fetch(`${this.barangSearchUrl}?q=${encodeURIComponent(keyword)}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
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
                    }, 250);
                },
            
                async pilihBarang(item) {
                    this.selected = item;
                    this.query = item.nama || '';
                    this.results = [];
                    this.units = [];
                    this.selectedUnitIds = [];
            
                    if (item.tipe === 'aset') {
                        await this.loadUnits();
                    }
                },
            
                resetBarang() {
                    this.selected = null;
                    this.query = '';
                    this.results = [];
                    this.units = [];
                    this.selectedUnitIds = [];
                },
            
                async loadUnits() {
                    if (!this.hasSelectedBarang() || this.selected.tipe !== 'aset') {
                        this.units = [];
                        return;
                    }
            
                    this.loadingUnits = true;
            
                    try {
                        const response = await fetch(`${this.unitBaseUrl}/${this.selected.id}?include_rusak=1`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });
            
                        if (!response.ok) {
                            this.units = [];
                            return;
                        }
            
                        const data = await response.json();
                        this.units = Array.isArray(data) ? data : [];
                    } catch (_) {
                        this.units = [];
                    } finally {
                        this.loadingUnits = false;
                    }
                },
            
                butuhLokasi() {
                    return this.alasan === 'pindah_lokasi';
                },
            
                butuhUnitAset() {
                    return this.hasSelectedBarang() && this.selected.tipe === 'aset' && this.alasan !== 'pindah_lokasi';
                },
            
                butuhJumlahStok() {
                    return this.hasSelectedBarang() && this.selected.tipe === 'stok' && this.alasan !== 'pindah_lokasi';
                },
            
                butuhPeringatanKeluar() {
                    return this.alasan === 'dibuang' || this.alasan === 'hibah';
                },
            
                butuhTujuanHibah() {
                    return this.alasan === 'hibah';
                },
            
                butuhLainnya() {
                    return this.alasan === 'lainnya';
                },
            
                lokasiTujuanValue() {
                    if (!this.butuhLokasi()) {
                        return '';
                    }
            
                    return this.lokasiTujuanMode === 'manual' ? '' : (this.lokasiTujuanMode || '');
                }
            }" x-init="init()" @submit="loading = true">
            @csrf

            <div class="space-y-4">
                <div class="relative" @click.outside="results = []">
                    <label for="barang_search" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Cari Barang
                    </label>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                            <i class="bi bi-search text-sm"></i>
                        </span>

                        <input id="barang_search" type="text" x-model="query" @input="cariBarang()" autocomplete="off"
                            placeholder="Ketik minimal 2 huruf..."
                            class="block w-full rounded-md border-gray-300 py-1.5 pl-8 pr-10 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                        <button x-cloak x-show="hasSelectedBarang()" type="button"
                            class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="resetBarang()" title="Reset pilihan barang">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>

                    <input type="hidden" name="barang_id" :value="hasSelectedBarang() ? selected.id : ''">

                    @error('barang_id')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div x-cloak x-show="results.length > 0"
                        class="absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
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
                                            <p
                                                x-text="item.tipe === 'aset'
                                                ? ((item.unit_tersedia ?? 0) + ' unit')
                                                : ((item.qty_tersedia ?? 0) + ' stok')">
                                            </p>
                                        </div>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div x-cloak x-show="hasSelectedBarang()"
                    class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900/30 dark:bg-blue-900/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-blue-800 dark:text-blue-300" x-text="selectedNama()"></p>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">
                                <span x-text="selectedKategori()"></span> ·
                                <span x-text="selectedMerek()"></span> ·
                                <span x-text="selectedLokasi()"></span>
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                <span x-text="selectedTipeLabel()"></span>
                            </span>

                            <span
                                class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                <span x-text="selectedLabelKondisi()"></span>
                                <span class="ml-1" x-text="selectedKondisi() + '%'"></span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 text-xs text-blue-700 dark:text-blue-400">
                        <span x-cloak x-show="hasSelectedBarang() && selected.tipe === 'aset'">
                            Unit tersedia: <strong x-text="selectedUnitTersedia()"></strong> ·
                            Unit rusak: <strong x-text="selectedUnitRusak()"></strong>
                        </span>

                        <span x-cloak x-show="hasSelectedBarang() && selected.tipe === 'stok'">
                            Stok tersedia: <strong x-text="selectedQtyTersedia()"></strong>
                        </span>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Alasan Keluar
                    </label>

                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <button type="button" @click="alasan = 'pindah_lokasi'"
                            :class="alasan === 'pindah_lokasi'
                                ?
                                'border-blue-600 bg-blue-600 text-white' :
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="rounded-md border px-3 py-2 text-xs">
                            Pindah Lokasi
                        </button>

                        <button type="button" @click="alasan = 'dibuang'"
                            :class="alasan === 'dibuang'
                                ?
                                'border-blue-600 bg-blue-600 text-white' :
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="rounded-md border px-3 py-2 text-xs">
                            Dibuang
                        </button>

                        <button type="button" @click="alasan = 'hibah'"
                            :class="alasan === 'hibah'
                                ?
                                'border-blue-600 bg-blue-600 text-white' :
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="rounded-md border px-3 py-2 text-xs">
                            Hibah
                        </button>

                        <button type="button" @click="alasan = 'lainnya'"
                            :class="alasan === 'lainnya'
                                ?
                                'border-blue-600 bg-blue-600 text-white' :
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            class="rounded-md border px-3 py-2 text-xs">
                            Lainnya
                        </button>
                    </div>

                    <input type="hidden" name="alasan_keluar" :value="alasan">

                    @error('alasan_keluar')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-cloak x-show="butuhPeringatanKeluar()"
                    class="rounded-md border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-700 dark:border-amber-900/30 dark:bg-amber-900/10 dark:text-amber-400">
                    Barang akan diproses keluar dari inventaris aktif setelah tindakan ini.
                </div>

                <div x-cloak x-show="butuhLokasi()" class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div>
                        <label for="lokasi_tujuan_selector"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Lokasi Tujuan
                        </label>

                        <select id="lokasi_tujuan_selector" x-model="lokasiTujuanMode" :disabled="!butuhLokasi()"
                            name="_lokasi_tujuan_mode"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Pilih lokasi</option>
                            @foreach ($lokasi as $item)
                                <option value="{{ $item->id }}" @selected((string) old('_lokasi_tujuan_mode', old('lokasi_tujuan_id')) === (string) $item->id)>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                            <option value="manual" @selected(old('_lokasi_tujuan_mode', old('lokasi_tujuan_id')) === 'manual')>Lainnya</option>
                        </select>

                        <input type="hidden" name="lokasi_tujuan_id" :value="lokasiTujuanValue()">

                        @error('lokasi_tujuan_id')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-cloak x-show="lokasiTujuanMode === 'manual'">
                        <label for="lokasi_tujuan_manual"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Lokasi Tujuan Manual
                        </label>

                        <input id="lokasi_tujuan_manual" name="lokasi_tujuan_manual" type="text"
                            value="{{ old('lokasi_tujuan_manual') }}"
                            :disabled="!butuhLokasi() || lokasiTujuanMode !== 'manual'"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                        @error('lokasi_tujuan_manual')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div x-cloak x-show="butuhTujuanHibah()">
                    <label for="sumber_tujuan" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Tujuan Penerima
                    </label>

                    <input id="sumber_tujuan" name="sumber_tujuan" type="text" value="{{ old('sumber_tujuan') }}"
                        placeholder="Contoh: Sekolah Mitra / Donasi / Instansi" :disabled="!butuhTujuanHibah()"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                    @error('sumber_tujuan')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-cloak x-show="butuhUnitAset()">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Pilih Unit Aset
                    </label>

                    <div x-cloak x-show="loadingUnits"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                        Memuat unit...
                    </div>

                    <div x-cloak x-show="!loadingUnits"
                        class="max-h-40 space-y-2 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-900/40">
                        <template x-if="units.length === 0">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada unit yang bisa diproses.
                            </p>
                        </template>

                        <template x-for="unit in units" :key="unit.id">
                            <label
                                class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-white dark:hover:bg-gray-800">
                                <input type="checkbox" name="unit_barang_ids[]" :value="String(unit.id)"
                                    x-model="selectedUnitIds" :disabled="!butuhUnitAset()">
                                <span class="text-sm text-gray-700 dark:text-gray-200">
                                    <span x-text="unit.nomor_unit"></span>
                                    —
                                    <span x-text="unit.kondisi"></span>%
                                    —
                                    <span x-text="unit.label_kondisi"></span>
                                    —
                                    <span x-text="unit.status"></span>
                                </span>
                            </label>
                        </template>
                    </div>

                    @error('unit_barang_ids')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-cloak x-show="butuhJumlahStok()">
                    <label for="jumlah" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Jumlah
                    </label>

                    <input id="jumlah" name="jumlah" type="number" min="1" value="{{ old('jumlah', 1) }}"
                        :disabled="!butuhJumlahStok()"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Maksimal sesuai stok tersedia.
                    </p>

                    @error('jumlah')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-cloak x-show="butuhLainnya()" class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div>
                        <label for="status_akhir" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Status Akhir Barang
                        </label>

                        <select id="status_akhir" name="status_akhir" :disabled="!butuhLainnya()"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Pilih status akhir</option>
                            <option value="tersedia" @selected(old('status_akhir') === 'tersedia')>Tersedia</option>
                            <option value="rusak" @selected(old('status_akhir') === 'rusak')>Rusak</option>
                            <option value="keluar" @selected(old('status_akhir') === 'keluar')>Keluar</option>
                        </select>

                        @error('status_akhir')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="catatan" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Keterangan
                        </label>

                        <textarea id="catatan" name="catatan" rows="3" :disabled="!butuhLainnya()"
                            class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan') }}</textarea>

                        @error('catatan')
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div x-cloak x-show="!butuhLainnya()">
                    <label for="catatan_umum" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Catatan
                    </label>

                    <textarea id="catatan_umum" name="catatan" rows="3" :disabled="butuhLainnya()"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan') }}</textarea>

                    @error('catatan')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_transaksi"
                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Tanggal Keluar <span class="text-red-500">*</span>
                    </label>

                    <input id="tanggal_transaksi" name="tanggal_transaksi" type="date"
                        value="{{ old('tanggal_transaksi', now()->format('Y-m-d')) }}"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                    @error('tanggal_transaksi')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700">
                    <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-md bg-red-500 px-4 py-1.5 text-sm text-white hover:bg-red-600">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span>Catat Barang Keluar</span>
                        </span>

                        <span x-cloak x-show="loading" class="inline-flex items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                            <span>Menyimpan...</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>

        <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 lg:p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Riwayat Barang Keluar
                    </h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Riwayat transaksi barang keluar terbaru.
                    </p>
                </div>

                <form method="GET" action="{{ route('transaksi.keluar') }}" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="dari" value="{{ $filterTanggal['dari'] }}"
                        class="rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <input type="date" name="sampai" value="{{ $filterTanggal['sampai'] }}"
                        class="rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <button type="submit"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Terapkan
                    </button>
                </form>
            </div>

            @if ($riwayatData->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Tanggal
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Barang
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Alasan
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Unit/Jumlah
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Tujuan
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Admin
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatData as $trx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
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

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm capitalize text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ str_replace('_', ' ', $trx->alasan_keluar ?? '-') }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        @if ($trx->unitBarang)
                                            {{ $trx->unitBarang->nomor_unit }}
                                        @else
                                            {{ $trx->jumlah }}
                                        @endif
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $trx->lokasiTujuan?->nama ?? ($trx->lokasi_tujuan_manual ?? ($trx->sumber_tujuan ?? '—')) }}
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
                        {{ $riwayatData->links('components.pagination') }}
                    </div>
                @endif
            @else
                <x-empty-state icon="bi-arrow-up-circle" title="Belum ada riwayat barang keluar"
                    message="Riwayat transaksi barang keluar akan muncul di sini." />
            @endif
        </section>
    </div>
@endsection
