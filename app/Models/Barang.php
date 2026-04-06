<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'nama',
        'kategori_id',
        'merek_id',
        'merek_manual',
        'lokasi_id',
        'lokasi_manual',
        'tipe',
        'spesifikasi',
        'tahun_pengadaan',
        'qty_total',
        'qty_tersedia',
        'qty_dipinjam',
        'qty_rusak',
        'qty_keluar',
        'kondisi_stok',
        'aktif',
        'catatan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'qty_total' => 'integer',
        'qty_tersedia' => 'integer',
        'qty_dipinjam' => 'integer',
        'qty_rusak' => 'integer',
        'qty_keluar' => 'integer',
        'kondisi_stok' => 'integer',
        'tahun_pengadaan' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function merek(): BelongsTo
    {
        return $this->belongsTo(Merek::class, 'merek_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function unitBarang(): HasMany
    {
        return $this->hasMany(UnitBarang::class, 'barang_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'barang_id');
    }

    public function detailPeminjaman(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'barang_id');
    }

    public function getLabelMerekAttribute(): string
    {
        if ($this->merek?->nama) {
            return $this->merek->nama;
        }

        if (filled($this->merek_manual)) {
            return $this->merek_manual;
        }

        return 'Tidak Diketahui';
    }

    public function getLabelLokasiAttribute(): string
    {
        if ($this->lokasi?->nama) {
            return $this->lokasi->nama;
        }

        if (filled($this->lokasi_manual)) {
            return $this->lokasi_manual;
        }

        return 'Tidak Diketahui';
    }

    public function getLabelKondisiStokAttribute(): string
    {
        $kondisi = (int) $this->kondisi_stok;

        return match (true) {
            $kondisi >= 80 => 'Baik',
            $kondisi >= 60 => 'Lumayan',
            $kondisi >= 35 => 'Rusak',
            default => 'Rusak Parah',
        };
    }
}
