<?php

namespace App\Models;

use App\Helpers\KondisiHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitBarang extends Model
{
    use HasFactory;

    protected $table = 'unit_barang';

    protected $fillable = [
        'barang_id',
        'nomor_unit',
        'serial_number',
        'kondisi',
        'status',
        'catatan',
    ];

    protected $casts = [
        'kondisi' => 'integer',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class);
    }

    public function getLabelKondisiAttribute(): string
    {
        return KondisiHelper::label((int) $this->kondisi);
    }

    public function getWarnaKondisiAttribute(): string
    {
        return KondisiHelper::warna((int) $this->kondisi);
    }
}
