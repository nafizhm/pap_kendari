<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = ['pengajuan_hold', 'pengajuan_hold_tempo', 'customer'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                for ($i = 1; $i <= 5; $i++) {
                    foreach (['kontak_darurat', 'nama_pemilik_kontak_darurat', 'keterangan_kontak_darurat'] as $field) {
                        $table->string($field . '_' . $i)->nullable();
                    }
                }
            });
            DB::table($name)->update([
                'kontak_darurat_1' => DB::raw('no_telp_saudara'),
                'nama_pemilik_kontak_darurat_1' => DB::raw('nama_saudara'),
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                for ($i = 1; $i <= 5; $i++) {
                    foreach (['kontak_darurat', 'nama_pemilik_kontak_darurat', 'keterangan_kontak_darurat'] as $field) {
                        $table->dropColumn($field . '_' . $i);
                    }
                }
            });
        }
    }
};
