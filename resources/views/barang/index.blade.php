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

            $rataKondisi = $isAset
                ? (int) round((float) ($item->rata_kondisi_unit ?? 0))
                : (int) ($item->kondisi_stok ?? 100);

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

    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Kelola Barang
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kelola aset dan stok inventaris laboratorium.
                </p>
            </div>

            <a href="{{ route('barang.create') }}"
                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Barang</span>
            </a>
        </div>

        <form method="GET" action="{{ route('barang.index') }}" x-data="{ open: false }"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
            <div class="w-full max-w-52">
                <label for="q" class="sr-only">Cari barang</label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                        <i class="bi bi-search text-sm"></i>
                    </span>

                    <input id="q" name="q" type="text" value="{{ $filters['q'] }}"
                        placeholder="Cari nama barang..."
                        class="block w-full rounded-md border-gray-300 py-1.5 pl-8 pr-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
            </div>

            <div class="relative" @click.outside="open = false">
                <button type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    @click="open = !open">
                    <i class="bi bi-funnel"></i>
                    <span>Filter</span>

                    @if ($activeFilterCount > 0)
                        <span
                            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] text-white">
                            {{ $activeFilterCount }}
                        </span>
                    @endif

                    <i class="bi bi-chevron-down text-[10px]"></i>
                </button>

                <div x-cloak x-show="open"
                    class="absolute left-0 z-30 mt-2 w-[290px] rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="space-y-3">
                        <div>
                            <p
                                class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Kategori
                            </p>

                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="radio" name="kategori_id" value="" @checked(blank($filters['kategori_id']))>
                                    <span>Semua</span>
                                </label>

                                @foreach ($kategori as $item)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                        <input type="radio" name="kategori_id" value="{{ $item->id }}"
                                            @checked((string) $filters['kategori_id'] === (string) $item->id)>
                                        <span>{{ $item->nama }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Tipe
                            </p>

                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="radio" name="tipe" value="" @checked(blank($filters['tipe']))>
                                    <span>Semua</span>
                                </label>

                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="radio" name="tipe" value="aset" @checked(($filters['tipe'] ?? null) === 'aset')>
                                    <span>Aset</span>
                                </label>

                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="radio" name="tipe" value="stok" @checked(($filters['tipe'] ?? null) === 'stok')>
                                    <span>Stok</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Status
                            </p>

                            <div class="space-y-1.5">
                                @foreach (['' => 'Semua', 'tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'rusak' => 'Rusak', 'keluar' => 'Keluar'] as $value => $label)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                        <input type="radio" name="status" value="{{ $value }}"
                                            @checked(($filters['status'] ?? '') === $value)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Kondisi
                            </p>

                            <div class="space-y-1.5">
                                @foreach ([
            '' => 'Semua',
            'baik' => 'Baik ≥80',
            'lumayan' => 'Lumayan 60-79',
            'rusak' => 'Rusak 35-59',
            'rusak_parah' => 'Rusak Parah ≤34',
        ] as $value => $label)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                        <input type="radio" name="kondisi" value="{{ $value }}"
                                            @checked(($filters['kondisi'] ?? '') === $value)>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <a href="{{ route('barang.index') }}"
                                class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                Reset
                            </a>

                            <button type="submit"
                                class="rounded-md bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700"
                                @click="open = false">
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($activeFilterCount > 0 || filled($filters['q']))
                <div class="flex flex-wrap items-center gap-1.5">
                    @if (filled($filters['q']))
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            Cari: {{ $filters['q'] }}
                        </span>
                    @endif

                    @if ($kategoriAktif)
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            Kategori: {{ $kategoriAktif->nama }}
                        </span>
                    @endif

                    @if ($labelTipeFilter)
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            Tipe: {{ $labelTipeFilter }}
                        </span>
                    @endif

                    @if ($labelStatusFilter)
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            Status: {{ $labelStatusFilter }}
                        </span>
                    @endif

                    @if ($labelKondisiFilter)
                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            Kondisi: {{ $labelKondisiFilter }}
                        </span>
                    @endif
                </div>
            @endif
        </form>

        @if ($barang->count() > 0)
            <div
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    #
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Nama
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Kategori
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Merek
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Tipe
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Kondisi
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Jml/Stok
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-right text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barang as $item)
                                @php
                                    $meta = $resolveBarangMeta($item);
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
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
                                        <span
                                            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $meta['is_aset']
                                                ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400'
                                                : 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-900/20 dark:text-violet-400' }}">
                                            {{ $meta['is_aset'] ? 'Aset' : 'Stok' }}
                                        </span>
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600 dark:text-gray-300">
                                                {{ $meta['rata_kondisi'] }}%
                                            </span>

                                            <div class="w-full max-w-[72px]">
                                                <div
                                                    class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                    <div class="h-1.5 origin-left rounded-full {{ $meta['progress_color'] }}"
                                                        style="transform: scaleX({{ number_format($meta['progress_scale'], 2, '.', '') }});">
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
                                            <a href="{{ route('barang.show', $item) }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-50 text-sm text-sky-600 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30"
                                                title="Detail barang" aria-label="Detail barang">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="{{ route('barang.edit', $item) }}"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-sm text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                                title="Edit barang" aria-label="Edit barang">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <div x-data="{ hapus: false }">
                                                <button type="button"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-sm text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                                    title="Hapus barang" aria-label="Hapus barang" @click="hapus = true">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                                <x-confirm-modal name="hapus" title="Hapus Barang"
                                                    message="Barang '{{ $item->nama }}' akan dihapus. Sistem akan menolak jika barang masih dipinjam atau sudah punya riwayat transaksi/peminjaman."
                                                    confirm-text="Ya, Hapus">
                                                    <x-slot:footer>
                                                        <button type="button"
                                                            class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                                            @click="hapus = false">
                                                            Batal
                                                        </button>

                                                        <form method="POST"
                                                            action="{{ route('barang.destroy', $item) }}">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit"
                                                                class="rounded-md bg-red-500 px-3 py-1.5 text-xs text-white hover:bg-red-600">
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

            <div class="grid gap-3 lg:hidden">
                @foreach ($barang as $item)
                    @php
                        $meta = $resolveBarangMeta($item);
                    @endphp

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $item->nama }}
                                </p>
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->kategori?->nama }} · {{ $item->label_merek }}
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $meta['is_aset']
                                    ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400'
                                    : 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-900/20 dark:text-violet-400' }}">
                                {{ $meta['is_aset'] ? 'Aset' : 'Stok' }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <span class="text-xs text-gray-600 dark:text-gray-300">
                                {{ $meta['rata_kondisi'] }}%
                            </span>

                            <div class="w-full max-w-[72px]">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-1.5 origin-left rounded-full {{ $meta['progress_color'] }}"
                                        style="transform: scaleX({{ number_format($meta['progress_scale'], 2, '.', '') }});">
                                    </div>
                                </div>
                            </div>

                            <x-kondisi-badge :kondisi="$meta['rata_kondisi']" />
                            <x-status-badge :status="$meta['status_display']" />
                        </div>

                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            @if ($meta['is_aset'])
                                {{ $item->unit_barang_count }} unit · {{ $item->unit_tersedia_count }} tersedia ·
                                {{ $item->unit_dipinjam_count }} dipinjam
                            @else
                                {{ (int) $item->qty_total }} stok · {{ (int) $item->qty_tersedia }} tersedia ·
                                {{ (int) $item->qty_dipinjam }} dipinjam
                            @endif
                        </div>

                        <div class="mt-3 flex gap-1">
                            <a href="{{ route('barang.show', $item) }}"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-50 text-sm text-sky-600 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30"
                                title="Detail barang" aria-label="Detail barang">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('barang.edit', $item) }}"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-amber-50 text-sm text-amber-600 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30"
                                title="Edit barang" aria-label="Edit barang">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <div x-data="{ hapus: false }">
                                <button type="button"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-sm text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                    title="Hapus barang" aria-label="Hapus barang" @click="hapus = true">
                                    <i class="bi bi-trash"></i>
                                </button>

                                <x-confirm-modal name="hapus" title="Hapus Barang"
                                    message="Barang '{{ $item->nama }}' akan dihapus. Sistem akan menolak jika barang masih dipinjam atau sudah punya riwayat transaksi/peminjaman."
                                    confirm-text="Ya, Hapus">
                                    <x-slot:footer>
                                        <button type="button"
                                            class="rounded-md bg-gray-100 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                            @click="hapus = false">
                                            Batal
                                        </button>

                                        <form method="POST" action="{{ route('barang.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="rounded-md bg-red-500 px-3 py-1.5 text-xs text-white hover:bg-red-600">
                                                Ya, Hapus
                                            </button>
                                        </form>
                                    </x-slot:footer>
                                </x-confirm-modal>
                            </div>
                        </div>
                    </div>
                @endforeach
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
    </div>
@endsection
