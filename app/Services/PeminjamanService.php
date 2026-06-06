<?php

namespace App\Services;

use App\Events\PeminjamanUpdated;
use App\Models\Barang;
use App\Models\DetailPeminjaman;
use App\Models\Peminjaman;
use App\Models\UnitBarang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeminjamanService
{
    public function normalizeItems(array $items): Collection
    {
        return collect($items)
            ->map(function (array $item) {
                return [
                    'barang_id' => (int) ($item['barang_id'] ?? 0),
                    'jumlah' => max(1, (int) ($item['jumlah'] ?? 1)),
                ];
            })
            ->filter(fn (array $item) => $item['barang_id'] > 0)
            ->groupBy('barang_id')
            ->map(function (Collection $group, int|string $barangId) {
                return [
                    'barang_id' => (int) $barangId,
                    'jumlah' => (int) $group->sum('jumlah'),
                ];
            })
            ->values();
    }

    public function buatPeminjamanPublik(array $data, array $items): array
    {
        $itemsNormalized = $this->normalizeItems($items);

        if ($itemsNormalized->isEmpty()) {
            throw ValidationException::withMessages([
                'items_json' => 'Tambahkan minimal satu barang ke daftar.',
            ]);
        }

        // [UPDATE] Anti-Spam: Cooldown 60 detik per nama peminjam.
        // Jika siswa dengan nama yang sama baru saja mengajukan peminjaman kurang dari 60 detik yang lalu,
        // tolak permintaan ini agar data tidak meledak karena klik berulang/spam.
        $namaPeminjam = trim($data['nama_peminjam']);
        $sudahAdaBaru = Peminjaman::query()
            ->where('nama_peminjam', $namaPeminjam)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->exists();

        if ($sudahAdaBaru) {
            throw ValidationException::withMessages([
                'nama_peminjam' => 'Anda baru saja mengajukan peminjaman. Silakan tunggu 1 menit sebelum mengajukan lagi.',
            ]);
        }

        $ringkasanItems = [];
        $peminjamanId = null;

        DB::transaction(function () use ($data, $itemsNormalized, &$ringkasanItems, &$peminjamanId) {
            $peminjaman = Peminjaman::query()->create([
                'nama_peminjam' => $data['nama_peminjam'],
                'kelas_id' => $data['kelas_id'],
                'jurusan_id' => $data['jurusan_id'],
                'no_hp' => $data['no_hp'] ?? null,
                'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
                'tanggal_pinjam' => now()->toDateString(),
                'waktu_pinjam' => now()->format('H:i:s'),
                'status' => 'aktif',
                'catatan' => $data['catatan'] ?? null,
                'pengguna_id' => null,
            ]);

            foreach ($itemsNormalized as $item) {
                $barang = Barang::query()
                    ->lockForUpdate()
                    ->findOrFail($item['barang_id']);

                if (! $barang->aktif) {
                    throw ValidationException::withMessages([
                        'items_json' => "Barang '{$barang->nama}' tidak aktif dan tidak bisa dipinjam.",
                    ]);
                }

                if ($barang->tipe === 'aset') {
                    $jumlah = (int) $item['jumlah'];

                    $units = UnitBarang::query()
                        ->where('barang_id', $barang->id)
                        ->where('status', 'tersedia')
                        ->orderByDesc('kondisi')
                        ->orderBy('nomor_unit')
                        ->lockForUpdate()
                        ->limit($jumlah)
                        ->get();

                    if ($units->count() < $jumlah) {
                        throw ValidationException::withMessages([
                            'items_json' => "Unit tersedia untuk '{$barang->nama}' tidak mencukupi.",
                        ]);
                    }

                    foreach ($units as $unit) {
                        $this->pinjamUnitAset($peminjaman, $barang, $unit);

                        $ringkasanItems[] = [
                            'barang' => $barang->nama,
                            'unit_qty' => $unit->nomor_unit,
                        ];
                    }

                    continue;
                }

                $jumlah = (int) $item['jumlah'];

                if ($jumlah > (int) $barang->qty_tersedia) {
                    throw ValidationException::withMessages([
                        'items_json' => "Stok tersedia untuk '{$barang->nama}' tidak mencukupi.",
                    ]);
                }

                $this->pinjamBarangStok($peminjaman, $barang, $jumlah);

                $ringkasanItems[] = [
                    'barang' => $barang->nama,
                    'unit_qty' => 'Qty '.$jumlah,
                ];
            }

            $peminjamanId = $peminjaman->id;
        });

        PeminjamanUpdated::dispatch(
            aksi: 'peminjaman_baru',
            pesan: "Peminjaman baru atas nama {$data['nama_peminjam']} — ".count($ringkasanItems).' item',
            data: ['peminjaman_id' => $peminjamanId],
        );

        return [
            'peminjaman_id' => $peminjamanId,
            'items' => $ringkasanItems,
        ];
    }

    public function prosesPengembalianDetail(
        DetailPeminjaman $detail,
        int $kondisiKembali,
        ?string $catatanKembali = null
    ): void {
        if ($detail->status_item !== 'dipinjam') {
            throw ValidationException::withMessages([
                'detail_id' => 'Item ini sudah dikembalikan.',
            ]);
        }

        DB::transaction(function () use ($detail, $kondisiKembali, $catatanKembali) {
            $detail = DetailPeminjaman::query()
                ->lockForUpdate()
                ->findOrFail($detail->id);

            if ($detail->status_item !== 'dipinjam') {
                throw ValidationException::withMessages([
                    'detail_id' => 'Item ini sudah dikembalikan.',
                ]);
            }

            $detail->update([
                'status_item' => 'dikembalikan',
                'waktu_kembali' => now(),
                'kondisi_kembali' => $kondisiKembali,
                'catatan_kembali' => $catatanKembali,
            ]);

            if ($detail->unit_barang_id) {
                $unit = UnitBarang::query()
                    ->lockForUpdate()
                    ->findOrFail($detail->unit_barang_id);

                $unit->update([
                    'kondisi' => $kondisiKembali,
                    'status' => $kondisiKembali <= 34 ? 'rusak' : 'tersedia',
                ]);
            } else {
                $barang = Barang::query()
                    ->lockForUpdate()
                    ->findOrFail($detail->barang_id);

                $barang->update([
                    'qty_dipinjam' => max(0, (int) $barang->qty_dipinjam - (int) $detail->jumlah),
                    'qty_tersedia' => (int) $barang->qty_tersedia + (int) $detail->jumlah,
                ]);
            }

            $peminjaman = Peminjaman::query()
                ->lockForUpdate()
                ->findOrFail($detail->peminjaman_id);

            $masihAktif = DetailPeminjaman::query()
                ->where('peminjaman_id', $peminjaman->id)
                ->where('status_item', 'dipinjam')
                ->count();

            if ($masihAktif === 0) {
                $peminjaman->update([
                    'status' => 'selesai',
                ]);
            }
        });

        PeminjamanUpdated::dispatch(
            aksi: 'pengembalian',
            pesan: 'Item dikembalikan — kondisi '.$kondisiKembali.'%',
            data: ['detail_id' => $detail->id],
        );
    }

    protected function pinjamUnitAset(Peminjaman $peminjaman, Barang $barang, UnitBarang $unit): void
    {
        $unit->update([
            'status' => 'dipinjam',
        ]);

        DetailPeminjaman::query()->create([
            'peminjaman_id' => $peminjaman->id,
            'barang_id' => $barang->id,
            'unit_barang_id' => $unit->id,
            'jumlah' => 1,
            'status_item' => 'dipinjam',
            'kondisi_awal' => (int) $unit->kondisi,
            'waktu_kembali' => null,
            'kondisi_kembali' => null,
            'catatan_kembali' => null,
        ]);
    }

    protected function pinjamBarangStok(Peminjaman $peminjaman, Barang $barang, int $jumlah): void
    {
        $barang->update([
            'qty_dipinjam' => (int) $barang->qty_dipinjam + $jumlah,
            'qty_tersedia' => (int) $barang->qty_tersedia - $jumlah,
        ]);

        DetailPeminjaman::query()->create([
            'peminjaman_id' => $peminjaman->id,
            'barang_id' => $barang->id,
            'unit_barang_id' => null,
            'jumlah' => $jumlah,
            'status_item' => 'dipinjam',
            'kondisi_awal' => (int) $barang->kondisi_stok,
            'waktu_kembali' => null,
            'kondisi_kembali' => null,
            'catatan_kembali' => null,
        ]);
    }
}
