<?php

namespace Tests\Feature;

use App\Http\Controllers\Legal\BerkasPengajuanController;
use App\Models\PersyaratanLegal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegalDocumentUploadTest extends TestCase
{
    private string $publicDirectory;
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->publicDirectory = storage_path('framework/testing/legal-' . uniqid());
        $this->app->usePublicPath($this->publicDirectory);
        Schema::create('persyaratan_legal', function (Blueprint $table) {
            $table->id();
            foreach ((new PersyaratanLegal)->getFillable() as $field) $table->string($field)->nullable();
        });
        Schema::create('jenis_berkas', function (Blueprint $table) {
            $table->id(); $table->string('nama'); $table->boolean('aktif');
        });
        DB::table('jenis_berkas')->insert([['id' => 1, 'nama' => 'IPH', 'aktif' => 1], ['id' => 2, 'nama' => 'Arsip', 'aktif' => 0]]);
        PersyaratanLegal::create(['status_jenis_berkas' => ['1' => 0, '2' => 1], 'percakapan_wa' => 'old.png']);
        Route::put('/_test/legal/{id}', [BerkasPengajuanController::class, 'update']);
        Route::post('/_test/legal/{id}/pdf', [BerkasPengajuanController::class, 'pdf']);
    }
    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }
    public function test_upload_sets_status_and_replacement_preserves_other_statuses(): void
    {
        $this->put('/_test/legal/1', ['status_berkas' => [1 => 0], 'berkas_files' => [1 => UploadedFile::fake()->image('iph.png')]], ['Accept' => 'application/json'])->assertOk();
        $record = PersyaratanLegal::findOrFail(1);
        $first = $record->file_jenis_berkas[1];
        $this->assertSame(1, $record->status_jenis_berkas[1]);
        $this->assertSame(1, $record->status_jenis_berkas[2]);
        $this->assertSame('1', $record->IPH);
        $this->assertFileExists(public_path('assets/legal/pengajuan_berkas/berkas/' . $first));
        $this->putJson('/_test/legal/1', ['status_berkas' => [1 => 0]])->assertOk();
        $this->assertSame(1, PersyaratanLegal::findOrFail(1)->status_jenis_berkas[1]);
        $this->put('/_test/legal/1', ['berkas_files' => [1 => UploadedFile::fake()->createWithContent('iph.pdf', '%PDF-1.4 test')]], ['Accept' => 'application/json'])->assertOk();
        $record->refresh();
        $this->assertStringEndsWith('.pdf', $record->file_jenis_berkas[1]);
        $this->assertFileDoesNotExist(public_path('assets/legal/pengajuan_berkas/berkas/' . $first));
        $this->assertSame('old.png', $record->percakapan_wa);
    }
    public function test_invalid_or_unknown_upload_is_rejected_without_changing_data(): void
    {
        $this->put('/_test/legal/1', ['berkas_files' => [1 => UploadedFile::fake()->create('bad.exe', 1)]], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('berkas_files.1');
        $this->put('/_test/legal/1', ['berkas_files' => [999 => UploadedFile::fake()->image('iph.png')]], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('berkas_files');
        $this->assertNull(PersyaratanLegal::findOrFail(1)->file_jenis_berkas);
    }

    public function test_pdf_print_combines_selected_image_and_pdf_and_rejects_missing_files(): void
    {
        $this->put('/_test/legal/1', ['berkas_files' => [1 => UploadedFile::fake()->image('iph.png', 100, 100)]], ['Accept' => 'application/json'])->assertOk();
        $source = new \TCPDF();
        $source->AddPage();
        $source->Write(0, 'Dokumen sumber');
        $folder = public_path('assets/legal/pengajuan_berkas/berkas');
        File::put($folder . '/source.pdf', $source->Output('', 'S'));
        $record = PersyaratanLegal::findOrFail(1);
        $record->update(['file_jenis_berkas' => array_replace($record->file_jenis_berkas, [2 => 'source.pdf'])]);
        $response = $this->post('/_test/legal/1/pdf', ['urutan' => [2, 1]]);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        File::put($folder . '/result.pdf', $response->getContent());
        $reader = new \setasign\Fpdi\Fpdi();
        $this->assertSame(2, $reader->setSourceFile($folder . '/result.pdf'));
        $this->postJson('/_test/legal/1/pdf', ['urutan' => [1, 1]])->assertUnprocessable();
        File::delete($folder . '/source.pdf');
        $this->postJson('/_test/legal/1/pdf', ['urutan' => [2]])->assertUnprocessable();
    }
}
