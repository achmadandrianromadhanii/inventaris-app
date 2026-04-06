<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangKeluarRequest;
use App\Http\Requests\BarangMasukRequest;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merek;
use App\Models\Transaksi;
use App\Models\UnitBarang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    public function masuk(Request $request): View
    {
        $filterTanggal = $this->resolveFilterTanggal($request);

        $riwayat = Transaksi::query()
            ->with([
                'barang:id,nama,tipe',
                'pengguna:id,nama',
            ])
            ->where('jenis', 'masuk')
            ->whereDate('tanggal_transaksi', '>=', $filterTanggal['dari'])
            ->whereDate('tanggal_transaksi', '<=', $filterTanggal['sampai'])
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('transaksi.masuk', [
            'kategori' => Kategori::query()->orderBy('nama')->get(['id', 'nama']),
            'merek' => Merek::query()->orderBy('nama')->get(['id', 'nama']),
            'lokasi' => Lokasi::query()->orderBy('nama')->get(['id', 'nama']),
            'riwayat' => $riwayat,
            'filterTanggal' => $filterTanggal,
            'barangTerpilih' => $this->getBarangTerpilihMasuk(),
        ]);
    }

    public function simpanMasuk(BarangMasukRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $penggunaId = (int) $request->user()->getAuthIdentifier();

        DB::transaction(function () use ($data, $penggunaId) {
            if ($data['mode_barang'] === 'baru') {
                $barang = $this->buatBarangBaruDariTransaksi($data);
            } else {
                $barang = Barang::query()
                    ->with('kategori:id,nama')
                    ->lockForUpdate()
                    ->findOrFail($data['barang_id']);

                $barang->update([
                    'aktif' => true,
                ]);

                $this->tambahBarangExistingDariTransaksi($barang, $data);
            }

            Transaksi::create([
                'jenis' => 'masuk',
                'barang_id' => $barang->id,
                'unit_barang_id' => null,
                'jumlah' => (int) $data['jumlah_masuk'],
                'alasan_keluar' => null,
                'lokasi_tujuan_id' => null,
                'lokasi_tujuan_manual' => null,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'kondisi_saat_itu' => (int) $data['kondisi_saat_itu'],
                'catatan' => $data['catatan'] ?? null,
                'pengguna_id' => $penggunaId,
            ]);
        });

        return back()->with('sukses', 'Barang masuk berhasil dicatat.');
    }

    public function keluar(Request $request): View
    {
        $filterTanggal = $this->resolveFilterTanggal($request);

        $riwayat = Transaksi::query()
            ->with([
                'barang:id,nama,tipe',
                'pengguna:id,nama',
                'unitBarang:id,nomor_unit',
                'lokasiTujuan:id,nama',
            ])
            ->where('jenis', 'keluar')
            ->whereDate('tanggal_transaksi', '>=', $filterTanggal['dari'])
            ->whereDate('tanggal_transaksi', '<=', $filterTanggal['sampai'])
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('transaksi.keluar', [
            'lokasi' => Lokasi::query()->orderBy('nama')->get(['id', 'nama']),
            'riwayat' => $riwayat,
            'filterTanggal' => $filterTanggal,
            'barangTerpilih' => $this->getBarangTerpilihKeluar(),
        ]);
    }

    public function simpanKeluar(BarangKeluarRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $penggunaId = (int) $request->user()->getAuthIdentifier();

        DB::transaction(function () use ($data, $penggunaId) {
            $barang = Barang::query()
                ->with('kategori:id,nama')
                ->lockForUpdate()
                ->findOrFail($data['barang_id']);

            if ($data['alasan_keluar'] === 'pindah_lokasi') {
                $this->prosesKeluarPindahLokasi($barang, $data, $penggunaId);
                return;
            }

            if ($barang->tipe === 'aset') {
                $this->prosesKeluarAset($barang, $data, $penggunaId);
                return;
            }

            $this->prosesKeluarStok($barang, $data, $penggunaId);
        });

        return back()->with('sukses', 'Barang keluar berhasil dicatat.');
    }

    protected function buatBarangBaruDariTransaksi(array $data): Barang
    {
        $barang = Barang::create([
            'nama' => $data['nama'],
            'kategori_id' => $data['kategori_id'],
            'merek_id' => $data['merek_id'] ?? null,
            'merek_manual' => !empty($data['merek_id']) ? null : ($data['merek_manual'] ?? null),
            'lokasi_id' => $data['lokasi_id'] ?? null,
            'lokasi_manual' => !empty($data['lokasi_id']) ? null : ($data['lokasi_manual'] ?? null),
            'tipe' => $data['tipe'],
            'spesifikasi' => $data['spesifikasi'] ?? null,
            'tahun_pengadaan' => $data['tahun_pengadaan'] ?? null,
            'qty_total' => $data['tipe'] === 'stok' ? (int) $data['jumlah_masuk'] : 0,
            'qty_tersedia' => $data['tipe'] === 'stok' ? (int) $data['jumlah_masuk'] : 0,
            'qty_dipinjam' => 0,
            'qty_rusak' => 0,
            'qty_keluar' => 0,
            'kondisi_stok' => $data['tipe'] === 'stok' ? (int) $data['kondisi_saat_itu'] : 100,
            'aktif' => true,
            'catatan' => $data['catatan'] ?? null,
        ]);

        if ($barang->tipe === 'aset') {
            $barang->load('kategori:id,nama');

            $this->buatUnitBarang(
                barang: $barang,
                jumlah: (int) $data['jumlah_masuk'],
                kondisi: (int) $data['kondisi_saat_itu'],
                serialRaw: $data['serial_number_list'] ?? null
            );
        }

        return $barang;
    }

    protected function tambahBarangExistingDariTransaksi(Barang $barang, array $data): void
    {
        if ($barang->tipe === 'aset') {
            $barang->loadMissing('kategori:id,nama');

            $this->buatUnitBarang(
                barang: $barang,
                jumlah: (int) $data['jumlah_masuk'],
                kondisi: (int) $data['kondisi_saat_itu'],
                serialRaw: $data['serial_number_list'] ?? null
            );

            return;
        }

        $barang->update([
            'qty_total' => (int) $barang->qty_total + (int) $data['jumlah_masuk'],
            'qty_tersedia' => (int) $barang->qty_tersedia + (int) $data['jumlah_masuk'],
            'kondisi_stok' => (int) $data['kondisi_saat_itu'],
            'aktif' => true,
        ]);
    }

    protected function prosesKeluarPindahLokasi(Barang $barang, array $data, int $penggunaId): void
    {
        $lokasiTujuanId = $data['lokasi_tujuan_id'] ?? null;
        $lokasiTujuanManual = !empty($lokasiTujuanId) ? null : ($data['lokasi_tujuan_manual'] ?? null);

        $barang->update([
            'lokasi_id' => $lokasiTujuanId,
            'lokasi_manual' => $lokasiTujuanManual,
        ]);

        if ($barang->tipe === 'aset') {
            $unitAktifQuery = $barang->unitBarang()->where('status', '!=', 'keluar');

            $jumlah = max(1, (clone $unitAktifQuery)->count());
            $avgKondisi = (clone $unitAktifQuery)->avg('kondisi');
            $kondisi = (int) round((float) ($avgKondisi ?? 0));
        } else {
            $jumlah = max(1, (int) $barang->qty_total);
            $kondisi = (int) $barang->kondisi_stok;
        }

        Transaksi::create([
            'jenis' => 'keluar',
            'barang_id' => $barang->id,
            'unit_barang_id' => null,
            'jumlah' => $jumlah,
            'alasan_keluar' => 'pindah_lokasi',
            'lokasi_tujuan_id' => $lokasiTujuanId,
            'lokasi_tujuan_manual' => $lokasiTujuanManual,
            'sumber_tujuan' => null,
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'kondisi_saat_itu' => $kondisi,
            'catatan' => $data['catatan'] ?? null,
            'pengguna_id' => $penggunaId,
        ]);
    }

    protected function prosesKeluarAset(Barang $barang, array $data, int $penggunaId): void
    {
        $unitIds = collect($data['unit_barang_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $unitList = UnitBarang::query()
            ->where('barang_id', $barang->id)
            ->whereIn('id', $unitIds)
            ->whereIn('status', ['tersedia', 'rusak'])
            ->lockForUpdate()
            ->get();

        if ($unitIds->isEmpty() || $unitList->count() !== $unitIds->count()) {
            throw ValidationException::withMessages([
                'unit_barang_ids' => 'Beberapa unit tidak valid atau sudah berubah status.',
            ]);
        }

        $statusAkhir = match ($data['alasan_keluar']) {
            'dibuang', 'hibah' => 'keluar',
            'lainnya' => $data['status_akhir'],
            default => 'keluar',
        };

        foreach ($unitList as $unit) {
            $unit->update([
                'status' => $statusAkhir,
            ]);

            Transaksi::create([
                'jenis' => 'keluar',
                'barang_id' => $barang->id,
                'unit_barang_id' => $unit->id,
                'jumlah' => 1,
                'alasan_keluar' => $data['alasan_keluar'],
                'lokasi_tujuan_id' => null,
                'lokasi_tujuan_manual' => null,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'kondisi_saat_itu' => (int) $unit->kondisi,
                'catatan' => $data['catatan'] ?? null,
                'pengguna_id' => $penggunaId,
            ]);
        }

        $semuaKeluar = !$barang->unitBarang()
            ->where('status', '!=', 'keluar')
            ->exists();

        if ($semuaKeluar) {
            $barang->update([
                'aktif' => false,
            ]);
        }
    }

    protected function prosesKeluarStok(Barang $barang, array $data, int $penggunaId): void
    {
        $jumlah = (int) $data['jumlah'];
        $alasan = $data['alasan_keluar'];

        if ($jumlah < 1 || $jumlah > (int) $barang->qty_tersedia) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah stok tidak valid atau melebihi stok tersedia.',
            ]);
        }

        $qtyTersedia = (int) $barang->qty_tersedia;
        $qtyKeluar = (int) $barang->qty_keluar;
        $qtyRusak = (int) $barang->qty_rusak;

        if (in_array($alasan, ['dibuang', 'hibah'], true)) {
            $qtyTersedia -= $jumlah;
            $qtyKeluar += $jumlah;
        }

        if ($alasan === 'lainnya') {
            $statusAkhir = $data['status_akhir'];

            if ($statusAkhir === 'keluar') {
                $qtyTersedia -= $jumlah;
                $qtyKeluar += $jumlah;
            }

            if ($statusAkhir === 'rusak') {
                $qtyTersedia -= $jumlah;
                $qtyRusak += $jumlah;
            }
        }

        $barang->update([
            'qty_tersedia' => max(0, $qtyTersedia),
            'qty_keluar' => max(0, $qtyKeluar),
            'qty_rusak' => max(0, $qtyRusak),
            'aktif' => ((int) $barang->qty_total - max(0, $qtyKeluar)) > 0,
        ]);

        Transaksi::create([
            'jenis' => 'keluar',
            'barang_id' => $barang->id,
            'unit_barang_id' => null,
            'jumlah' => $jumlah,
            'alasan_keluar' => $alasan,
            'lokasi_tujuan_id' => null,
            'lokasi_tujuan_manual' => null,
            'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'kondisi_saat_itu' => (int) $barang->kondisi_stok,
            'catatan' => $data['catatan'] ?? null,
            'pengguna_id' => $penggunaId,
        ]);
    }

    protected function buatUnitBarang(Barang $barang, int $jumlah, int $kondisi, ?string $serialRaw = null): void
    {
        $serials = collect(preg_split('/\r\n|\r|\n/', (string) $serialRaw))
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values();

        $prefix = mb_strtoupper(mb_substr($barang->kategori->nama, 0, 3));
        $nomorAwal = $barang->unitBarang()->count();
        $statusAwal = $kondisi <= 34 ? 'rusak' : 'tersedia';

        for ($i = 1; $i <= $jumlah; $i++) {
            $urutan = $nomorAwal + $i;

            $barang->unitBarang()->create([
                'nomor_unit' => $prefix . '-' . str_pad((string) $urutan, 3, '0', STR_PAD_LEFT),
                'serial_number' => $serials[$i - 1] ?? null,
                'kondisi' => $kondisi,
                'status' => $statusAwal,
                'catatan' => null,
            ]);
        }
    }

    protected function formatBarangAutocomplete(Barang $barang): array
    {
        $kondisi = $barang->tipe === 'aset'
            ? (int) round((float) ($barang->rata_kondisi_unit ?? 0))
            : (int) ($barang->kondisi_stok ?? 100);

        return [
            'id' => $barang->id,
            'nama' => $barang->nama,
            'tipe' => $barang->tipe,
            'kategori' => $barang->kategori?->nama,
            'merek' => $barang->label_merek,
            'lokasi' => $barang->label_lokasi,
            'kondisi' => $kondisi,
            'label_kondisi' => $this->labelKondisi($kondisi),
            'qty_tersedia' => $barang->tipe === 'stok' ? (int) $barang->qty_tersedia : null,
            'unit_tersedia' => $barang->tipe === 'aset' ? (int) $barang->unit_tersedia_count : null,
        ];
    }

    protected function formatBarangAutocompleteKeluar(Barang $barang): array
    {
        $kondisi = $barang->tipe === 'aset'
            ? (int) round((float) ($barang->rata_kondisi_unit ?? 0))
            : (int) ($barang->kondisi_stok ?? 100);

        return [
            'id' => $barang->id,
            'nama' => $barang->nama,
            'tipe' => $barang->tipe,
            'kategori' => $barang->kategori?->nama,
            'merek' => $barang->label_merek,
            'lokasi' => $barang->label_lokasi,
            'kondisi' => $kondisi,
            'label_kondisi' => $this->labelKondisi($kondisi),
            'qty_tersedia' => (int) $barang->qty_tersedia,
            'unit_tersedia' => (int) ($barang->unit_tersedia_count ?? 0),
            'unit_rusak' => (int) ($barang->unit_rusak_count ?? 0),
        ];
    }

    protected function labelKondisi(int $kondisi): string
    {
        return match (true) {
            $kondisi >= 80 => 'Baik',
            $kondisi >= 60 => 'Lumayan',
            $kondisi >= 35 => 'Rusak',
            default => 'Rusak Parah',
        };
    }

    protected function resolveFilterTanggal(Request $request): array
    {
        return [
            'dari' => (string) $request->query('dari', now()->subDays(30)->format('Y-m-d')),
            'sampai' => (string) $request->query('sampai', now()->format('Y-m-d')),
        ];
    }

    protected function getBarangTerpilihMasuk(): ?array
    {
        $barangId = old('barang_id');

        if (!$barangId) {
            return null;
        }

        $barang = Barang::query()
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang as unit_tersedia_count' => fn($sub) => $sub->where('status', 'tersedia'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->find($barangId);

        return $barang ? $this->formatBarangAutocomplete($barang) : null;
    }

    protected function getBarangTerpilihKeluar(): ?array
    {
        $barangId = old('barang_id');

        if (!$barangId) {
            return null;
        }

        $barang = Barang::query()
            ->with([
                'kategori:id,nama',
                'merek:id,nama',
                'lokasi:id,nama',
            ])
            ->withCount([
                'unitBarang as unit_tersedia_count' => fn($sub) => $sub->where('status', 'tersedia'),
                'unitBarang as unit_rusak_count' => fn($sub) => $sub->where('status', 'rusak'),
            ])
            ->withAvg('unitBarang as rata_kondisi_unit', 'kondisi')
            ->find($barangId);

        return $barang ? $this->formatBarangAutocompleteKeluar($barang) : null;
    }
}
