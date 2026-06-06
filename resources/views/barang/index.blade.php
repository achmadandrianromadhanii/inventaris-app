@extends('layouts.app')

@section('title', 'Kelola Barang')
@section('meta_description', 'Kelola data barang inventaris Shiro.')

@section('content')
@php
$labelKondisiFilter = match ($filters['kondisi'] ?? null) {
'baik' => 'Baik ≥80',
'lumayan' => 'Lumayan 60-79',
'rusak' => 'Rusak 35-59',
'rusak_parah' => 'Rusak Parah ≤34',
default => null,
};

$labelStatusFilter = match ($filters['status'] ?? null) {
'tersedia' => 'Tersedia',
'dipinjam' => 'Dipinjam',
'rusak' => 'Rusak',
'keluar' => 'Keluar',
default => null,
};

$labelTipeFilter = match ($filters['tipe'] ?? null) {
'aset' => 'Aset',
'stok' => 'Stok',
default => null,
};

$activeFilterCount = collect([
filled($filters['kategori_id'] ?? null),
filled($filters['tipe'] ?? null),
filled($filters['status'] ?? null),
filled($filters['kondisi'] ?? null),
])
->filter()
->count();

$kategoriAktif = filled($filters['kategori_id'] ?? null)
? $kategori->firstWhere('id', (int) $filters['kategori_id'])
: null;

$isPaginator = method_exists($barang, 'links');

$resolveBarangMeta = function ($item) {
$isAset = $item->tipe === 'aset';

$rataKondisi = $item->kondisi_efektif;

$statusDisplay = $isAset
? (($item->unit_rusak_count ?? 0) > 0
? 'rusak'
: (($item->unit_dipinjam_count ?? 0) > 0
? 'dipinjam'
: (($item->unit_tersedia_count ?? 0) > 0
? 'tersedia'
: 'keluar')))
: (($item->qty_rusak ?? 0) > 0
? 'rusak'
: (($item->qty_dipinjam ?? 0) > 0
? 'dipinjam'
: (($item->qty_tersedia ?? 0) > 0
? 'tersedia'
: 'keluar')));

$progressColor = match (true) {
$rataKondisi >= 80 => 'bg-emerald-500',
$rataKondisi >= 60 => 'bg-blue-500',
$rataKondisi >= 35 => 'bg-amber-500',
default => 'bg-red-500',
};

$progressScale = max(0, min(100, $rataKondisi)) / 100;

return [
'is_aset' => $isAset,
'rata_kondisi' => $rataKondisi,
'status_display' => $statusDisplay,
'progress_color' => $progressColor,
'progress_scale' => $progressScale,
];
};
@endphp

