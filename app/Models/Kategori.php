<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }

    public static function getCachedDropdown()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('kategori_dropdown', function () {
            return self::query()->orderBy('nama')->get(['id', 'nama']);
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('kategori_dropdown'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('kategori_dropdown'));
    }
}
