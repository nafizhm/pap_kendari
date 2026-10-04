<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\LokasiKavlingController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LokasiPerumahanFieldsTest extends TestCase
{
    public function test_location_can_be_created_and_updated_after_obsolete_columns_are_removed(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            foreach (['nama_kavling', 'nama_singkat', 'alamat', 'no_kwitansi', 'no_bast', 'no_ppjb'] as $field) {
                $table->string($field);
            }
            $table->integer('urutan');
            $table->integer('reset_nomor');
            $table->integer('stt_tampil')->default(0);
            $table->boolean('is_cluster')->default(false);
            $table->string('header')->default('');
        });
        Schema::create('lokasi_kavling_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
            $table->integer('id_perusahaan');
        });

        $payload = [
            'nama_kavling' => 'Perumahan A', 'nama_singkat' => 'PA', 'alamat' => 'Kendari',
            'urutan' => 1, 'reset_nomor' => 1, 'id_perusahaan' => [1],
            'no_kwitansi' => '0000/KW/MM/YYYY', 'no_bast' => '0000/BAST/MM/YYYY',
            'no_ppjb' => '0000/PPJB/MM/YYYY',
        ];
        DB::table('lokasi_kavling')->insert(collect($payload)->except('id_perusahaan')->all());
        $migration = require database_path('migrations/2026_10_03_010000_remove_unused_lokasi_kavling_fields.php');
        $migration->up();
        $migration->up();
        foreach (['stt_tampil', 'is_cluster', 'header'] as $column) {
            $this->assertFalse(Schema::hasColumn('lokasi_kavling', $column));
        }
        $this->assertDatabaseHas('lokasi_kavling', ['id' => 1, 'nama_kavling' => 'Perumahan A']);

        Route::post('/_test/lokasi', [LokasiKavlingController::class, 'store']);
        Route::put('/_test/lokasi/{id}', [LokasiKavlingController::class, 'update']);
        $this->postJson('/_test/lokasi', $payload)->assertOk()->assertJson(['success' => true]);
        $payload['nama_kavling'] = 'Perumahan B';
        $payload['id_perusahaan'] = [2];
        $this->putJson('/_test/lokasi/2', $payload)->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lokasi_kavling', ['id' => 2, 'nama_kavling' => 'Perumahan B']);
        $this->assertDatabaseHas('lokasi_kavling_perusahaan', ['id_lokasi' => 2, 'id_perusahaan' => 2]);
        $this->assertDatabaseMissing('lokasi_kavling_perusahaan', ['id_lokasi' => 2, 'id_perusahaan' => 1]);
    }
}
