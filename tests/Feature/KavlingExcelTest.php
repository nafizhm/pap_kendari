<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\KavlingController;
use App\Models\KavlingPeta;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KavlingExcelTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->file = tempnam(sys_get_temp_dir(), 'kavling_excel_');
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
            $table->string('kode_kavling');
            foreach (['panjang_kanan', 'panjang_kiri', 'lebar_depan', 'lebar_belakang', 'luas_tanah', 'luas_bangunan', 'hrg_jual', 'biaya_surat', 'peningkatan_mutu'] as $name) {
                $table->double($name)->nullable();
            }
            $table->text('rincian_biaya')->nullable();
            $table->timestamps();
        });
        Schema::create('komponen_biaya', function (Blueprint $table) {
            $table->id();
            foreach (['kode_unik', 'nama', 'deskripsi', 'satuan'] as $name) $table->string($name)->nullable();
            $table->integer('urutan');
            $table->boolean('aktif');
            $table->boolean('wajib')->default(false);
            $table->timestamps();
        });
        DB::table('lokasi_kavling')->insert([
            ['id' => 1, 'nama_kavling' => 'GREEN ANUGERAH RESIDENCE 3'],
            ['id' => 2, 'nama_kavling' => 'RUNDU REGENCY'],
        ]);
        DB::table('komponen_biaya')->insert(['nama' => 'Harga Rumah', 'kode_unik' => 'harga_rumah', 'urutan' => 1, 'aktif' => true]);
        foreach ([1, 2] as $location) {
            KavlingPeta::create(['id_lokasi' => $location, 'kode_kavling' => 'A-01',
                'panjang_kanan' => 12.5, 'panjang_kiri' => 13, 'lebar_depan' => 6,
                'lebar_belakang' => 7, 'luas_tanah' => 80, 'luas_bangunan' => 36,
                'rincian_biaya' => [['nama' => 'Harga Rumah', 'nilai' => 150000000]]]);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) unlink($this->file);
        parent::tearDown();
    }

    private function export(int $location = 0)
    {
        $response = app(KavlingController::class)->cetakExcel(Request::create('/'), $location);
        ob_start();
        $response->sendContent();
        file_put_contents($this->file, ob_get_clean());
        return IOFactory::load($this->file);
    }

    private function import($book)
    {
        (new Xlsx($book))->save($this->file);
        $request = Request::create('/', 'POST', [], [], [
            'file' => new UploadedFile($this->file, 'kavling.xlsx', null, null, true),
        ]);
        return app(KavlingController::class)->importExcel($request);
    }

    public function test_export_edit_import_preserves_individual_dimensions_and_location(): void
    {
        $book = $this->export();
        $sheet = $book->getActiveSheet();
        $this->assertSame('Luas Tanah (m2)', $sheet->getCell('H2')->getValue());
        $this->assertEquals(12.5, $sheet->getCell('D3')->getValue());
        $this->assertEquals(150000000, $sheet->getCell('K3')->getCalculatedValue());
        foreach (['D3' => 14.75, 'E3' => null, 'F3' => 0, 'G3' => 8.25, 'H3' => 90.5, 'I3' => 45, 'J3' => 175000000, 'D4' => 16] as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        $this->import($book);
        $one = KavlingPeta::findOrFail(1);
        $two = KavlingPeta::findOrFail(2);
        foreach (['panjang_kanan' => 14.75, 'panjang_kiri' => 13, 'lebar_depan' => 0, 'lebar_belakang' => 8.25, 'luas_tanah' => 90.5, 'luas_bangunan' => 45] as $field => $value) {
            $this->assertEquals($value, $one->$field);
        }
        $this->assertSame(175000000, $one->total_harga);
        $this->assertEquals(16, $two->panjang_kanan);
        $this->assertSame(150000000, $two->total_harga);
    }

    public function test_blank_first_dimension_does_not_shift_price_columns(): void
    {
        $book = $this->export(1);
        $book->getActiveSheet()->setCellValue('D3', null)->setCellValue('H3', 99);
        $this->import($book);
        $this->assertEquals(99, KavlingPeta::find(1)->luas_tanah);
        $this->assertEquals(12.5, KavlingPeta::find(1)->panjang_kanan);
        $this->assertEquals(80, KavlingPeta::find(2)->luas_tanah);
        $this->assertSame(1, DB::table('komponen_biaya')->count());
    }

    public function test_invalid_dimension_rolls_back_entire_import(): void
    {
        $book = $this->export();
        $book->getActiveSheet()->setCellValue('D3', 20)->setCellValue('D4', 'bukan angka');
        $this->import($book);
        $this->assertEquals(12.5, KavlingPeta::find(1)->panjang_kanan);
        $this->assertTrue(session()->has('errors'));
    }
}
