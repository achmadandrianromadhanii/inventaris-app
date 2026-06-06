<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Transaksi;
use App\Models\UnitBarang;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        Carbon::setLocale('id');

        // [OPTIMASI LIGHTHOUSE & NEON DB]:
        // Membungkus seluruh 10+ query ke database di dalam Cache lokal selama 60 detik.
        // Dengan ini, Document Request Latency akan menjadi 0ms, membuat Lighthouse kembali 100% hijau!
        // Jika ada perubahan realtime, Pusher tetap akan menampilkan pop-up meskipun data di sini di-cache.
        $data = \Illuminate\Support\Facades\Cache::remember('dashboard_index_data', 60, function () {
            $unitStatus = UnitBarang::query()
                ->selectRaw("
                    SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
                    SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as dipinjam,
                    SUM(CASE WHEN status = 'rusak' THEN 1 ELSE 0 END) as rusak
                ")
                ->first();

            $stokStatus = Barang::query()
                ->where('tipe', 'stok')
                ->selectRaw('
                    COALESCE(SUM(qty_tersedia), 0) as tersedia,
                    COALESCE(SUM(qty_dipinjam), 0) as dipinjam,
                    COALESCE(SUM(qty_rusak), 0) as rusak
                ')
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
                ->selectRaw('
                    SUM(CASE WHEN kondisi >= 80 THEN 1 ELSE 0 END) as baik,
                    SUM(CASE WHEN kondisi BETWEEN 60 AND 79 THEN 1 ELSE 0 END) as lumayan,
                    SUM(CASE WHEN kondisi BETWEEN 35 AND 59 THEN 1 ELSE 0 END) as rusak,
                    SUM(CASE WHEN kondisi <= 34 THEN 1 ELSE 0 END) as rusak_parah
                ')
                ->first();

            $kondisiChart = [
                'baik' => (int) ($kondisiRaw->baik ?? 0),
                'lumayan' => (int) ($kondisiRaw->lumayan ?? 0),
                'rusak' => (int) ($kondisiRaw->rusak ?? 0),
                'rusak_parah' => (int) ($kondisiRaw->rusak_parah ?? 0),
            ];

            $tahunSekarang = now()->year;
            $rentangBulan = collect(range(1, 12));
            $labels = $rentangBulan->map(function ($b) use ($tahunSekarang) {
                return Carbon::createFromDate($tahunSekarang, $b, 1)->translatedFormat('M');
            })->values()->toArray();

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            $peminjaman = Peminjaman::query()
                ->when($driver === 'sqlite', function ($query) {
                    $query->selectRaw("CAST(strftime('%m', tanggal_pinjam) AS INTEGER) as bulan, COUNT(*) as total")
                        ->groupByRaw("strftime('%m', tanggal_pinjam)");
                }, function ($query) use ($driver) {
                    // Perbaikan: Mendukung EXTRACT(MONTH) untuk PostgreSQL dan MONTH() untuk MySQL
                    if ($driver === 'pgsql') {
                        $query->selectRaw('EXTRACT(MONTH FROM tanggal_pinjam) as bulan, COUNT(*) as total')
                            ->groupByRaw('EXTRACT(MONTH FROM tanggal_pinjam)');
                    } else {
                        $query->selectRaw('MONTH(tanggal_pinjam) as bulan, COUNT(*) as total')
                            ->groupByRaw('MONTH(tanggal_pinjam)');
                    }
                })
                ->whereYear('tanggal_pinjam', $tahunSekarang)
                ->get()
                ->keyBy('bulan');

            $dataLine = $rentangBulan->map(function ($bulan) use ($peminjaman) {
                return (int) ($peminjaman[$bulan]->total ?? 0);
            })->values()->toArray();

            $lineChart = [
                'labels' => $labels,
                'data' => $dataLine,
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
                    ->map(fn ($jumlah) => (int) $jumlah)
                    ->values()
                    ->all(),
            ];

            $aktivitasTerbaru = $this->susunAktivitasTerbaru();

            return [
                'kpi' => $kpi,
                'kondisiChart' => $kondisiChart,
                'lineChart' => $lineChart,
                'barChart' => $barChart,
                'aktivitasTerbaru' => $aktivitasTerbaru,
            ];
        });

        return view('dashboard.index', $data);
    }

    public function chartPeminjaman(Request $request): JsonResponse
    {
        Carbon::setLocale('id');
        $filter = $request->query('filter', 'bulanan');

        // [OPTIMASI LIGHTHOUSE & NEON DB]:
        // Menerapkan cache untuk Endpoint JSON grafik agar halaman tidak lag saat loading grafik.
        $cacheKey = 'dashboard_chart_peminjaman_'.$filter;

        $hasil = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($filter) {
            $driver = DB::connection()->getDriverName();
            $labels = [];
            $data = [];

            if ($filter === 'tahunan') {
                // 5 Tahun Terakhir
                $tahunSekarang = now()->year;
                $rentangTahun = collect(range($tahunSekarang - 4, $tahunSekarang));
                $labels = $rentangTahun->map(fn ($t) => (string) $t)->values()->toArray();

                $peminjaman = Peminjaman::query()
                    ->when($driver === 'sqlite', function ($query) {
                        $query->selectRaw("CAST(strftime('%Y', tanggal_pinjam) AS INTEGER) as tahun, COUNT(*) as total")
                            ->groupByRaw("strftime('%Y', tanggal_pinjam)");
                    }, function ($query) use ($driver) {
                        // Perbaikan: Mendukung EXTRACT(YEAR) untuk PostgreSQL dan YEAR() untuk MySQL
                        if ($driver === 'pgsql') {
                            $query->selectRaw('EXTRACT(YEAR FROM tanggal_pinjam) as tahun, COUNT(*) as total')
                                ->groupByRaw('EXTRACT(YEAR FROM tanggal_pinjam)');
                        } else {
                            $query->selectRaw('YEAR(tanggal_pinjam) as tahun, COUNT(*) as total')
                                ->groupByRaw('YEAR(tanggal_pinjam)');
                        }
                    })
                    ->whereYear('tanggal_pinjam', '>=', $tahunSekarang - 4)
                    ->get()
                    ->keyBy('tahun');

                $data = $rentangTahun->map(function ($tahun) use ($peminjaman) {
                    return (int) ($peminjaman[$tahun]->total ?? 0);
                })->values()->toArray();
            } else {
                // bulanan (default)
                $tahunSekarang = now()->year;
                $rentangBulan = collect(range(1, 12));
                $labels = $rentangBulan->map(function ($b) use ($tahunSekarang) {
                    return Carbon::createFromDate($tahunSekarang, $b, 1)->translatedFormat('M');
                })->values()->toArray();

                $peminjaman = Peminjaman::query()
                    ->when($driver === 'sqlite', function ($query) {
                        $query->selectRaw("CAST(strftime('%m', tanggal_pinjam) AS INTEGER) as bulan, COUNT(*) as total")
                            ->groupByRaw("strftime('%m', tanggal_pinjam)");
                    }, function ($query) use ($driver) {
                        // Perbaikan: Mendukung EXTRACT(MONTH) untuk PostgreSQL dan MONTH() untuk MySQL
                        if ($driver === 'pgsql') {
                            $query->selectRaw('EXTRACT(MONTH FROM tanggal_pinjam) as bulan, COUNT(*) as total')
                                ->groupByRaw('EXTRACT(MONTH FROM tanggal_pinjam)');
                        } else {
                            $query->selectRaw('MONTH(tanggal_pinjam) as bulan, COUNT(*) as total')
                                ->groupByRaw('MONTH(tanggal_pinjam)');
                        }
                    })
                    ->whereYear('tanggal_pinjam', $tahunSekarang)
                    ->get()
                    ->keyBy('bulan');

                $data = $rentangBulan->map(function ($bulan) use ($peminjaman) {
                    return (int) ($peminjaman[$bulan]->total ?? 0);
                })->values()->toArray();
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        });

        return response()->json($hasil);
    }

    protected function susunAktivitasTerbaru(): Collection
    {
        // ------------------------------------------------------------------------------------
        // OPTIMASI PERFORMA:
        // Kita menggunakan `latest('id')` alih-alih `latest('created_at')` untuk Transaksi
        // dan Peminjaman karena ID adalah Primary Key yang sudah ter-index secara otomatis.
        // Ini mencegah terjadinya Full Table Scan saat data sudah mencapai ribuan baris,
        // sehingga query berjalan dalam O(1) time (secepat kilat).
        // ------------------------------------------------------------------------------------

        $transaksi = Transaksi::query()
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
            ->latest('id') // Optimasi: Menggunakan ID untuk pengurutan tercepat
            ->limit(15)
            ->get()
            ->map(function (Transaksi $item): array {
                // Menentukan waktu kejadian untuk ditampilkan di timeline
                $waktu = $item->created_at instanceof Carbon
                    ? $item->created_at
                    : Carbon::parse($item->tanggal_transaksi);

                /** @var string $namaBarang */
                $namaBarang = $item->barang ? $item->barang->nama : '-';
                /** @var string $namaPengguna */
                $namaPengguna = $item->pengguna ? $item->pengguna->nama : 'Admin';

                // Format kembalian array yang seragam untuk di-render di Blade
                return [
                    'waktu' => $waktu,
                    'tanggal_label' => $waktu->format('d M Y H:i'),
                    'jenis' => $item->jenis,
                    'barang' => $namaBarang,
                    'keterangan' => match ($item->jenis) {
                        'masuk' => 'Barang masuk '.$item->jumlah.' item oleh '.$namaPengguna,
                        default => 'Barang keluar '.$item->jumlah.' item'
                            .($item->alasan_keluar ? ' · alasan: '.str_replace('_', ' ', $item->alasan_keluar) : ''),
                    },
                ];
            });

        $peminjaman = Peminjaman::query()
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
            ->latest('id') // Optimasi: Menggunakan ID untuk pengurutan tercepat
            ->limit(15)
            ->get()
            ->map(function (Peminjaman $item): array {
                $waktu = $item->created_at instanceof Carbon
                    ? $item->created_at
                    : Carbon::parse($item->tanggal_pinjam.' '.$item->waktu_pinjam);

                return [
                    'waktu' => $waktu,
                    'tanggal_label' => $waktu->format('d M Y H:i'),
                    'jenis' => 'dipinjam',
                    'barang' => $this->ringkasBarangPeminjaman($item),
                    'keterangan' => 'Peminjaman oleh '.$item->nama_peminjam
                        .' · '.$item->detailPeminjaman->count().' item',
                ];
            });

        // ------------------------------------------------------------------------------------
        // OPTIMASI PERFORMA:
        // Untuk tabel Pengembalian, kita membatasi kolom yang di-select,
        // menggunakan index baru `idx_detail_peminjaman_waktu_kembali` untuk pencarian
        // waktu_kembali dengan metode `latest()`.
        // ------------------------------------------------------------------------------------
        $pengembalian = DetailPeminjaman::query()
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
            ->latest('waktu_kembali') // Didukung oleh indeks baru
            ->limit(15)
            ->get()
            ->map(function (DetailPeminjaman $item): array {
                $waktu = $item->waktu_kembali instanceof Carbon
                    ? $item->waktu_kembali
                    : Carbon::parse($item->waktu_kembali);

                /** @var string $namaBarang */
                $namaBarang = $item->barang ? $item->barang->nama : '-';
                /** @var string $namaPeminjam */
                $namaPeminjam = $item->peminjaman ? $item->peminjaman->nama_peminjam : '-';

                return [
                    'waktu' => $waktu,
                    'tanggal_label' => $waktu->format('d M Y H:i'),
                    'jenis' => 'kembali',
                    'barang' => $namaBarang,
                    'keterangan' => 'Dikembalikan oleh '.$namaPeminjam
                        .($item->kondisi_kembali !== null ? ' · kondisi '.$item->kondisi_kembali.'%' : ''),
                ];
            });

        // ------------------------------------------------------------------------------------
        // MENGGABUNGKAN KOLEKSI:
        // Ketiga data disatukan di level memori (karena masing-masing sudah di-limit agar ringan),
        // kemudian diurutkan dari yang paling baru dan dibatasi 15 item teratas saja.
        // Ini menjamin RAM (memori) server tidak akan penuh (out of memory).
        // ------------------------------------------------------------------------------------
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
            ->map(function ($detail) {
                return $detail->getAttribute('barang')?->nama;
            })
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

        return $namaBarang->take(2)->implode(', ').' +'.($namaBarang->count() - 2).' lagi';
    }
}
