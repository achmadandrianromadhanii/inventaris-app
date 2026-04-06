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

        $filename = 'laporan-shiro-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    protected function buildReportData(array $filters): array
    {
        $inventaris = $this->getInventarisData();
        $transaksi = $this->getTransaksiData($filters['dari'], $filters['sampai']);
        $peminjaman = $this->getPeminjamanData($filters['dari'], $filters['sampai']);

        return [
            'filters' => $filters,
            'inventaris' => $inventaris,
            'inventarisSummary' => $this->getInventarisSummary($inventaris),
            'transaksi' => $transaksi,
            'transaksiSummary' => $this->getTransaksiSummary($transaksi),
            'peminjaman' => $peminjaman,
            'peminjamanSummary' => $this->getPeminjamanSummary($peminjaman),
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

        return [
            'dari' => $dari->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
        ];
    }

    protected function normalizeDate(mixed $value, Carbon $fallback): Carbon
    {
        if (!is_string($value) || trim($value) === '') {
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
                'unitBarang as unit_tersedia_count' => fn($q) => $q->where('status', 'tersedia'),
                'unitBarang as unit_dipinjam_count' => fn($q) => $q->where('status', 'dipinjam'),
                'unitBarang as unit_rusak_count' => fn($q) => $q->where('status', 'rusak'),
                'unitBarang as unit_keluar_count' => fn($q) => $q->where('status', 'keluar'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
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

    protected function getTransaksiSummary(Collection $transaksi): array
    {
        return [
            'masuk' => $transaksi->where('jenis', 'masuk')->count(),
            'keluar' => $transaksi->where('jenis', 'keluar')->count(),
        ];
    }

    protected function getPeminjamanSummary(Collection $peminjaman): array
    {
        return [
            'aktif' => $peminjaman->where('status', 'aktif')->count(),
            'selesai' => $peminjaman->where('status', 'selesai')->count(),
        ];
    }

    protected function getLogoBase64(): ?string
    {
        $logoPath = public_path('logo-sekolah.png');

        if (!is_file($logoPath) || !is_readable($logoPath)) {
            return null;
        }

        $content = file_get_contents($logoPath);

        if ($content === false) {
            return null;
        }

        $mime = mime_content_type($logoPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
