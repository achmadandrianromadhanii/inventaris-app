<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merek extends Model
{
    use HasFactory;

    protected $table = 'merek';

    protected $fillable = [
        'nama',
    ];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class, 'merek_id');
    }

    public static function getCachedDropdown()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('merek_dropdown', function () {
            return self::query()->orderBy('nama')->get(['id', 'nama']);
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('merek_dropdown'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('merek_dropdown'));
    }
}
