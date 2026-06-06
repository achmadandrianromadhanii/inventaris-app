<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Menjalankan proses seeder master data inti.
     * CATATAN UPDATE:
     * File DummyDataSeeder dan data dummy lainnya telah Dihapus Secara Permanen
     * untuk menjaga database tetap bersih (clean) 100% dan terhindar dari data sampah
     * saat di-deploy ke server (production).
     */
    public function run(): void
    {
        $this->call([
            KelasSeeder::class,    // Seeder Master Kelas
            JurusanSeeder::class,  // Seeder Master Jurusan
            KategoriSeeder::class, // Seeder Master Kategori
            MerekSeeder::class,    // Seeder Master Merek
            LokasiSeeder::class,   // Seeder Master Lokasi
            PenggunaSeeder::class, // Seeder Akun Admin Default
        ]);
    }
}
