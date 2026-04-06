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
            class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 lg:p-4"
            x-data="{
                modeBarang: @js(old('mode_barang', 'baru')),
                tipe: @js(old('tipe', 'aset')),
                merekId: @js(old('merek_id')),
                lokasiId: @js(old('lokasi_id')),
                kondisi: Number(@js(old('kondisi_saat_itu', 100))),
                loading: false,
            
                query: '',
                results: [],
                selected: @js($barangTerpilih ?? null),
                searchTimeout: null,
            
                init() {
                    if (this.selected) {
                        this.query = this.selected.nama || '';
                        this.tipe = this.selected.tipe || this.tipe;
                    }
                },
            
                setMode(mode) {
                    this.modeBarang = mode;
                    this.results = [];
            
                    if (mode === 'baru') {
                        this.selected = null;
                        this.query = '';
                        this.tipe = @js(old('tipe', 'aset'));
                    }
                },
            
                get labelKondisi() {
                    if (this.kondisi >= 80) return 'Baik';
                    if (this.kondisi >= 60) return 'Lumayan';
                    if (this.kondisi >= 35) return 'Rusak';
                    return 'Rusak Parah';
                },
            
                get warnaKondisiText() {
                    if (this.kondisi >= 80) return 'text-emerald-600';
                    if (this.kondisi >= 60) return 'text-blue-600';
                    if (this.kondisi >= 35) return 'text-amber-600';
                    return 'text-red-600';
                },
            
                get warnaSlider() {
                    if (this.kondisi >= 80) return 'accent-color: #059669';
                    if (this.kondisi >= 60) return 'accent-color: #2563eb';
                    if (this.kondisi >= 35) return 'accent-color: #f59e0b';
                    return 'accent-color: #ef4444';
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
                            const response = await fetch(`{{ route('api.barang.search') }}?q=${encodeURIComponent(keyword)}`, {
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
            
                pilihBarang(item) {
                    this.selected = item;
                    this.query = item.nama || '';
                    this.results = [];
                    this.tipe = item.tipe || this.tipe;
                },
            
                resetBarang() {
                    this.selected = null;
                    this.query = '';
                    this.results = [];
                },
            }" x-init="init()" @submit="loading = true">
            @csrf

            <div class="space-y-4">
                <div>
                    <div class="inline-flex w-fit gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                        <button type="button" @click="setMode('baru')"
                            :class="modeBarang === 'baru'
                                ?
                                'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                'text-gray-500 dark:text-gray-300'"
                            class="rounded-md px-3 py-2 text-xs">
                            Barang Baru
                        </button>

                        <button type="button" @click="setMode('existing')"
                            :class="modeBarang === 'existing'
                                ?
                                'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                'text-gray-500 dark:text-gray-300'"
                            class="rounded-md px-3 py-2 text-xs">
                            Barang Sudah Ada
                        </button>
                    </div>

                    <input type="hidden" name="mode_barang" :value="modeBarang">

                    @error('mode_barang')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-cloak x-show="modeBarang === 'baru'" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Tipe Barang
                        </label>

                        <div class="inline-flex w-fit gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                            <button type="button" @click="tipe = 'aset'"
                                :class="tipe === 'aset'
                                    ?
                                    'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                    'text-gray-500 dark:text-gray-300'"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs">
                                <i class="bi bi-cpu"></i>
                                <span>Aset</span>
                            </button>

                            <button type="button" @click="tipe = 'stok'"
                                :class="tipe === 'stok'
                                    ?
                                    'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                    'text-gray-500 dark:text-gray-300'"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-xs">
                                <i class="bi bi-stack"></i>
                                <span>Stok</span>
                            </button>
                        </div>

                        <input type="hidden" name="tipe" :value="tipe" :disabled="modeBarang !== 'baru'">
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div>
                                <label for="nama"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Nama Barang <span class="text-red-500">*</span>
                                </label>
                                <input id="nama" name="nama" type="text" value="{{ old('nama') }}"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('nama')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="kategori_id"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select id="kategori_id" name="kategori_id" :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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

                            <div>
                                <label for="merek_id"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Merek
                                </label>
                                <select id="merek_id" name="merek_id" x-model="merekId"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">Pilih merek</option>
                                    @foreach ($merek as $item)
                                        <option value="{{ $item->id }}" @selected((string) old('merek_id') === (string) $item->id)>
                                            {{ $item->nama }}
                                        </option>
                                    @endforeach
                                    <option value="lainnya" @selected(old('merek_id') === 'lainnya')>Lainnya</option>
                                </select>

                                <div x-cloak x-show="merekId === 'lainnya'" class="mt-2">
                                    <input name="merek_manual" type="text" value="{{ old('merek_manual') }}"
                                        placeholder="Masukkan merek manual"
                                        :disabled="modeBarang !== 'baru' || merekId !== 'lainnya'"
                                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                </div>

                                @error('merek_id')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                @error('merek_manual')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="lokasi_id"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Lokasi
                                </label>
                                <select id="lokasi_id" name="lokasi_id" x-model="lokasiId"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">Pilih lokasi</option>
                                    @foreach ($lokasi as $item)
                                        <option value="{{ $item->id }}" @selected((string) old('lokasi_id') === (string) $item->id)>
                                            {{ $item->nama }}
                                        </option>
                                    @endforeach
                                    <option value="lainnya" @selected(old('lokasi_id') === 'lainnya')>Lainnya</option>
                                </select>

                                <div x-cloak x-show="lokasiId === 'lainnya'" class="mt-2">
                                    <input name="lokasi_manual" type="text" value="{{ old('lokasi_manual') }}"
                                        placeholder="Masukkan lokasi manual"
                                        :disabled="modeBarang !== 'baru' || lokasiId !== 'lainnya'"
                                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                </div>

                                @error('lokasi_id')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                @error('lokasi_manual')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="spesifikasi"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Spesifikasi
                                </label>
                                <textarea id="spesifikasi" name="spesifikasi" rows="3" :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('spesifikasi') }}</textarea>
                                @error('spesifikasi')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="tahun_pengadaan"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Tahun Pengadaan
                                </label>
                                <input id="tahun_pengadaan" name="tahun_pengadaan" type="number" min="2000"
                                    max="{{ now()->year + 1 }}" value="{{ old('tahun_pengadaan') }}"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('tahun_pengadaan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="jumlah_masuk_baru"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    <span x-text="tipe === 'aset' ? 'Jumlah Unit' : 'Jumlah Total'"></span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input id="jumlah_masuk_baru" name="jumlah_masuk" type="number" min="1"
                                    max="1000" value="{{ old('jumlah_masuk', 1) }}"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <p x-cloak x-show="tipe === 'aset'"
                                    class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Nomor unit akan dibuat otomatis berdasarkan kategori.
                                </p>
                                @error('jumlah_masuk')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-cloak x-show="tipe === 'aset'">
                                <label for="serial_number_list_baru"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Serial Number
                                </label>
                                <textarea id="serial_number_list_baru" name="serial_number_list" rows="3"
                                    placeholder="Satu serial number per baris" :disabled="modeBarang !== 'baru' || tipe !== 'aset'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('serial_number_list') }}</textarea>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Opsional. Untuk aset, satu baris mewakili satu unit.
                                </p>
                            </div>

                            <div>
                                <label for="sumber_tujuan_baru"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Sumber
                                </label>
                                <input id="sumber_tujuan_baru" name="sumber_tujuan" type="text"
                                    value="{{ old('sumber_tujuan') }}" maxlength="200"
                                    placeholder="Contoh: Pengadaan Sekolah / Donasi / Pembelian"
                                    :disabled="modeBarang !== 'baru'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('sumber_tujuan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div x-cloak x-show="modeBarang === 'existing'" class="space-y-4">
                    <div class="relative">
                        <label for="barang_search"
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            Cari Barang
                        </label>

                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                <i class="bi bi-search text-sm"></i>
                            </span>

                            <input id="barang_search" type="text" x-model="query" @input="cariBarang()"
                                autocomplete="off" placeholder="Ketik minimal 2 huruf..."
                                class="block w-full rounded-md border-gray-300 py-1.5 pl-8 pr-10 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">

                            <button x-cloak x-show="selected" type="button"
                                class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                @click="resetBarang()" title="Reset pilihan barang">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>

                        <input type="hidden" name="barang_id" :value="selected ? selected.id : ''"
                            :disabled="modeBarang !== 'existing'">

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

                    <div x-cloak x-show="selected"
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

                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                    <span x-text="selected ? (selected.tipe === 'aset' ? 'Aset' : 'Stok') : '-'"></span>
                                </span>

                                <span
                                    class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[10px] font-medium text-blue-700 ring-1 ring-blue-600/20 dark:bg-gray-800 dark:text-blue-300">
                                    <span x-text="selected ? selected.label_kondisi : ''"></span>
                                    <span class="ml-1" x-text="selected ? selected.kondisi + '%' : ''"></span>
                                </span>
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

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div>
                                <label for="jumlah_masuk_existing"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Jumlah Masuk <span class="text-red-500">*</span>
                                </label>
                                <input id="jumlah_masuk_existing" name="jumlah_masuk" type="number" min="1"
                                    max="1000" value="{{ old('jumlah_masuk', 1) }}"
                                    :disabled="modeBarang !== 'existing'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('jumlah_masuk')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-cloak x-show="selected && selected.tipe === 'aset'">
                                <label for="serial_number_list_existing"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Serial Number Unit Baru
                                </label>
                                <textarea id="serial_number_list_existing" name="serial_number_list" rows="3"
                                    placeholder="Satu serial number per baris"
                                    :disabled="modeBarang !== 'existing' || !selected || selected.tipe !== 'aset'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('serial_number_list') }}</textarea>
                            </div>

                            <div>
                                <label for="sumber_tujuan_existing"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Sumber
                                </label>
                                <input id="sumber_tujuan_existing" name="sumber_tujuan" type="text"
                                    value="{{ old('sumber_tujuan') }}" maxlength="200"
                                    placeholder="Contoh: Pengadaan Sekolah / Donasi / Pembelian"
                                    :disabled="modeBarang !== 'existing'"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('sumber_tujuan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="tanggal_transaksi"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Tanggal Masuk <span class="text-red-500">*</span>
                                </label>
                                <input id="tanggal_transaksi" name="tanggal_transaksi" type="date"
                                    value="{{ old('tanggal_transaksi', now()->format('Y-m-d')) }}"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('tanggal_transaksi')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <label for="kondisi_saat_itu"
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Kondisi Saat Itu % <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-sm font-semibold" :class="warnaKondisiText">
                                        <span x-text="labelKondisi"></span>
                                        <span x-text="kondisi + '%'"></span>
                                    </span>
                                </div>

                                <input id="kondisi_saat_itu" name="kondisi_saat_itu" type="range" min="0"
                                    max="100" x-model="kondisi" :style="warnaSlider" class="block w-full">

                                <div
                                    class="mt-2 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                                    <span>Rusak Parah 0%</span>
                                    <span>Rusak 35%</span>
                                    <span>Lumayan 60%</span>
                                    <span>Baik 80%</span>
                                </div>

                                @error('kondisi_saat_itu')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="catatan"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Catatan
                                </label>
                                <textarea id="catatan" name="catatan" rows="3"
                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan') }}</textarea>
                                @error('catatan')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 pt-3 dark:border-gray-700">
                    <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-1.5 text-sm text-white hover:bg-emerald-700">
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
        </form>

        <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 lg:p-4">
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
                        class="rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <input type="date" name="sampai" value="{{ $filterTanggal['sampai'] }}"
                        class="rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <button type="submit"
                        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Terapkan
                    </button>
                </form>
            </div>

            @if ($riwayatMasukData->count() > 0)
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
                                    Tipe
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Jumlah
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Kondisi
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Sumber
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Admin
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatMasukData as $trx)
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
