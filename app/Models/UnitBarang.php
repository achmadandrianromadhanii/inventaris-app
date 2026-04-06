<?php

namespace App\Models;

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
        return match (true) {
            $this->kondisi >= 80 => 'Baik',
            $this->kondisi >= 60 => 'Lumayan',
            $this->kondisi >= 35 => 'Rusak',
            default => 'Rusak Parah',
        };
    }

    public function getWarnaKondisiAttribute(): string
    {
        return match (true) {
            $this->kondisi >= 80 => 'emerald',
            $this->kondisi >= 60 => 'blue',
            $this->kondisi >= 35 => 'amber',
            default => 'red',
        };
    }
}
