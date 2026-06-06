<?php

namespace App\Services;

use App\Models\Barang;
use Illuminate\Support\Str;

class UnitBarangService
{
    public function buatUnit(
        Barang $barang,
        int $jumlah,
        int $kondisiAwal,
        ?array $serials = null,
        ?array $kondisis = null
    ): void {
        $prefix = $this->buatPrefix($barang);
        $nomorAwal = $barang->unitBarang()->count();

        for ($i = 1; $i <= $jumlah; $i++) {
            $urutan = $nomorAwal + $i;

            $kondisi = isset($kondisis[$i - 1])
                ? (int) $kondisis[$i - 1]
                : $kondisiAwal;

            $status = $kondisi <= 34 ? 'rusak' : 'tersedia';

            $serial = isset($serials[$i - 1]) && trim((string) $serials[$i - 1]) !== ''
                ? trim((string) $serials[$i - 1])
                : null;

            $barang->unitBarang()->create([
                'nomor_unit' => $prefix.'-'.str_pad((string) $urutan, 3, '0', STR_PAD_LEFT),
                'serial_number' => $serial,
                'kondisi' => $kondisi,
                'status' => $status,
                'catatan' => null,
            ]);
        }
    }

    protected function buatPrefix(Barang $barang): string
    {
        $barang->loadMissing('kategori:id,nama');

        $namaKategori = $barang->kategori ? $barang->kategori->nama : 'UNT';
        $clean = preg_replace('/[^A-Za-z0-9]/', '', Str::upper($namaKategori)) ?: 'UNT';

        return str_pad(Str::substr($clean, 0, 3), 3, 'X');
    }
}
