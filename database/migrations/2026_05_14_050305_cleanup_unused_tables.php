<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus tabel 'users' — tidak digunakan, auth pakai tabel 'pengguna'
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        // Hapus index redundan 'kode_pinjam' pada tabel peminjaman
        // (sudah ada unique constraint, index terpisah tidak perlu)
        if (Schema::hasTable('peminjaman')) {
            Schema::table('peminjaman', function (Blueprint $table) {
                $indexes = collect(
                    Schema::getIndexes('peminjaman')
                )->pluck('name')->all();

                // Hanya drop jika index non-unique terpisah bernama 'peminjaman_kode_pinjam_index' ada
                if (in_array('peminjaman_kode_pinjam_index', $indexes)) {
                    $table->dropIndex('peminjaman_kode_pinjam_index');
                }
            });
        }
    }

    public function down(): void
    {
        // Buat ulang tabel users (scaffold bawaan Laravel)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
