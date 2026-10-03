<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_hold', function (Blueprint $table) {
            $table->unsignedBigInteger('id_bank')->nullable();
            $table->unsignedBigInteger('id_metode_bayar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_hold', function (Blueprint $table) {
            $table->dropColumn(['id_bank', 'id_metode_bayar']);
        });
    }
};
