<?php

namespace App\Models;

use App\Helpers\KondisiHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \App\Models\Kategori|null $kategori
 * @property \App\Models\Merek|null $merek
 * @property \App\Models\Lokasi|null $lokasi
 */
class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'nama',
        'kategori_id',
        'merek_id',
        'lokasi_id',
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
        // Mengembalikan nama merek atau 'Lainnya' jika tidak ada merek yang terkait
        return $this->merek ? $this->merek->nama : 'Lainnya';
    }

    public function getLabelLokasiAttribute(): string
    {
        // Mengembalikan nama lokasi atau 'Lainnya' jika tidak ada lokasi yang terkait
        return $this->lokasi ? $this->lokasi->nama : 'Lainnya';
    }

    public function getLabelKondisiStokAttribute(): string
    {
        return KondisiHelper::label((int) $this->kondisi_stok);
    }

    public function getKondisiEfektifAttribute(): int
    {
        if ($this->tipe === 'aset') {
            return (int) round((float) ($this->rata_kondisi_unit ?? 0));
        }

        $totalQty = $this->qty_tersedia + $this->qty_dipinjam + $this->qty_rusak;

        if ($totalQty === 0) {
            return (int) ($this->kondisi_stok ?? 100);
        }

        $kondisi = (($this->qty_tersedia + $this->qty_dipinjam) * ($this->kondisi_stok ?? 100)) / $totalQty;

        return (int) round($kondisi);
    }
}
