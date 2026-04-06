<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Transaksi;
use App\Models\UnitBarang;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        Carbon::setLocale('id');

        $unitStatus = UnitBarang::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
                SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as dipinjam,
                SUM(CASE WHEN status = 'rusak' THEN 1 ELSE 0 END) as rusak
            ")
            ->first();

        $stokStatus = Barang::query()
            ->where('tipe', 'stok')
            ->selectRaw("
                COALESCE(SUM(qty_tersedia), 0) as tersedia,
                COALESCE(SUM(qty_dipinjam), 0) as dipinjam,
                COALESCE(SUM(qty_rusak), 0) as rusak
            ")
            ->first();

        $kpi = [
            'total_barang' => Barang::query()
                ->where('aktif', true)
                ->count(),

            'barang_tersedia' => (int) ($unitStatus->tersedia ?? 0) + (int) ($stokStatus->tersedia ?? 0),

            'barang_dipinjam' => (int) ($unitStatus->dipinjam ?? 0) + (int) ($stokStatus->dipinjam ?? 0),

            'barang_rusak' => (int) ($unitStatus->rusak ?? 0) + (int) ($stokStatus->rusak ?? 0),

            'total_kategori' => Kategori::query()->count(),
        ];

        $kondisiRaw = UnitBarang::query()
            ->selectRaw("
                SUM(CASE WHEN kondisi >= 80 THEN 1 ELSE 0 END) as baik,
                SUM(CASE WHEN kondisi BETWEEN 60 AND 79 THEN 1 ELSE 0 END) as lumayan,
                SUM(CASE WHEN kondisi BETWEEN 35 AND 59 THEN 1 ELSE 0 END) as rusak,
                SUM(CASE WHEN kondisi <= 34 THEN 1 ELSE 0 END) as rusak_parah
            ")
            ->first();

        $kondisiChart = [
            'baik' => (int) ($kondisiRaw->baik ?? 0),
            'lumayan' => (int) ($kondisiRaw->lumayan ?? 0),
            'rusak' => (int) ($kondisiRaw->rusak ?? 0),
            'rusak_parah' => (int) ($kondisiRaw->rusak_parah ?? 0),
        ];

        $bulanAwal = now()->startOfMonth()->subMonths(5);

        $rentangBulan = collect(range(0, 5))
            ->map(fn(int $index) => $bulanAwal->copy()->addMonths($index));

        $peminjamanBulananRaw = Peminjaman::query()
            ->selectRaw('YEAR(tanggal_pinjam) as tahun, MONTH(tanggal_pinjam) as bulan, COUNT(*) as total')
            ->whereDate('tanggal_pinjam', '>=', $bulanAwal->toDateString())
            ->groupByRaw('YEAR(tanggal_pinjam), MONTH(tanggal_pinjam)')
            ->orderByRaw('YEAR(tanggal_pinjam), MONTH(tanggal_pinjam)')
            ->get()
            ->keyBy(fn($item) => sprintf('%04d-%02d', $item->tahun, $item->bulan));

        $lineChart = [
            'labels' => $rentangBulan
                ->map(fn(Carbon $bulan) => $bulan->translatedFormat('M Y'))
                ->values()
                ->all(),

            'data' => $rentangBulan
                ->map(function (Carbon $bulan) use ($peminjamanBulananRaw): int {
                    $key = $bulan->format('Y-m');

                    return (int) ($peminjamanBulananRaw[$key]->total ?? 0);
                })
                ->values()
                ->all(),
        ];

        $kategoriChartRaw = Kategori::query()
            ->withCount([
                'barang as barang_count' => function ($query) {
                    $query->where('aktif', true);
                },
            ])
            ->orderByDesc('barang_count')
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $barChart = [
            'labels' => $kategoriChartRaw->pluck('nama')->values()->all(),
            'data' => $kategoriChartRaw->pluck('barang_count')
                ->map(fn($jumlah) => (int) $jumlah)
                ->values()
                ->all(),
        ];

        $aktivitasTerbaru = $this->susunAktivitasTerbaru();

        return view('dashboard.index', [
            'kpi' => $kpi,
            'kondisiChart' => $kondisiChart,
            'lineChart' => $lineChart,
            'barChart' => $barChart,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }

    protected function susunAktivitasTerbaru(): Collection
    {
        $transaksi = collect(
            Transaksi::query()
                ->select([
                    'id',
                    'jenis',
                    'barang_id',
                    'jumlah',
                    'alasan_keluar',
                    'tanggal_transaksi',
                    'pengguna_id',
                    'created_at',
                ])
                ->with([
                    'barang:id,nama',
                    'pengguna:id,nama',
                ])
                ->latest('created_at')
                ->limit(15)
                ->get()
                ->map(function (Transaksi $item): array {
                    $waktu = $item->created_at instanceof Carbon
                        ? $item->created_at
                        : Carbon::parse($item->tanggal_transaksi);

                    return [
                        'waktu' => $waktu,
                        'tanggal_label' => $waktu->format('d M Y H:i'),
                        'jenis' => $item->jenis,
                        'barang' => $item->barang?->nama ?? '-',
                        'keterangan' => match ($item->jenis) {
                            'masuk' => 'Barang masuk ' . $item->jumlah . ' item oleh ' . ($item->pengguna?->nama ?? 'Admin'),
                            'keluar' => 'Barang keluar ' . $item->jumlah . ' item'
                                . ($item->alasan_keluar ? ' · alasan: ' . str_replace('_', ' ', $item->alasan_keluar) : ''),
                            default => 'Transaksi barang',
                        },
                    ];
                })
                ->all()
        );

        $peminjaman = collect(
            Peminjaman::query()
                ->select([
                    'id',
                    'nama_peminjam',
                    'tanggal_pinjam',
                    'waktu_pinjam',
                    'created_at',
                ])
                ->with([
                    'detailPeminjaman:id,peminjaman_id,barang_id',
                    'detailPeminjaman.barang:id,nama',
                ])
                ->latest('created_at')
                ->limit(15)
                ->get()
                ->map(function (Peminjaman $item): array {
                    $waktu = $item->created_at instanceof Carbon
                        ? $item->created_at
                        : Carbon::parse($item->tanggal_pinjam . ' ' . $item->waktu_pinjam);

                    return [
                        'waktu' => $waktu,
                        'tanggal_label' => $waktu->format('d M Y H:i'),
                        'jenis' => 'dipinjam',
                        'barang' => $this->ringkasBarangPeminjaman($item),
                        'keterangan' => 'Peminjaman oleh ' . $item->nama_peminjam
                            . ' · ' . $item->detailPeminjaman->count() . ' item',
                    ];
                })
                ->all()
        );

        $pengembalian = collect(
            DetailPeminjaman::query()
                ->select([
                    'id',
                    'barang_id',
                    'peminjaman_id',
                    'status_item',
                    'kondisi_kembali',
                    'waktu_kembali',
                ])
                ->with([
                    'barang:id,nama',
                    'peminjaman:id,nama_peminjam',
                ])
                ->where('status_item', 'dikembalikan')
                ->whereNotNull('waktu_kembali')
                ->latest('waktu_kembali')
                ->limit(15)
                ->get()
                ->map(function (DetailPeminjaman $item): array {
                    $waktu = $item->waktu_kembali instanceof Carbon
                        ? $item->waktu_kembali
                        : Carbon::parse($item->waktu_kembali);

                    return [
                        'waktu' => $waktu,
                        'tanggal_label' => $waktu->format('d M Y H:i'),
                        'jenis' => 'kembali',
                        'barang' => $item->barang?->nama ?? '-',
                        'keterangan' => 'Dikembalikan oleh ' . ($item->peminjaman?->nama_peminjam ?? '-')
                            . ($item->kondisi_kembali !== null ? ' · kondisi ' . $item->kondisi_kembali . '%' : ''),
                    ];
                })
                ->all()
        );

        return $transaksi
            ->merge($peminjaman)
            ->merge($pengembalian)
            ->sortByDesc('waktu')
            ->take(15)
            ->values();
    }

    protected function ringkasBarangPeminjaman(Peminjaman $peminjaman): string
    {
        $namaBarang = collect($peminjaman->detailPeminjaman)
            ->map(fn($detail) => $detail->barang?->nama)
            ->filter()
            ->unique()
            ->values();

        if ($namaBarang->isEmpty()) {
            return '-';
        }

        if ($namaBarang->count() === 1) {
            return $namaBarang->first();
        }

        if ($namaBarang->count() === 2) {
            return $namaBarang->implode(', ');
        }

        return $namaBarang->take(2)->implode(', ') . ' +' . ($namaBarang->count() - 2) . ' lagi';
    }
}
