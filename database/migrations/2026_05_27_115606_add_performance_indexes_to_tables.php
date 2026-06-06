<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menambahkan indeks untuk optimasi performa sorting (latest) di Dashboard
        Schema::table('transaksi', function (Blueprint $table) {
            $table->index('created_at', 'idx_transaksi_created_at');
        });

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->index('created_at', 'idx_peminjaman_created_at');
        });

        Schema::table('detail_peminjaman', function (Blueprint $table) {
            $table->index('waktu_kembali', 'idx_detail_peminjaman_waktu_kembali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex('idx_transaksi_created_at');
        });

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropIndex('idx_peminjaman_created_at');
        });

        Schema::table('detail_peminjaman', function (Blueprint $table) {
            $table->dropIndex('idx_detail_peminjaman_waktu_kembali');
        });
    }
};
