<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);
        $report = $this->buildReportData($filters);

        return view('laporan.index', $report);
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $this->resolveFilters($request);
        $report = $this->buildReportData($filters);

        $pdf = Pdf::loadView('laporan.pdf', [
            ...$report,
            'logoBase64' => $this->getLogoBase64(),
            'tanggalCetak' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-shiro-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }

    protected function buildReportData(array $filters): array
    {
        $tipe = $filters['tipe_laporan'];

        $inventaris = collect();
        $transaksi = collect();
        $peminjaman = collect();

        // Data fetching logic based on report type
        if ($tipe === 'lengkap') {
            $inventaris = $this->getInventarisData();
            $transaksi = $this->getTransaksiData($filters['dari'], $filters['sampai']);
            $peminjaman = $this->getPeminjamanData($filters['dari'], $filters['sampai']);
        } elseif ($tipe === 'rusak') {
            $inventaris = $this->getInventarisRusakData();
        }

        return [
            'filters' => $filters,
            'inventaris' => $inventaris,
            'inventarisSummary' => $this->getInventarisSummary($inventaris),
            'transaksi' => $transaksi,
            'transaksiSummary' => $tipe === 'rusak' ? ['masuk' => 0, 'keluar' => 0] : $this->getTransaksiSummary($transaksi),
            'peminjaman' => $peminjaman,
            'peminjamanSummary' => $tipe === 'rusak' ? ['aktif' => 0, 'selesai' => 0] : $this->getPeminjamanSummary($peminjaman),
        ];
    }

    protected function resolveFilters(Request $request): array
    {
        $defaultDari = now()->startOfMonth();
        $defaultSampai = now();

        $dari = $this->normalizeDate(
            value: $request->query('dari'),
            fallback: $defaultDari
        );

        $sampai = $this->normalizeDate(
            value: $request->query('sampai'),
            fallback: $defaultSampai
        );

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $tipeLaporan = in_array($request->query('tipe_laporan'), ['lengkap', 'rusak'], true)
            ? $request->query('tipe_laporan')
            : 'lengkap';

        return [
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
            'tipe_laporan' => $tipeLaporan,
        ];
    }

    protected function normalizeDate(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback->copy();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    protected function getInventarisData(): Collection
    {
        return Barang::query()
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang',
                'unitBarang as unit_tersedia_count' => fn ($q) => $q->where('status', 'tersedia'),
                'unitBarang as unit_dipinjam_count' => fn ($q) => $q->where('status', 'dipinjam'),
                'unitBarang as unit_rusak_count' => fn ($q) => $q->where('status', 'rusak'),
                'unitBarang as unit_keluar_count' => fn ($q) => $q->where('status', 'keluar'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->orderBy('nama')
            ->get();
    }

    protected function getInventarisRusakData(): Collection
    {
        return Barang::query()
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang',
                'unitBarang as unit_tersedia_count' => fn ($q) => $q->where('status', 'tersedia'),
                'unitBarang as unit_dipinjam_count' => fn ($q) => $q->where('status', 'dipinjam'),
                'unitBarang as unit_rusak_count' => fn ($q) => $q->where('status', 'rusak'),
                'unitBarang as unit_keluar_count' => fn ($q) => $q->where('status', 'keluar'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->where(function ($query) {
                // Aset dengan unit rusak
                $query->whereHas('unitBarang', function ($q) {
                    $q->where('status', 'rusak');
                })
                // Atau Stok dengan qty rusak
                    ->orWhere(function ($q) {
                        $q->where('tipe', 'stok')
                            ->where('qty_rusak', '>', 0);
                    })
                // Atau Stok dengan kondisi buruk (<= 59%)
                    ->orWhere(function ($q) {
                        $q->where('tipe', 'stok')
                            ->where('kondisi_stok', '<=', 59);
                    });
            })
            ->orderBy('nama')
            ->get();
    }

    protected function getTransaksiData(string $dari, string $sampai): Collection
    {
        return Transaksi::query()
            ->with([
                'barang:id,nama,tipe',
                'pengguna:id,nama',
                'unitBarang:id,nomor_unit',
                'lokasiTujuan:id,nama',
            ])
            ->whereDate('tanggal_transaksi', '>=', $dari)
            ->whereDate('tanggal_transaksi', '<=', $sampai)
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->get();
    }

    protected function getPeminjamanData(string $dari, string $sampai): Collection
    {
        return Peminjaman::query()
            ->with([
                'kelas:id,nama',
                'jurusan:id,nama',
            ])
            ->withCount('detailPeminjaman')
            ->whereDate('tanggal_pinjam', '>=', $dari)
            ->whereDate('tanggal_pinjam', '<=', $sampai)
            ->latest('tanggal_pinjam')
            ->latest('id')
            ->get();
    }

    protected function getInventarisSummary(Collection $inventaris): array
    {
        return [
            'total' => $inventaris->count(),
            'aset' => $inventaris->where('tipe', 'aset')->count(),
            'stok' => $inventaris->where('tipe', 'stok')->count(),
            'aktif' => $inventaris->where('aktif', true)->count(),
        ];
    }

    protected function getInventarisSummaryDB(): array
    {
        return [
            'total' => Barang::count(),
            'aset' => Barang::where('tipe', 'aset')->count(),
            'stok' => Barang::where('tipe', 'stok')->count(),
            'aktif' => Barang::where('aktif', true)->count(),
        ];
    }

    protected function getTransaksiSummary(Collection $transaksi): array
    {
        return [
            'masuk' => $transaksi->where('jenis', 'masuk')->count(),
            'keluar' => $transaksi->where('jenis', 'keluar')->count(),
        ];
    }

    protected function getTransaksiSummaryDB(string $dari, string $sampai): array
    {
        $baseQuery = Transaksi::whereBetween('tanggal_transaksi', [$dari, $sampai]);

        return [
            'masuk' => (clone $baseQuery)->where('jenis', 'masuk')->count(),
            'keluar' => (clone $baseQuery)->where('jenis', 'keluar')->count(),
        ];
    }

    protected function getPeminjamanSummary(Collection $peminjaman): array
    {
        return [
            'aktif' => $peminjaman->where('status', 'aktif')->count(),
            'selesai' => $peminjaman->where('status', 'selesai')->count(),
        ];
    }

    protected function getPeminjamanSummaryDB(string $dari, string $sampai): array
    {
        $baseQuery = Peminjaman::whereBetween('tanggal_pinjam', [$dari, $sampai]);

        return [
            'aktif' => (clone $baseQuery)->where('status', 'aktif')->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];
    }

    protected function getLogoBase64(): ?string
    {
        $logoPath = public_path('images/logo-sekolah.png');

        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            return null;
        }

        $content = file_get_contents($logoPath);

        if ($content === false) {
            return null;
        }

        $mime = mime_content_type($logoPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
