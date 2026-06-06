<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = [
        'nama',
    ];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'lokasi_id');
    }

    public function transaksiTujuan(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'lokasi_tujuan_id');
    }

    public static function getCachedDropdown()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('lokasi_dropdown', function () {
            return self::query()->orderBy('nama')->get(['id', 'nama']);
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('lokasi_dropdown'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('lokasi_dropdown'));
    }
}
