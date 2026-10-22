<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->date('tanggal_masuk_bank')->nullable();
            $table->string('petugas_bank')->nullable();
            $table->string('tanda_terima_bank')->nullable();
            $table->text('tindak_lanjut_bank')->nullable();
            $table->dateTime('tanggal_tindak_lanjut_bank')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->dropColumn(['tanggal_masuk_bank', 'petugas_bank', 'tanda_terima_bank', 'tindak_lanjut_bank', 'tanggal_tindak_lanjut_bank']);
        });
    }
};
