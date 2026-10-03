<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->string('no_tanda_terima', 100)->nullable()->after('tanggal');
            $table->string('diterima_dari')->nullable()->after('no_tanda_terima');
            $table->string('nama_penerima')->nullable()->after('diterima_dari');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn(['no_tanda_terima', 'diterima_dari', 'nama_penerima']);
        });
    }
};
