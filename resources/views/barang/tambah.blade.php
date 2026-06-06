@extends('layouts.app')

@section('title', 'Tambah Barang')
@section('meta_description', 'Tambah data barang inventaris Shiro.')

@section('content')
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Tambah Barang
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tambahkan aset atau stok baru ke inventaris.
                </p>
            </div>

            <a href="{{ route('barang.index') }}"
                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('barang.store') }}"
            class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800"
            x-data="{
                tipe: @js(old('tipe', 'aset')),
                merekId: @js(old('merek_id')),
                lokasiId: @js(old('lokasi_id')),
                kondisi: Number(@js(old('kondisi_awal', 100))),
                jumlahUnit: Number(@js(old('jumlah_unit', 1))),
                unitKondisiValues: [],
                editKondisiUnit: {},
                isKondisiMulus: true,
                isiTahun: @js(old('tahun_pengadaan') !== null && old('tahun_pengadaan') !== ''),
                isiSpesifikasi: @js(old('spesifikasi') !== null && old('spesifikasi') !== ''),
                isiCatatan: @js(old('catatan') !== null && old('catatan') !== ''),
                loading: false,
                
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
                }
            }" @submit="loading = true">
            @csrf

            <div class="space-y-4">
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

                    <input type="hidden" name="tipe" :value="tipe">

                    @error('tipe')
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div class="space-y-3">
                        <div>
                            <label for="nama" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Nama Barang <span class="text-red-500">*</span>
                            </label>
                            <input id="nama" name="nama" type="text" value="{{ old('nama') }}" required
                                maxlength="200"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            @error('nama')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="kategori_id"
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select id="kategori_id" name="kategori_id" required
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
                            <label for="merek_id" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Merek
                            </label>
                            <select id="merek_id" name="merek_id" x-model="merekId"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Pilih merek</option>
                                @foreach ($merek as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('merek_id') === (string) $item->id)>
                                        {{ $item->nama }}
                                    </option>
                                @endforeach
                                <option value="lainnya" @selected((string) old('merek_id') === 'lainnya')>Lainnya</option>
                            </select>

                            <div x-cloak x-show="merekId === 'lainnya'" x-transition class="mt-2">
                                <!-- Input otomatis untuk merek baru, menjaga database tetap bersih dengan membuat Merek master data on-the-fly -->
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

                        <div>
                            <label for="lokasi_id" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Lokasi
                            </label>
                            <select id="lokasi_id" name="lokasi_id" x-model="lokasiId"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Pilih lokasi</option>
                                @foreach ($lokasi as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('lokasi_id') === (string) $item->id)>
                                        {{ $item->nama }}
                                    </option>
                                @endforeach
                                <option value="lainnya" @selected((string) old('lokasi_id') === 'lainnya')>Lainnya</option>
                            </select>

                            <div x-cloak x-show="lokasiId === 'lainnya'" x-transition class="mt-2">
                                <!-- Input otomatis untuk lokasi baru, menjaga database tetap bersih dengan membuat Lokasi master data on-the-fly -->
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

                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="isiSpesifikasi"
                                    class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-900">
                                <span>Spesifikasi</span>
                            </label>
                            <textarea x-cloak x-show="isiSpesifikasi" x-transition
                                id="spesifikasi" name="spesifikasi" rows="3"
                                :disabled="!isiSpesifikasi"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('spesifikasi') }}</textarea>
                            @error('spesifikasi')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="isiTahun"
                                    class="rounded border-gray-300 text-blue-600 dark:border-gray-600 dark:bg-gray-900">
                                <span>Tahun Pengadaan</span>
                            </label>
                            <input x-cloak x-show="isiTahun" x-transition
                                id="tahun_pengadaan" name="tahun_pengadaan" type="number" min="2000"
                                max="{{ now()->year + 1 }}" value="{{ old('tahun_pengadaan') }}"
                                :disabled="!isiTahun"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            @error('tahun_pengadaan')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-cloak x-show="tipe === 'aset'" 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="space-y-3">
                            <div>
                                <label for="jumlah_unit"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Jumlah Unit <span class="text-red-500">*</span>
                                </label>
                                <input id="jumlah_unit" name="jumlah_unit" type="number" min="1" max="100"
                                    x-model.number="jumlahUnit"
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('jumlah_unit')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4 space-y-3" x-show="jumlahUnit > 0">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Detail Spesifik Unit (Opsional)</label>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2">Isi jika setiap unit memiliki serial number atau kondisi fisik yang berbeda.</p>
                                
                                <div class="max-h-[300px] overflow-y-auto pr-1 space-y-2">
                                    <template x-for="i in jumlahUnit" :key="i">
                                        <div class="flex flex-col gap-2 rounded-md border border-gray-100 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800/50 sm:flex-row sm:items-center">
                                            <span class="text-[11px] font-medium text-gray-500 w-14">Unit <span x-text="i"></span></span>
                                            
                                            <div class="relative flex-1 w-full sm:w-auto">
                                                <input type="text" :name="'unit_serials[' + (i-1) + ']'" placeholder="Scan/Ketik SN..."
                                                    @keydown.enter.prevent="$el.blur()"
                                                    class="block w-full rounded-lg border-gray-200 bg-white px-2 py-1 pr-7 text-xs focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400">
                                                    <i class="bi bi-upc-scan text-lg"></i>
                                                </div>
                                            </div>
                                            
                                            <div class="flex w-full sm:w-56 items-center gap-2">
                                                <div x-cloak x-show="!editKondisiUnit[i-1]" class="flex w-full items-center justify-between rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400 font-medium">
                                                    <span><i class="bi bi-stars"></i> 100% Mulus</span>
                                                    <button type="button" @click="editKondisiUnit[i-1] = true" class="text-emerald-600/70 hover:text-emerald-800 dark:text-emerald-400/70 dark:hover:text-emerald-300" title="Ubah Kondisi">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <input type="hidden" :name="'unit_kondisis[' + (i-1) + ']'" value="100" :disabled="editKondisiUnit[i-1]">
                                                </div>

                                                <div x-cloak x-show="editKondisiUnit[i-1]" class="flex w-full items-center gap-2">
                                                    <input type="range" :name="'unit_kondisis[' + (i-1) + ']'" min="0" max="100" 
                                                        :value="unitKondisiValues[i-1] !== undefined && unitKondisiValues[i-1] !== '' ? unitKondisiValues[i-1] : 100"
                                                        @input="unitKondisiValues[i-1] = $event.target.value"
                                                        :disabled="!editKondisiUnit[i-1]"
                                                        class="w-full accent-blue-600">
                                                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-300 w-8" 
                                                        x-text="(unitKondisiValues[i-1] !== undefined && unitKondisiValues[i-1] !== '' ? unitKondisiValues[i-1] : 100) + '%'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div x-cloak x-show="tipe === 'stok'" 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="space-y-3">
                            <div>
                                <label for="qty_total"
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Jumlah Total <span class="text-red-500">*</span>
                                </label>
                                <input id="qty_total" name="qty_total" type="number" min="1"
                                    value="{{ old('qty_total', 1) }}"
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @error('qty_total')
                                    <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div x-cloak x-show="tipe === 'stok'"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="isKondisiMulus"
                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900">
                                <span>Kondisi 100% (Barang Baru / Mulus)</span>
                            </label>

                            <input type="hidden" name="kondisi_awal" value="100" :disabled="!isKondisiMulus">

                            <div x-cloak x-show="!isKondisiMulus" x-transition class="mt-3">
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <label for="kondisi_awal"
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Kondisi Awal % <span class="text-red-500">*</span>
                                    </label>

                                    <span class="text-sm font-semibold" :class="warnaKondisiText">
                                        <span x-text="labelKondisi"></span> <span x-text="kondisi + '%'"></span>
                                    </span>
                                </div>

                                <input id="kondisi_awal" name="kondisi_awal" type="range" min="0" max="100"
                                    x-model="kondisi" :style="warnaSlider" :disabled="isKondisiMulus" class="block w-full">

                                <div
                                    class="mt-2 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                                    <span>Rusak Parah 0%</span>
                                    <span>Rusak 35%</span>
                                    <span>Lumayan 60%</span>
                                    <span>Baik 80%</span>
                                </div>

                                <div x-cloak x-show="kondisi <= 34" x-transition
                                    class="mt-2 rounded-md border border-red-200 bg-red-50 px-2.5 py-2 text-[11px] text-red-600 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400">
                                    Kondisi ≤34% akan otomatis membuat status unit menjadi <strong>rusak</strong>.
                                </div>
                            </div>

                            @error('kondisi_awal')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="isiCatatan"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900">
                                <span>Catatan</span>
                            </label>
                            <textarea x-cloak x-show="isiCatatan" x-transition
                                id="catatan" name="catatan" rows="3"
                                :disabled="!isiCatatan"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan') }}</textarea>
                            @error('catatan')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                    <a href="{{ route('barang.index') }}"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Batal
                    </a>

                    <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-1.5 text-sm text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                        <span x-show="!loading">Simpan</span>
                        <span x-show="loading" class="inline-flex items-center gap-2">
                            <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                            <span>Menyimpan...</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
