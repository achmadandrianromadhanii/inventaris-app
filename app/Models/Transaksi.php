<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \App\Models\Barang|null $barang
 * @property \App\Models\UnitBarang|null $unitBarang
 * @property \App\Models\Lokasi|null $lokasiTujuan
 * @property \App\Models\Pengguna|null $pengguna
 */
class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'jenis',
        'barang_id',
        'unit_barang_id',
        'jumlah',
        'alasan_keluar',
        'lokasi_tujuan_id',
        'sumber_tujuan',
        'tanggal_transaksi',
        'kondisi_saat_itu',
        'catatan',
        'pengguna_id',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'kondisi_saat_itu' => 'integer',
        'tanggal_transaksi' => 'date',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function unitBarang(): BelongsTo
    {
        return $this->belongsTo(UnitBarang::class, 'unit_barang_id');
    }

    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_tujuan_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