<turbo-frame id="barang-index" data-turbo-action="advance" class="space-y-4 block">
    <!-- Page Header -->
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Kelola Barang
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Kelola aset dan stok inventaris laboratorium.
        </p>
    </div>

    <!-- Toolbar (Search, Filter, Add) -->
    <form method="GET" action="{{ route('barang.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <!-- Search Bar -->
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <i class="bi bi-search text-sm"></i>
            </span>
            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}"
                x-data @input.debounce.500ms="$el.closest('form').requestSubmit()"
                placeholder="Cari nama barang..."
                class="block w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500">
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Filter Component -->
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <i class="bi bi-funnel text-xs"></i>
                    <span>Filter</span>
                    @if ($activeFilterCount > 0)
                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-blue-600 px-1.5 text-[10px] font-bold text-white">
                        {{ $activeFilterCount }}
                    </span>
                    @endif
                </button>

                <!-- Mobile Backdrop -->
                <div x-cloak x-show="open"
                    x-transition:enter="transition-opacity duration-200"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-40 bg-black/40 sm:hidden"
                    @click="open = false"></div>

                <!-- Filter Panel (Popover Desktop / Drawer Mobile) -->
                <div x-cloak x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-y-full sm:translate-y-1 sm:opacity-0"
                    x-transition:enter-end="translate-y-0 sm:opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-y-0 sm:opacity-100"
                    x-transition:leave-end="translate-y-full sm:translate-y-1 sm:opacity-0"
                    class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85vh] flex-col rounded-t-2xl bg-white shadow-2xl sm:absolute sm:left-auto sm:right-0 sm:top-full sm:bottom-auto sm:mt-2 sm:w-[380px] sm:max-h-[70vh] sm:rounded-xl sm:border sm:border-slate-200 sm:shadow-lg dark:bg-slate-900 dark:sm:border-slate-700"
                    @click.outside="open = false">

                    <!-- Panel Header -->
                    <div class="flex flex-shrink-0 items-start justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800 md:border-none md:pb-0">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Filter Barang</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Filter data berdasarkan kategori, tipe, dan status.</p>
                        </div>
                        <button type="button" @click="open = false" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-slate-300">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- Panel Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto px-5 py-5">
                        <div class="space-y-6">
                            <!-- Kategori (Chips) -->
                            <section>
                                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kategori</h4>
                                <div class="flex flex-wrap gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="kategori_id" value="" class="peer sr-only" @checked(blank($filters['kategori_id']))>
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:peer-checked:border-blue-600 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white">
                                            Semua
                                        </span>
                                    </label>
                                    @foreach ($kategori as $item)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="kategori_id" value="{{ $item->id }}" class="peer sr-only" @checked((string) ($filters['kategori_id'] ?? '' )===(string) $item->id)>
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:peer-checked:border-blue-600 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white">
                                            {{ $item->nama }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </section>

                            <!-- Tipe (Chips) -->
                            <section>
                                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tipe Barang</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['' => 'Semua', 'aset' => 'Aset', 'stok' => 'Stok'] as $val => $lbl)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="tipe" value="{{ $val }}" class="peer sr-only" @checked(($filters['tipe'] ?? '' )===$val)>
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:peer-checked:border-blue-600 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white">
                                            {{ $lbl }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </section>

                            <!-- Status (Chips) -->
                            <section>
                                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['' => 'Semua', 'tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'rusak' => 'Rusak', 'keluar' => 'Keluar'] as $val => $lbl)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="status" value="{{ $val }}" class="peer sr-only" @checked(($filters['status'] ?? '' )===$val)>
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:peer-checked:border-blue-600 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white">
                                            {{ $lbl }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </section>

                            <!-- Kondisi (Chips) -->
                            <section>
                                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kondisi</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['' => 'Semua', 'baik' => 'Baik ≥80', 'lumayan' => 'Lumayan 60-79', 'rusak' => 'Rusak 35-59', 'rusak_parah' => 'Rusak Parah ≤34'] as $val => $lbl)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="kondisi" value="{{ $val }}" class="peer sr-only" @checked(($filters['kondisi'] ?? '' )===$val)>
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:peer-checked:border-blue-600 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white">
                                            {{ $lbl }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Panel Footer (Sticky) -->
                    <div class="flex flex-shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <a href="{{ route('barang.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            Reset
                        </a>
                        <button type="submit" @click="open = false"
                            class="inline-flex flex-1 md:flex-none items-center justify-center rounded-xl bg-blue-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 shadow-sm shadow-blue-600/20">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <a href="{{ route('barang.create') }}" data-turbo-frame="_top"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                <i class="bi bi-plus-lg text-xs"></i>
                <span>Tambah Barang</span>
            </a>
        </div>
    </form>

    <!-- Active Filter Indicator -->
    @if ($activeFilterCount > 0 || filled($filters['q']))
    <div class="flex flex-wrap items-center gap-2 pt-2 md:pt-0">
        <span class="text-xs font-medium text-slate-500">Filter Aktif:</span>
        @if (filled($filters['q']))
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            Pencarian: {{ $filters['q'] }}
        </span>
        @endif
        @if ($kategoriAktif)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            Kategori: {{ $kategoriAktif->nama }}
        </span>
        @endif
        @if ($labelTipeFilter)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            Tipe: {{ $labelTipeFilter }}
        </span>
        @endif
        @if ($labelStatusFilter)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            Status: {{ $labelStatusFilter }}
        </span>
        @endif
        @if ($labelKondisiFilter)
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            Kondisi: {{ $labelKondisiFilter }}
        </span>
        @endif
        <a href="{{ route('barang.index') }}" class="ml-1 text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-400">
            Bersihkan Semua
        </a>
    </div>
    @endif

    @if ($barang->count() > 0)
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            #
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Nama
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Kategori
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Merek
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Tipe
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Kondisi
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Jml/Stok
                        </th>
                        <th scope="col"
                            class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($barang as $item)
                    @php
                    $meta = $resolveBarangMeta($item);
                    @endphp

                    <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                        <td
                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ $barang->firstItem() + $loop->index }}
                        </td>

                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $item->nama }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->label_lokasi }}
                                </p>
                            </div>
                        </td>

                        <td
                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            {{ $item->kategori?->nama }}
                        </td>

                        <td
                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            {{ $item->label_merek }}
                        </td>

                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                            @php
                            $tipeBadge = $meta['is_aset']
                            ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400'
                            : 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-900/20 dark:text-violet-400';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $tipeBadge }}">
                                {{ $meta['is_aset'] ? 'Aset' : 'Stok' }}
                            </span>
                        </td>

                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ $meta['rata_kondisi'] }}%
                                </span>

                                <div class="w-full max-w-[72px]">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        @php $widthPct = max(0, min(100, $meta['rata_kondisi'])); @endphp
                                        <div class="h-1.5 rounded-full {{ $meta['progress_color'] }}"
                                            style="width:{{ $widthPct }}%">
                                        </div>
                                    </div>
                                </div>

                                <x-kondisi-badge :kondisi="$meta['rata_kondisi']" />
                                <x-status-badge :status="$meta['status_display']" />
                            </div>
                        </td>

                        <td
                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            @if ($meta['is_aset'])
                            <div class="space-y-0.5 text-xs">
                                <p>Total: {{ $item->unit_barang_count }}</p>
                                <p>Tersedia: {{ $item->unit_tersedia_count }}</p>
                                <p>Dipinjam: {{ $item->unit_dipinjam_count }}</p>
                            </div>
                            @else
                            <div class="space-y-0.5 text-xs">
                                <p>Total: {{ (int) $item->qty_total }}</p>
                                <p>Tersedia: {{ (int) $item->qty_tersedia }}</p>
                                <p>Dipinjam: {{ (int) $item->qty_dipinjam }}</p>
                            </div>
                            @endif
                        </td>

                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('barang.show', $item) }}" data-turbo-frame="_top"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sm text-sky-600 transition-all hover:-translate-y-0.5 hover:bg-sky-100 hover:shadow-sm dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30"
                                    title="Detail barang" aria-label="Detail barang">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('barang.edit', $item) }}" data-turbo-frame="_top"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-sm text-amber-600 transition-all hover:-translate-y-0.5 hover:bg-amber-100 hover:shadow-sm dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                    title="Edit barang" aria-label="Edit barang">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <div x-data="{ hapus: false }">
                                    <button type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-sm text-red-600 transition-all hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-sm dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                        title="Hapus barang" aria-label="Hapus barang" @click="hapus = true">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                    <x-confirm-modal name="hapus" title="Hapus Barang"
                                        message="Barang '{{ $item->nama }}' akan dihapus. Sistem akan menolak jika barang masih dipinjam atau sudah punya riwayat transaksi/peminjaman."
                                        confirm-text="Ya, Hapus">
                                        <x-slot:footer>
                                            <button type="button"
                                                class="inline-flex w-auto items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs text-slate-700 transition-colors hover:bg-slate-200 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600"
                                                @click="hapus = false">
                                                Batal
                                            </button>

                                            <form method="POST" action="{{ route('barang.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex w-auto items-center justify-center rounded-lg bg-red-500 px-3 py-1.5 text-xs text-white transition-colors hover:bg-red-600">
                                                    Ya, Hapus
                                                </button>
                                            </form>
                                        </x-slot:footer>
                                    </x-confirm-modal>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>



    @if ($isPaginator)
    <div class="pt-1">
        {{ $barang->links('components.pagination') }}
    </div>
    @endif
    @else
    <x-empty-state icon="bi-box-seam" title="Belum ada barang"
        message="Tambahkan barang baru untuk mulai mengelola inventaris." />
    @endif
</turbo-frame>
@endsection