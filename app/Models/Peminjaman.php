<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \App\Models\Kelas|null $kelas
 * @property \App\Models\Jurusan|null $jurusan
 * @property \App\Models\Pengguna|null $pengguna
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetailPeminjaman>|null $detailPeminjaman
 */
class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'peminjaman';

    protected $fillable = [
        'nama_peminjam',
        'kelas_id',
        'jurusan_id',
        'no_hp',
        'mata_pelajaran',
        'tanggal_pinjam',
        'waktu_pinjam',
        'status',
        'catatan',
        'pengguna_id',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'peminjaman_id');
    }
}
