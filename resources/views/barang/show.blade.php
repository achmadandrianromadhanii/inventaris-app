@extends('layouts.app')

@section('title', $barang->nama)
@section('meta_description', 'Detail barang inventaris Shiro.')

@section('content')
    @php
        $isAset = $barang->tipe === 'aset';

        $badgeTipeClass = $isAset
            ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400'
            : 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-900/20 dark:text-violet-400';

        $badgeAktifClass = $barang->aktif
            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400'
            : 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-300';

        $kondisiBarang = $barang->kondisi_efektif;

        $progressColor = match (true) {
            $kondisiBarang >= 80 => 'bg-emerald-500',
            $kondisiBarang >= 60 => 'bg-blue-500',
            $kondisiBarang >= 35 => 'bg-amber-500',
            default => 'bg-red-500',
        };

        $persenTersedia =
            !$isAset && (int) $barang->qty_total > 0
                ? (int) round(((int) $barang->qty_tersedia / (int) $barang->qty_total) * 100)
                : 0;
    @endphp

    <div class="space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ $barang->nama }}
                    </h1>

                    <span
                        class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $badgeTipeClass }}">
                        {{ $isAset ? 'Aset' : 'Stok' }}
                    </span>

                    <span
                        class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $badgeAktifClass }}">
                        {{ $barang->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $barang->kategori?->nama }} · {{ $barang->label_merek }} · {{ $barang->label_lokasi }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('barang.edit', $barang) }}"
                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                    Edit
                </a>

                <a href="{{ route('barang.index') }}"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Kembali
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                        Info Umum
                    </p>
                </div>

                <dl class="space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Kategori</dt>
                        <dd class="col-span-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $barang->kategori?->nama ?? '-' }}
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Merek</dt>
                        <dd class="col-span-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $barang->label_merek }}
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Lokasi</dt>
                        <dd class="col-span-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $barang->label_lokasi }}
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Tahun</dt>
                        <dd class="col-span-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $barang->tahun_pengadaan ?: ($barang->created_at ? $barang->created_at->format('Y') . ' (Otomatis)' : '-') }}
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Tipe</dt>
                        <dd class="col-span-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $isAset ? 'Aset' : 'Stok' }}
                        </dd>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Catatan</dt>
                        <dd class="col-span-2 text-sm text-gray-700 dark:text-gray-200">
                            {{ $barang->catatan ?: '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <p class="mb-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                        Spesifikasi
                    </p>

                    <div class="rounded-lg bg-gray-50 p-2.5 text-sm text-gray-700 dark:bg-gray-700/50 dark:text-gray-200">
                        {{ $barang->spesifikasi ?: 'Tidak ada spesifikasi.' }}
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                        Kondisi & Unit
                    </p>

                    @if ($isAset)
                        <a href="{{ route('barang.unit', $barang) }}"
                            class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            Kelola Unit →
                        </a>
                    @endif
                </div>

                @if ($isAset)
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Total Unit</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $barang->unit_barang_count }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Tersedia</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $barang->unit_tersedia_count }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Dipinjam</p>
                            <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">
                                {{ $barang->unit_dipinjam_count }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Rusak</p>
                            <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">
                                {{ $barang->unit_rusak_count }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        No Unit
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Serial
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Kondisi
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Status
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($barang->unitBarang->take(5) as $unit)
                                    @php
                                        $unitProgressColor = match (true) {
                                            $unit->kondisi >= 80 => 'bg-emerald-500',
                                            $unit->kondisi >= 60 => 'bg-blue-500',
                                            $unit->kondisi >= 35 => 'bg-amber-500',
                                            default => 'bg-red-500',
                                        };
                                    @endphp

                                    <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                            {{ $unit->nomor_unit }}
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $unit->serial_number ?: '—' }}
                                        </td>

                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                                    {{ $unit->kondisi }}%
                                                </span>

                                                <div class="w-full max-w-[72px]">
                                                    <div
                                                        class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                        <div class="h-1.5 rounded-full {{ $unitProgressColor }}"
                                                            style="width: {{ $unit->kondisi }}%; transition: width 0.7s ease-out;">
                                                        </div>
                                                    </div>
                                                </div>

                                                <x-kondisi-badge :kondisi="$unit->kondisi" />
                                            </div>
                                        </td>

                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <x-status-badge :status="$unit->status" />
                                        </td>

                                        <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-700">
                                            <a href="{{ route('barang.unit', $barang) }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-sm text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                                title="Kelola unit" aria-label="Kelola unit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4">
                                            <x-empty-state icon="bi-cpu" title="Belum ada unit"
                                                message="Unit aset belum tersedia untuk barang ini." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if ($barang->unitBarang->count() > 5)
                            <div class="border-t border-gray-100 bg-indigo-50/50 p-3 text-center dark:border-gray-700 dark:bg-indigo-900/10">
                                <a href="{{ route('barang.unit', $barang) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                                    Menampilkan 5 dari {{ $barang->unitBarang->count() }} unit. Lihat {{ $barang->unitBarang->count() - 5 }} unit lainnya &rarr;
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Tersedia</span>
                                <span>{{ (int) $barang->qty_tersedia }} / {{ (int) $barang->qty_total }}</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-blue-500"
                                    style="width: {{ $persenTersedia }}%; transition: width 0.7s ease-out;"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Total</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ (int) $barang->qty_total }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Tersedia</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ (int) $barang->qty_tersedia }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Dipinjam</p>
                                <p class="mt-1 text-sm font-semibold text-amber-600 dark:text-amber-400">
                                    {{ (int) $barang->qty_dipinjam }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Rusak</p>
                                <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">
                                    {{ (int) $barang->qty_rusak }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Keluar</p>
                                <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    {{ (int) $barang->qty_keluar }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                Kondisi Stok
                            </p>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $barang->kondisi_efektif }}%
                                </span>

                                <div class="w-full max-w-[72px]">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full {{ $progressColor }}"
                                            style="width: {{ $barang->kondisi_efektif }}%; transition: width 0.7s ease-out;">
                                        </div>
                                    </div>
                                </div>

                                <x-kondisi-badge :kondisi="$barang->kondisi_efektif" />
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            x-data="{ tab: 'transaksi' }">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-bold text-slate-800 dark:text-gray-100">
                    Riwayat
                </p>

                <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                    <button type="button" @click="tab = 'transaksi'"
                        :class="tab === 'transaksi'
                            ?
                            'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                            'text-gray-500 dark:text-gray-300'"
                        class="rounded-md px-3 py-1.5 text-xs">
                        Transaksi
                    </button>

                    <button type="button" @click="tab = 'peminjaman'"
                        :class="tab === 'peminjaman'
                            ?
                            'bg-white text-gray-800 shadow dark:bg-gray-800 dark:text-gray-100' :
                            'text-gray-500 dark:text-gray-300'"
                        class="rounded-md px-3 py-1.5 text-xs">
                        Peminjaman
                    </button>
                </div>
            </div>

            <div x-cloak x-show="tab === 'transaksi'" x-transition>
                @if ($riwayatTransaksi->isEmpty())
                    <x-empty-state icon="bi-arrow-left-right" title="Belum ada transaksi"
                        message="Riwayat transaksi barang akan muncul di sini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Tanggal
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Jenis
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Jumlah
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Keterangan
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Admin
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayatTransaksi as $trx)
                                    @php
                                        $badgeJenis =
                                            $trx->jenis === 'masuk'
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400'
                                                : 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-300';

                                        $keteranganTransaksi =
                                            $trx->jenis === 'keluar'
                                                ? ($trx->alasan_keluar
                                                    ? str_replace('_', ' ', $trx->alasan_keluar)
                                                    : 'Keluar')
                                                : 'Barang masuk';

                                        if ($trx->unitBarang) {
                                            $keteranganTransaksi .= ' · ' . $trx->unitBarang->nomor_unit;
                                        }
                                    @endphp

                                    <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ optional($trx->tanggal_transaksi)->format('d M Y') }}
                                        </td>

                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <span
                                                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $badgeJenis }}">
                                                {{ ucfirst($trx->jenis) }}
                                            </span>
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ $trx->jumlah }}
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $keteranganTransaksi }}
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
                @endif
            </div>

            <div x-cloak x-show="tab === 'peminjaman'" x-transition>
                @if ($riwayatPeminjaman->isEmpty())
                    <x-empty-state icon="bi-people" title="Belum ada riwayat peminjaman"
                        message="Riwayat peminjaman barang akan muncul di sini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Tgl Pinjam
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Kode
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Peminjam
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Unit/Qty
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayatPeminjaman as $detail)
                                    <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ optional($detail->peminjaman?->tanggal_pinjam)->format('d M Y') }}
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                            #{{ $detail->peminjaman?->id ?? '-' }}
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $detail->peminjaman?->nama_peminjam ?? '-' }}
                                        </td>

                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $detail->unitBarang?->nomor_unit ?? 'Qty ' . $detail->jumlah }}
                                        </td>

                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <x-status-badge :status="$detail->status_item" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
