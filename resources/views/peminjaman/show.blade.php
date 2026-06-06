@extends('layouts.app')

@section('title', 'Detail Peminjaman')
@section('meta_description', 'Detail data peminjaman inventaris Shiro.')

@section('content')
    @php
        $aksiKembalikan = request('aksi') === 'kembalikan';
        $jumlahItemAktif = $peminjaman->detailPeminjaman->where('status_item', 'dipinjam')->count();

        $tanggalPinjamLabel = $peminjaman->tanggal_pinjam
            ? \Illuminate\Support\Carbon::parse($peminjaman->tanggal_pinjam)->format('d M Y')
            : '—';

        $waktuPinjamLabel = $peminjaman->waktu_pinjam
            ? \Illuminate\Support\Carbon::parse($peminjaman->waktu_pinjam)->format('H:i')
            : '—';
    @endphp

    <div class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Detail Peminjaman
                </h1>
                <p class="mt-1 font-mono text-sm text-gray-500 dark:text-gray-400">
                    #{{ $peminjaman->id }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <x-status-badge :status="$peminjaman->status" />

                <a href="{{ route('peminjaman.index') }}"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Kembali
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->nama_peminjam }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Kelas</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->kelas?->nama ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Jurusan</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->jurusan?->nama ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Mata Pelajaran</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->mata_pelajaran ?: '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal Pinjam</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $tanggalPinjamLabel }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu Pinjam</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $waktuPinjamLabel }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">No. HP</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->no_hp ?: '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Admin</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $peminjaman->pengguna?->nama ?? 'Form Siswa Publik' }}
                    </p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Item Aktif</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $jumlahItemAktif }} item
                    </p>
                </div>
            </div>

            @if ($peminjaman->catatan)
                <div class="mt-4">
                    <p class="text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Catatan</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                        {{ $peminjaman->catatan }}
                    </p>
                </div>
            @endif
        </section>

        <section id="area-pengembalian"
            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    Daftar Item
                </h2>

                @if ($jumlahItemAktif > 0)
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400">
                        {{ $jumlahItemAktif }} item belum kembali
                    </span>
                @endif
            </div>

            @if ($aksiKembalikan && $jumlahItemAktif > 0)
                <div
                    class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-900/10 dark:text-emerald-400">
                    Pilih tombol <strong>Kembalikan</strong> pada item yang masih berstatus dipinjam untuk memproses
                    pengembalian dari admin.
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Barang
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Unit/Qty
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Kondisi Awal
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Status Item
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Kondisi Kembali
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Waktu
                            </th>
                            <th scope="col"
                                class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($peminjaman->detailPeminjaman as $detail)
                            @php
                                $kondisiAwal = is_numeric($detail->kondisi_awal) ? (int) $detail->kondisi_awal : null;
                                $kondisiKembali = is_numeric($detail->kondisi_kembali)
                                    ? (int) $detail->kondisi_kembali
                                    : null;

                                $selisihKondisi =
                                    !is_null($kondisiAwal) && !is_null($kondisiKembali)
                                        ? $kondisiAwal - $kondisiKembali
                                        : 0;

                                $highlightKondisi =
                                    $selisihKondisi >= 10 || (!is_null($kondisiKembali) && $kondisiKembali <= 34);
                                $highlightPengembalian = $aksiKembalikan && $detail->status_item === 'dipinjam';

                                $rowClass = $highlightPengembalian
                                    ? 'bg-emerald-50 dark:bg-emerald-900/10'
                                    : ($highlightKondisi
                                        ? 'bg-amber-50 dark:bg-amber-900/10'
                                        : '');

                                $modalShouldOpen =
                                    old('detail_id') && (string) old('detail_id') === (string) $detail->id;

                                $initialKondisi = $modalShouldOpen
                                    ? (int) old('kondisi_kembali', $kondisiAwal ?? 100)
                                    : $kondisiAwal ?? 100;

                                $waktuKembaliLabel = $detail->waktu_kembali
                                    ? \Illuminate\Support\Carbon::parse($detail->waktu_kembali)->format('d M Y H:i')
                                    : '—';

                                $sliderId = 'kondisi-kembali-' . $detail->id;
                                $catatanId = 'catatan-kembali-' . $detail->id;
                            @endphp

                            <tr class="{{ $rowClass }} group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                <td
                                    class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                    {{ $detail->barang?->nama ?? '-' }}
                                </td>

                                <td
                                    class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    {{ $detail->unitBarang?->nomor_unit ?? 'Qty ' . $detail->jumlah }}
                                </td>

                                <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                    @if (!is_null($kondisiAwal))
                                        <x-kondisi-badge :kondisi="$kondisiAwal" :show-value="true" />
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>

                                <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                    <x-status-badge :status="$detail->status_item" />
                                </td>

                                <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                    @if (!is_null($kondisiKembali))
                                        <x-kondisi-badge :kondisi="$kondisiKembali" :show-value="true" />
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>

                                <td
                                    class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    {{ $waktuKembaliLabel }}
                                </td>

                                <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-700">
                                    @if ($detail->status_item === 'dipinjam')
                                        <div x-data="{
                                            open: @js($modalShouldOpen),
                                            kondisi: {{ $initialKondisi }},
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
                                        }">
                                            <button type="button"
                                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 shadow-md shadow-emerald-500/30"
                                                @click="open = true">
                                                <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                                                <span>Kembalikan</span>
                                            </button>

                                            <x-modal1 name="open" title="Proses Pengembalian" max-width="max-w-lg">
                                                <form method="POST"
                                                    action="{{ route('peminjaman.kembalikan', $peminjaman) }}"
                                                    class="space-y-3" @submit="loading = true">
                                                    @csrf
                                                    @method('PATCH')

                                                    <input type="hidden" name="detail_id" value="{{ $detail->id }}">

                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                            {{ $detail->barang?->nama ?? '-' }}
                                                        </p>
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $detail->unitBarang?->nomor_unit ?? 'Qty ' . $detail->jumlah }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <div class="mb-1 flex items-center justify-between gap-3">
                                                            <label for="{{ $sliderId }}"
                                                                class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                                                Kondisi Saat Kembali
                                                            </label>

                                                            <span class="text-sm font-semibold" :class="warnaKondisiText">
                                                                <span x-text="labelKondisi"></span>
                                                                <span x-text="kondisi + '%'"></span>
                                                            </span>
                                                        </div>

                                                        <input id="{{ $sliderId }}" name="kondisi_kembali"
                                                            type="range" min="0" max="100" x-model="kondisi"
                                                            :style="warnaSlider" class="block w-full">

                                                        <div
                                                            class="mt-2 flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                                                            <span>Rusak Parah 0%</span>
                                                            <span>Rusak 35%</span>
                                                            <span>Lumayan 60%</span>
                                                            <span>Baik 80%</span>
                                                        </div>

                                                        <div x-cloak x-show="kondisi <= 34" x-transition
                                                            class="mt-2 text-xs text-red-600 dark:text-red-400">
                                                            Kondisi ≤34% akan otomatis mengubah status unit menjadi rusak.
                                                        </div>

                                                        @if ($modalShouldOpen)
                                                            @error('kondisi_kembali')
                                                                <p class="mt-2 text-[11px] text-red-600 dark:text-red-400">
                                                                    {{ $message }}
                                                                </p>
                                                            @enderror
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <label for="{{ $catatanId }}"
                                                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                                            Catatan Kembali
                                                        </label>

                                                        <textarea id="{{ $catatanId }}" name="catatan_kembali" rows="3"
                                                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $modalShouldOpen ? old('catatan_kembali') : '' }}</textarea>

                                                        @if ($modalShouldOpen)
                                                            @error('catatan_kembali')
                                                                <p class="mt-2 text-[11px] text-red-600 dark:text-red-400">
                                                                    {{ $message }}
                                                                </p>
                                                            @enderror
                                                        @endif
                                                    </div>

                                                    <div class="flex justify-end gap-2">
                                                        <button type="button"
                                                            class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                                            @click="open = false" :disabled="loading">
                                                            Batal
                                                        </button>

                                                        <button type="submit" :disabled="loading"
                                                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
                                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-700 shadow-md shadow-emerald-500/30">
                                                            <span x-show="!loading">Simpan Pengembalian</span>
                                                            <span x-show="loading" class="inline-flex items-center gap-2">
                                                                <i class="bi bi-arrow-repeat animate-spin-smooth"></i>
                                                                <span>Menyimpan...</span>
                                                            </span>
                                                        </button>
                                                    </div>
                                                </form>
                                            </x-modal1>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
