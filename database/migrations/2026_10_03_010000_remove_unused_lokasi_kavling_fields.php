<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['stt_tampil', 'is_cluster', 'header'],
            fn ($column) => Schema::hasColumn('lokasi_kavling', $column)
        ));

        if ($columns) {
            Schema::table('lokasi_kavling', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    public function down(): void
    {
        Schema::table('lokasi_kavling', function (Blueprint $table) {
            $table->integer('stt_tampil')->default(0);
            $table->boolean('is_cluster')->default(false);
            $table->string('header', 100)->default('');
        });
    }
};
