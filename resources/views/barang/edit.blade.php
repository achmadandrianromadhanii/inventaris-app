@extends('layouts.app')

@section('title', 'Edit Barang')
@section('meta_description', 'Edit data barang inventaris Shiro.')

@section('content')

    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Edit Barang
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Perbarui informasi barang tanpa mengubah riwayat inventaris.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('barang.show', $barang) }}"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Kembali
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('barang.update', $barang) }}"
            class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800"
            x-data="{
                tipe: @js(old('tipe', $barang->tipe)),
                merekId: @js(old('merek_id', $barang->merek_id ? (string) $barang->merek_id : '')),
                lokasiId: @js(old('lokasi_id', $barang->lokasi_id ? (string) $barang->lokasi_id : '')),
                isiCatatan: @js(old('catatan', $barang->catatan) !== null && old('catatan', $barang->catatan) !== ''),
                loading: false,
            }" @submit="loading = true">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Tipe Barang
                    </label>

                    <div class="inline-flex w-fit gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                        <button type="button" disabled
                            :class="tipe === 'aset'
                                ?
                                'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                'text-gray-500 dark:text-gray-300'"
                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-md px-3 py-2 text-xs opacity-90">
                            <i class="bi bi-cpu"></i>
                            <span>Aset</span>
                        </button>

                        <button type="button" disabled
                            :class="tipe === 'stok'
                                ?
                                'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                                'text-gray-500 dark:text-gray-300'"
                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-md px-3 py-2 text-xs opacity-90">
                            <i class="bi bi-stack"></i>
                            <span>Stok</span>
                        </button>
                    </div>

                    <input type="hidden" name="tipe" value="{{ $barang->tipe }}">

                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Tipe barang dikunci agar struktur data aset dan stok tetap konsisten.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div class="space-y-3">
                        <div>
                            <label for="nama" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Nama Barang <span class="text-red-500">*</span>
                            </label>
                            <input id="nama" name="nama" type="text" value="{{ old('nama', $barang->nama) }}"
                                required maxlength="200"
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
                                    <option value="{{ $item->id }}" @selected((string) old('kategori_id', $barang->kategori_id) === (string) $item->id)>
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
                                    <option value="{{ $item->id }}" @selected((string) old('merek_id', $barang->merek_id) === (string) $item->id)>
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
                                    <option value="{{ $item->id }}" @selected((string) old('lokasi_id', $barang->lokasi_id) === (string) $item->id)>
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
                            <label for="spesifikasi"
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Spesifikasi
                            </label>
                            <textarea id="spesifikasi" name="spesifikasi" rows="3"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('spesifikasi', $barang->spesifikasi) }}</textarea>
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
                                max="{{ now()->year + 1 }}" value="{{ old('tahun_pengadaan', $barang->tahun_pengadaan) }}"
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            @error('tahun_pengadaan')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($barang->tipe === 'aset')
                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-200">
                                    Informasi Unit Aset
                                </p>

                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <p>
                                        Total Unit:
                                        <span class="font-medium text-gray-700 dark:text-gray-200">
                                            {{ $barang->unitBarang()->count() }}
                                        </span>
                                    </p>
                                    <p>
                                        Kelola Unit:
                                        <a href="{{ route('barang.unit', $barang) }}"
                                            class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                            buka halaman unit
                                        </a>
                                    </p>
                                </div>

                                <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    Penambahan, perubahan kondisi, dan status per unit dilakukan di halaman kelola unit.
                                </p>
                            </div>

                            <input type="hidden" name="kondisi_awal" value="{{ old('kondisi_awal', 100) }}">
                        @endif

                        @if ($barang->tipe === 'stok')
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-200">
                                    Informasi Jumlah Stok
                                </p>

                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <p>
                                        Total Stok:
                                        <span class="font-medium text-gray-700 dark:text-gray-200">
                                            {{ (int) $barang->qty_total }}
                                        </span>
                                    </p>
                                    <p>
                                        Tersedia:
                                        <span class="font-medium text-gray-700 dark:text-gray-200">
                                            {{ (int) $barang->qty_tersedia }}
                                        </span>
                                    </p>
                                    <p>
                                        Dipinjam:
                                        <span class="font-medium text-gray-700 dark:text-gray-200">
                                            {{ (int) $barang->qty_dipinjam }}
                                        </span>
                                    </p>
                                    <p>
                                        Rusak:
                                        <span class="font-medium text-gray-700 dark:text-gray-200">
                                            {{ (int) $barang->qty_rusak }}
                                        </span>
                                    </p>
                                </div>

                                <div class="mt-3 rounded-md bg-amber-50 p-2 border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800/50 text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Penyesuaian Stok Terkunci.</strong>
                                    Untuk menjaga riwayat dan integritas audit, penambahan atau pengurangan jumlah stok hanya bisa dilakukan melalui menu <strong>Transaksi (Barang Masuk / Barang Keluar)</strong>.
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" x-model="isiCatatan"
                                    class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-900">
                                <span>Catatan</span>
                            </label>
                            <textarea x-cloak x-show="isiCatatan" x-transition
                                id="catatan" name="catatan" rows="3"
                                :disabled="!isiCatatan"
                                class="mt-1 block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('catatan', $barang->catatan) }}</textarea>
                            @error('catatan')
                                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                    <a href="{{ route('barang.show', $barang) }}"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Batal
                    </a>

                    <button type="submit" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-1.5 text-sm text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                        <span x-show="!loading">Perbarui</span>
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
