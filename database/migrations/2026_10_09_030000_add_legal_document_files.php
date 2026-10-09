<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('persyaratan_legal', fn (Blueprint $table) => $table->json('file_jenis_berkas')->nullable());
    }
    public function down(): void
    {
        Schema::table('persyaratan_legal', fn (Blueprint $table) => $table->dropColumn('file_jenis_berkas'));
    }
};
