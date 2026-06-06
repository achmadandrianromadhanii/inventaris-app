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
        Schema::table('barang', function (Blueprint $table) {
            // Menghapus kolom manual yang berlebihan untuk menjaga kebersihan data dan memaksa penggunaan relasi master
            $table->dropColumn(['merek_manual', 'lokasi_manual']);
        });

        Schema::table('transaksi', function (Blueprint $table) {
            // Menghapus lokasi tujuan manual yang berlebihan
            $table->dropColumn('lokasi_tujuan_manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->string('merek_manual', 100)->nullable();
            $table->string('lokasi_manual', 100)->nullable();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('lokasi_tujuan_manual', 100)->nullable();
        });
    }
};
