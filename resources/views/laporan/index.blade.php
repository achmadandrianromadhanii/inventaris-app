        @extends('layouts.app')

@section('title', 'Laporan')
@section('meta_description', 'Laporan inventaris, transaksi, dan peminjaman Shiro.')

@section('content')
    @php
        $hasPeriode = filled($filters['dari'] ?? null) && filled($filters['sampai'] ?? null);

        $periodeLabel = $hasPeriode
            ? \Carbon\Carbon::parse($filters['dari'])->format('d M Y') .
                ' - ' .
                \Carbon\Carbon::parse($filters['sampai'])->format('d M Y')
            : 'Semua periode';
    @endphp

    <turbo-frame id="laporan-filter" data-turbo-action="advance" class="space-y-4 block">
        <div>
            <h1 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                Laporan
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Ringkasan inventaris, transaksi, dan peminjaman.
            </p>
        </div>

        <form method="GET" action="{{ route('laporan.index') }}"
            class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-end gap-3">
                <!-- Date Filters -->
                <div>
                    <label for="dari" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Dari
                    </label>
                    <input id="dari" name="dari" type="date" value="{{ $filters['dari'] }}"
                        class="block w-[140px] rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label for="sampai" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        Sampai
                    </label>
                    <input id="sampai" name="sampai" type="date" value="{{ $filters['sampai'] }}"
                        class="block w-[140px] rounded-lg border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-[7px] text-sm text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-700 shadow-md shadow-indigo-500/30">
                        <i class="bi bi-funnel"></i>
                        <span>Terapkan</span>
                    </button>
                </div>

                <!-- Separator Line (Hidden on small screens) -->
                <div class="hidden sm:block h-8 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

                <!-- Tipe Laporan -->
                <div class="w-52">
                    <select name="tipe_laporan" onchange="this.form.requestSubmit()" class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-[7px] text-sm focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/20 transition-all dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 cursor-pointer shadow-sm">
                        <option value="lengkap" @selected(($filters['tipe_laporan'] ?? 'lengkap') === 'lengkap')>Laporan Lengkap</option>
                        <option value="rusak" @selected(($filters['tipe_laporan'] ?? 'lengkap') === 'rusak')>Laporan Barang Rusak</option>
                    </select>
                </div>

                <!-- Export PDF -->
                <div>
                    <a href="{{ route('laporan.pdf', ['dari' => $filters['dari'], 'sampai' => $filters['sampai'], 'tipe_laporan' => $filters['tipe_laporan'] ?? 'lengkap']) }}"
                        data-turbo="false" target="_blank"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-500 px-4 py-[7px] text-sm font-medium text-white shadow-md shadow-red-500/30 transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-600">
                        <i class="bi bi-file-pdf"></i>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Periode laporan transaksi & peminjaman: {{ $periodeLabel }}
            </p>
        </form>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="bi bi-box-seam text-sm text-blue-600 dark:text-blue-400"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        @if(($filters['tipe_laporan'] ?? 'lengkap') === 'rusak')
                            Inventaris Barang Rusak / Perlu Perbaikan
                        @else
                            Inventaris Barang
                        @endif
                    </h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        Total {{ $inventarisSummary['total'] }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-[10px] font-medium text-sky-700 dark:bg-sky-900/20 dark:text-sky-400">
                        Aset {{ $inventarisSummary['aset'] }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-violet-50 px-2 py-1 text-[10px] font-medium text-violet-700 dark:bg-violet-900/20 dark:text-violet-400">
                        Stok {{ $inventarisSummary['stok'] }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                        Aktif {{ $inventarisSummary['aktif'] }}
                    </span>
                </div>
            </div>

            @if ($inventaris->isNotEmpty())
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
                                        Tipe
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Merek
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Lokasi
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Thn
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Kondisi
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Rusak
                                    </th>
                                    <th scope="col"
                                        class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventaris as $item)
                                    @php
                                        $isAset = $item->tipe === 'aset';

                                        $isAset = $item->tipe === 'aset';
                                        $kondisi = $item->kondisi_efektif;

                                        $status = $isAset
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
                                    @endphp

                                    <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                            {{ $item->nama }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ $item->kategori?->nama }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ ucfirst($item->tipe) }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ $item->label_merek }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ $item->label_lokasi }}
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            {{ $item->tahun_pengadaan ?: '—' }}
                                        </td>
                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <x-kondisi-badge :kondisi="$kondisi" :show-value="true" />
                                        </td>
                                        <td
                                            class="border-b border-gray-100 px-3 py-2 text-sm text-red-600 dark:border-gray-700 dark:text-red-400 font-medium">
                                            {{ $isAset ? ($item->unit_rusak_count ?? 0) . ' Unit' : ($item->qty_rusak ?? 0) }}
                                        </td>
                                        <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                            <x-status-badge :status="$status" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            @else
                <div class="py-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data inventaris yang sesuai.</p>
                </div>
            @endif
        </section>

        @if (($filters['tipe_laporan'] ?? 'lengkap') !== 'rusak')
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="bi bi-arrow-left-right text-sm text-blue-600 dark:text-blue-400"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Transaksi
                    </h2>
                </div>

                <div class="flex gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                        Masuk {{ $transaksiSummary['masuk'] }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        Keluar {{ $transaksiSummary['keluar'] }}
                    </span>
                </div>
            </div>

            @if ($transaksi->isNotEmpty())
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
                                    Barang
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Jml
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Kondisi
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Sumber/Tujuan
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Admin
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksi as $trx)
                                @php
                                    $badgeClass =
                                        $trx->jenis === 'masuk'
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400'
                                            : 'bg-gray-100 text-gray-700 ring-gray-400/20 dark:bg-gray-700 dark:text-gray-200';

                                    $tujuanLabel =
                                        $trx->lokasiTujuan?->nama ??
                                        ($trx->lokasi_tujuan_manual ?? ($trx->sumber_tujuan ?? '—'));
                                @endphp

                                <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ optional($trx->tanggal_transaksi)->format('d M Y') }}
                                    </td>
                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <span
                                            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $badgeClass }}">
                                            {{ ucfirst($trx->jenis) }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        {{ $trx->barang?->nama ?? '-' }}
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
                                        {{ $tujuanLabel }}
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
            @else
                <x-empty-state icon="bi-arrow-left-right" title="Belum ada transaksi"
                    message="Tidak ada transaksi pada periode ini." />
            @endif
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="bi bi-people text-sm text-blue-600 dark:text-blue-400"></i>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Peminjaman
                    </h2>
                </div>

                <div class="flex gap-2">
                    <span
                        class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                        Aktif {{ $peminjamanSummary['aktif'] }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[10px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        Selesai {{ $peminjamanSummary['selesai'] }}
                    </span>
                </div>
            </div>

            @if ($peminjaman->isNotEmpty())
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
                                    Kelas
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Item
                                </th>
                                <th scope="col"
                                    class="border-b border-gray-200 bg-gray-50/50 px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($peminjaman as $pinjam)
                                <tr class="group transition-colors even:bg-slate-50/50 hover:bg-indigo-50/50 dark:even:bg-slate-800/30 dark:hover:bg-indigo-900/20">
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        {{ optional($pinjam->tanggal_pinjam)->format('d M Y') }}
                                    </td>
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 font-mono text-sm text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        #{{ $pinjam->id }}
                                    </td>
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm font-medium text-gray-800 dark:border-gray-700 dark:text-gray-100">
                                        {{ $pinjam->nama_peminjam }}
                                    </td>
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $pinjam->kelas?->nama }} / {{ $pinjam->jurusan?->nama }}
                                    </td>
                                    <td
                                        class="border-b border-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        {{ $pinjam->detail_peminjaman_count }} item
                                    </td>
                                    <td class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                        <x-status-badge :status="$pinjam->status" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state icon="bi-people" title="Belum ada data peminjaman"
                    message="Tidak ada data peminjaman pada periode ini." />
            @endif
        </section>
        @endif
    </turbo-frame>
@endsection
