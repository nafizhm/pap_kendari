<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_akad', function (Blueprint $table) {
            $table->string('foto_akad')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_akad', function (Blueprint $table) {
            $table->dropColumn('foto_akad');
        });
    }
};
