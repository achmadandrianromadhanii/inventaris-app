<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \App\Models\Peminjaman|null $peminjaman
 * @property \App\Models\Barang|null $barang
 * @property \App\Models\UnitBarang|null $unitBarang
 * @property int|null $kondisi_awal
 */
class DetailPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'detail_peminjaman';

    protected $fillable = [
        'peminjaman_id',
        'barang_id',
        'unit_barang_id',
        'jumlah',
        'status_item',
        'kondisi_awal', // Menambahkan kondisi_awal agar bisa disimpan ke database (Bug Fix)
        'waktu_kembali',
        'kondisi_kembali',
        'catatan_kembali',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'kondisi_kembali' => 'integer',
        'waktu_kembali' => 'datetime',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function unitBarang(): BelongsTo
    {
        return $this->belongsTo(UnitBarang::class, 'unit_barang_id');
    }
}
