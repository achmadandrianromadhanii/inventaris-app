<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_peminjaman', function (Blueprint $table) {
            $table->unsignedTinyInteger('kondisi_awal')->nullable()->after('status_item');
        });
    }

    public function down(): void
    {
        Schema::table('detail_peminjaman', function (Blueprint $table) {
            $table->dropColumn('kondisi_awal');
        });
    }
};
