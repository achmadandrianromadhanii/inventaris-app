@extends('layouts.app')

@section('title', 'Peminjaman')
@section('meta_description', 'Pantau data peminjaman inventaris Shiro.')

@section('content')
    @php
        $tabLinks = [
            'aktif' => 'Aktif',
            'selesai' => 'Selesai',
            'semua' => 'Semua',
        ];

        $queryTanpaPage = request()->except('page');
    @endphp

    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Peminjaman
                </h1>

                <span
                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[10px] font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                    {{ $counts['aktif'] ?? 0 }} aktif
                </span>
            </div>
        </div>

        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-4" aria-label="Filter status peminjaman">
                @foreach ($tabLinks as $key => $label)
                    <a href="{{ route('peminjaman.index', array_merge($queryTanpaPage, ['tab' => $key])) }}"
                        class="border-b-2 px-1 py-2 text-sm {{ $tab === $key
                            ? 'border-blue-600 font-medium text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                        @if ($tab === $key) aria-current="page" @endif>
                        {{ $label }}

                        @if (isset($counts[$key]))
                            <span class="ml-1 text-xs">({{ $counts[$key] }})</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        <form method="GET" action="{{ route('peminjaman.index') }}"
            class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_160px_160px_auto]">
                <div>
                    <label for="q" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Cari nama / kode
                    </label>
                    <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" autocomplete="off"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label for="dari" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Dari
                    </label>
                    <input id="dari" name="dari" type="date" value="{{ $filters['dari'] ?? '' }}"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label for="sampai" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Sampai
                    </label>
                    <input id="sampai" name="sampai" type="date" value="{{ $filters['sampai'] ?? '' }}"
                        class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div class="self-end">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </form>

        @if ($peminjaman->count() > 0)
            <div
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Kode
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Tgl
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Nama
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Kelas/Jurusan
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Item
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-left text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Status
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 px-3 py-2 text-right text-[11px] uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($peminjaman as $item)
                                @php
                                    $isAktif = $item->status === 'aktif';
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 font-mono text-sm text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        {{ $item->kode_pinjam }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ optional($item->tanggal_pinjam)->format('d M Y') }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        {{ $item->nama_peminjam }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $item->kelas?->nama }} / {{ $item->jurusan?->nama }}
                                    </td>

                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $item->detail_peminjaman_count }} item
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <x-status-badge :status="$item->status" />
                                    </td>

                                    <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-700">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($isAktif)
                                                <a href="{{ route('peminjaman.show', ['peminjaman' => $item, 'aksi' => 'kembalikan']) }}"
                                                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-xs text-white hover:bg-emerald-700"
                                                    title="Proses pengembalian" aria-label="Proses pengembalian">
                                                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                                                    <span>Kembalikan</span>
                                                </a>
                                            @endif

                                            <a href="{{ route('peminjaman.show', $item) }}"
                                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700"
                                                title="Lihat detail peminjaman" aria-label="Lihat detail peminjaman">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                <span>Detail</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-3 lg:hidden">
                @foreach ($peminjaman as $item)
                    @php
                        $isAktif = $item->status === 'aktif';
                    @endphp

                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->kode_pinjam }}
                                </p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $item->nama_peminjam }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->kelas?->nama }} / {{ $item->jurusan?->nama }}
                                </p>
                            </div>

                            <x-status-badge :status="$item->status" />
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ optional($item->tanggal_pinjam)->format('d M Y') }}</span>
                            <span>{{ $item->detail_peminjaman_count }} item</span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($isAktif)
                                <a href="{{ route('peminjaman.show', ['peminjaman' => $item, 'aksi' => 'kembalikan']) }}"
                                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-xs text-white hover:bg-emerald-700"
                                    title="Proses pengembalian" aria-label="Proses pengembalian">
                                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                                    <span>Kembalikan</span>
                                </a>
                            @endif

                            <a href="{{ route('peminjaman.show', $item) }}"
                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700"
                                title="Lihat detail peminjaman" aria-label="Lihat detail peminjaman">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                                <span>Detail</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-1">
                {{ $peminjaman->appends($queryTanpaPage)->links('components.pagination') }}
            </div>
        @else
            <x-empty-state icon="bi-people" title="Belum ada data peminjaman"
                message="Data peminjaman akan muncul di sini setelah siswa melakukan peminjaman." />
        @endif
    </div>
@endsection
