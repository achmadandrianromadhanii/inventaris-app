<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Website</title>
    <style>
        @page {
            margin: 22px 24px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header {
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 90px;
            vertical-align: top;
            padding-right: 15px;
        }

        .logo {
            width: 75px;
            height: auto;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .school-subtitle {
            font-size: 11px;
            margin: 2px 0 0 0;
            color: #4b5563;
        }

        .meta {
            font-size: 10px;
            color: #4b5563;
            margin-top: 6px;
        }

        .section {
            margin-top: 12px;
        }

        .section.page-break {
            page-break-before: always;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .summary {
            margin-bottom: 10px;
        }

        .summary-badge {
            display: inline-block;
            padding: 3px 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #374151;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-sky {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-violet {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge-emerald {
            background: #d1fae5;
            color: #047857;
        }

        .badge-amber {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background: #f9fafb;
            font-size: 10px;
            text-transform: uppercase;
            color: #4b5563;
        }

        .text-muted {
            color: #6b7280;
        }

        .status-badge,
        .kondisi-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        .kondisi-baik {
            background: #d1fae5;
            color: #047857;
        }

        .kondisi-lumayan {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .kondisi-rusak {
            background: #fef3c7;
            color: #b45309;
        }

        .kondisi-rusak-parah {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-tersedia {
            background: #d1fae5;
            color: #047857;
        }

        .status-dipinjam {
            background: #fef3c7;
            color: #b45309;
        }

        .status-rusak {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-keluar {
            background: #f3f4f6;
            color: #374151;
        }

        .status-aktif {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-selesai {
            background: #f3f4f6;
            color: #374151;
        }

        .empty {
            padding: 14px;
            border: 1px dashed #d1d5db;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        \Carbon\Carbon::setLocale('id');
        $periodeLabel =
            \Carbon\Carbon::parse($filters['dari'])->translatedFormat('d F Y') .
            ' - ' .
            \Carbon\Carbon::parse($filters['sampai'])->translatedFormat('d F Y');

        $badgeKondisi = function (int $nilai) {
            if ($nilai >= 80) {
                return ['Baik', 'kondisi-baik'];
            }

            if ($nilai >= 60) {
                return ['Lumayan', 'kondisi-lumayan'];
            }

            if ($nilai >= 35) {
                return ['Rusak', 'kondisi-rusak'];
            }

            return ['Rusak Parah', 'kondisi-rusak-parah'];
        };

        $badgeStatus = function (string $status) {
            return match ($status) {
                'tersedia' => ['Tersedia', 'status-tersedia'],
                'dipinjam' => ['Dipinjam', 'status-dipinjam'],
                'rusak' => ['Rusak', 'status-rusak'],
                'keluar' => ['Keluar', 'status-keluar'],
                'aktif' => ['Aktif', 'status-aktif'],
                'selesai' => ['Selesai', 'status-selesai'],
                default => [ucfirst($status), 'status-keluar'],
            };
        };
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo Sekolah" class="logo">
                    @endif
                </td>
                <td>
                    <p class="school-name">SMKN 9 Malang</p>
                    <p class="school-subtitle">Sistem Inventaris Lab RPL — Website</p>
                    <div class="meta">
                        Periode: {{ $periodeLabel }}<br>
                        Tanggal cetak: {{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y H:i:s') }} WIB
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- SECTION 1 --}}
    <div class="section">
        <p class="section-title">
            @if(($filters['tipe_laporan'] ?? 'lengkap') === 'rusak')
                Inventaris Barang Rusak / Perlu Perbaikan
            @else
                Inventaris Barang
            @endif
        </p>

        <div class="summary">
            <span class="summary-badge badge-gray">Total {{ $inventarisSummary['total'] }}</span>
            <span class="summary-badge badge-sky">Aset {{ $inventarisSummary['aset'] }}</span>
            <span class="summary-badge badge-violet">Stok {{ $inventarisSummary['stok'] }}</span>
            <span class="summary-badge badge-emerald">Aktif {{ $inventarisSummary['aktif'] }}</span>
        </div>

        @if ($inventaris->isNotEmpty())
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th>Merek</th>
                        <th>Lokasi</th>
                        <th>Thn</th>
                        <th>Kondisi</th>
                        <th>Rusak</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventaris as $item)
                        @php
                            $isAset = $item->tipe === 'aset';

                            $isAset = $item->tipe === 'aset';
                            $kondisi = $item->kondisi_efektif;

                            [$labelKondisi, $classKondisi] = $badgeKondisi($kondisi);

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

                            [$labelStatus, $classStatus] = $badgeStatus($status);
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->kategori?->nama }}</td>
                            <td>{{ ucfirst($item->tipe) }}</td>
                            <td>{{ $item->label_merek }}</td>
                            <td>{{ $item->label_lokasi }}</td>
                            <td>{{ $item->tahun_pengadaan ?: '—' }}</td>
                            <td>
                                <span class="kondisi-badge {{ $classKondisi }}">{{ $labelKondisi }}
                                    {{ $kondisi }}%</span>
                            </td>
                            <td style="color: #b91c1c; font-weight: bold;">
                                {{ $isAset ? ($item->unit_rusak_count ?? 0) . ' Unit' : ($item->qty_rusak ?? 0) }}
                            </td>
                            <td>
                                <span class="status-badge {{ $classStatus }}">{{ $labelStatus }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Belum ada data inventaris.</div>
        @endif
    </div>

    @if (($filters['tipe_laporan'] ?? 'lengkap') !== 'rusak')
    {{-- SECTION 2 --}}
    <div class="section page-break">
        <p class="section-title">Transaksi</p>

        <div class="summary">
            <span class="summary-badge badge-emerald">Masuk {{ $transaksiSummary['masuk'] }}</span>
            <span class="summary-badge badge-gray">Keluar {{ $transaksiSummary['keluar'] }}</span>
        </div>

        @if ($transaksi->isNotEmpty())
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Barang</th>
                        <th>Jml</th>
                        <th>Kondisi</th>
                        <th>Sumber/Tujuan</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaksi as $trx)
                        @php
                            $jenisStatus = $trx->jenis === 'masuk' ? 'aktif' : 'keluar';
                            [$labelJenis, $classJenis] = $badgeStatus($jenisStatus);

                            if ($trx->kondisi_saat_itu !== null) {
                                [$labelKondisiTrx, $classKondisiTrx] = $badgeKondisi((int) $trx->kondisi_saat_itu);
                            }
                        @endphp

                        <tr>
                            <td>{{ optional($trx->tanggal_transaksi)->format('d M Y') }}</td>
                            <td><span class="status-badge {{ $classJenis }}">{{ $labelJenis }}</span></td>
                            <td>{{ $trx->barang?->nama ?? '-' }}</td>
                            <td>{{ $trx->jumlah }}</td>
                            <td>
                                @if ($trx->kondisi_saat_itu !== null)
                                    <span class="kondisi-badge {{ $classKondisiTrx }}">{{ $labelKondisiTrx }}
                                        {{ $trx->kondisi_saat_itu }}%</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $trx->lokasiTujuan?->nama ?? ($trx->lokasi_tujuan_manual ?? ($trx->sumber_tujuan ?? '—')) }}
                            </td>
                            <td>{{ $trx->pengguna?->nama ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada transaksi pada periode ini.</div>
        @endif
    </div>

    {{-- SECTION 3 --}}
    <div class="section page-break">
        <p class="section-title">Peminjaman</p>

        <div class="summary">
            <span class="summary-badge badge-blue">Aktif {{ $peminjamanSummary['aktif'] }}</span>
            <span class="summary-badge badge-gray">Selesai {{ $peminjamanSummary['selesai'] }}</span>
        </div>

        @if ($peminjaman->isNotEmpty())
            <table class="table">
                <thead>
                    <tr>
                        <th>Tgl Pinjam</th>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Kelas</th>
                        <th>Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($peminjaman as $pinjam)
                        @php
                            [$labelStatusPinjam, $classStatusPinjam] = $badgeStatus($pinjam->status);
                        @endphp

                        <tr>
                            <td>{{ optional($pinjam->tanggal_pinjam)->format('d M Y') }}</td>
                            <td>#{{ $pinjam->id }}</td>
                            <td>{{ $pinjam->nama_peminjam }}</td>
                            <td>{{ $pinjam->kelas?->nama }} / {{ $pinjam->jurusan?->nama }}</td>
                            <td>{{ $pinjam->detail_peminjaman_count }} item</td>
                            <td><span class="status-badge {{ $classStatusPinjam }}">{{ $labelStatusPinjam }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data peminjaman pada periode ini.</div>
        @endif
    </div>
    @endif
</body>

</html>
