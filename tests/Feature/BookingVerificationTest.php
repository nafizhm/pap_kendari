<?php

namespace Tests\Feature;

use App\Http\Controllers\PengajuanHoldController;
use App\Models\PengajuanHold;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingVerificationTest extends TestCase
{
    private string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        $this->testPublicPath = storage_path('framework/testing/booking-' . uniqid());
        $this->app->usePublicPath($this->testPublicPath);
        File::ensureDirectoryExists(public_path('assets/booking'));

        Schema::create('pengajuan_hold', function (Blueprint $table) {
            $table->id();
            foreach ((new PengajuanHold)->getFillable() as $field) {
                $table->string($field)->nullable();
            }
        });
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            foreach ((new \App\Models\Customer)->getFillable() as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling')->nullable();
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->integer('id_lokasi');
            $table->integer('id_customer')->nullable();
            $table->string('rincian_biaya');
            $table->timestamps();
        });
        DB::table('lokasi_kavling')->insert(['id' => 1]);
        DB::table('kavling_peta')->insert([
            ['id' => 1, 'id_lokasi' => 1, 'rincian_biaya' => '[{"nama":"Harga Rumah","nilai":150000000}]'],
            ['id' => 2, 'id_lokasi' => 1, 'rincian_biaya' => '[{"nama":"Harga Rumah","nilai":160000000}]'],
        ]);
        PengajuanHold::create(array_merge($this->payload(), ['id' => 1, 'nama_lengkap' => 'Nama Lama', 'foto_ktp' => 'existing.png']));
        File::put(public_path('assets/booking/existing.png'), 'original attachment');
        Route::post('/_test/booking/{id}', [PengajuanHoldController::class, 'simpanVerifikasi']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testPublicPath);
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'stt_reg' => 1,
            'tgl_booking' => '2026-09-30',
            'nama_lengkap' => 'Nama Diperbarui',
            'nik' => '0012345678901234',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Bandung',
            'tgl_lahir' => '1990-01-01',
            'alamat_ktp' => 'Alamat baru',
            'no_telp' => '081234567890',
            'id_lokasi' => 1,
            'id_kavling' => 1,
            'id_marketing' => 0,
            'jenis_perumahan' => 'Subsidi',
            'booking_fee' => '1.500.000',
        ], $overrides);
    }

    public function test_pending_can_save_edits_without_payment_details_and_keep_attachment(): void
    {
        $this->postJson('/_test/booking/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('pengajuan_hold', [
            'id' => 1, 'nama_lengkap' => 'Nama Diperbarui', 'booking_fee' => 1500000,
            'stt_reg' => 1, 'foto_ktp' => 'existing.png',
        ]);
        $this->assertFalse(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        $this->assertFileExists(public_path('assets/booking/existing.png'));
    }

    public function test_changing_kavling_releases_old_and_reserves_new_unit(): void
    {
        $this->postJson('/_test/booking/1', $this->payload(['id_kavling' => 2]))->assertOk();
        $this->assertTrue(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        $this->assertFalse(\App\Models\KavlingPeta::whereKey(2)->available()->exists());
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'id_kavling' => 2, 'total_harga' => 160000000]);
    }

    public function test_pending_payment_choices_are_saved_and_can_be_changed_or_cleared(): void
    {
        foreach (['bank', 'metode_bayar'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
            DB::table($name)->insert([['id' => 1], ['id' => 2]]);
        }
        foreach ([1, 2, null] as $value) {
            $this->postJson('/_test/booking/1', $this->payload([
                'id_bank' => $value, 'id_metode_bayar' => $value,
            ]))->assertOk();
            $this->assertDatabaseHas('pengajuan_hold', [
                'id' => 1, 'stt_reg' => 1, 'id_bank' => $value, 'id_metode_bayar' => $value,
            ]);
        }
    }

    public function test_occupied_unit_rejects_edits_without_changing_booking(): void
    {
        PengajuanHold::create($this->payload(['id_kavling' => 2]));
        $this->postJson('/_test/booking/1', $this->payload(['id_kavling' => 2]))
            ->assertUnprocessable()->assertJsonValidationErrors('id_kavling');
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'nama_lengkap' => 'Nama Lama', 'id_kavling' => 1]);
    }

    public function test_attachment_replacement_and_pdf_are_saved(): void
    {
        $this->post('/_test/booking/1', $this->payload([
            'foto_ktp' => UploadedFile::fake()->image('replacement.png'),
            'file_bukti' => UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"),
        ]), ['Accept' => 'application/json'])->assertOk();
        $data = PengajuanHold::findOrFail(1);
        $this->assertNotSame('existing.png', $data->foto_ktp);
        $this->assertFileExists(public_path('assets/booking/' . $data->foto_ktp));
        $this->assertStringEndsWith('.pdf', $data->file_bukti);
        $this->assertFileExists(public_path('assets/booking/' . $data->file_bukti));
        $this->assertFileDoesNotExist(public_path('assets/booking/existing.png'));
    }

    public function test_rejection_saves_edits_and_releases_unit(): void
    {
        $this->postJson('/_test/booking/1', $this->payload(['stt_reg' => 3]))->assertOk();
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'nama_lengkap' => 'Nama Diperbarui', 'stt_reg' => 3]);
        $this->assertTrue(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
    }

    public function test_approved_booking_cannot_be_processed_again(): void
    {
        DB::table('pengajuan_hold')->where('id', 1)->update(['stt_reg' => 2]);
        $this->postJson('/_test/booking/1', $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('stt_reg');
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'stt_reg' => 2, 'nama_lengkap' => 'Nama Lama']);
    }

    private function prepareApproval(): array
    {
        foreach ([new \App\Models\Pemasukan, new \App\Models\Piutang, new \App\Models\PersyaratanLegal, new \App\Models\UploudFile] as $model) {
            Schema::create($model->getTable(), function (Blueprint $table) use ($model) {
                $table->id();
                foreach ($model->getFillable() as $field) {
                    if (!in_array($field, ['id', 'created_at', 'updated_at'])) {
                        $table->string($field)->nullable();
                    }
                }
                $table->timestamps();
            });
        }
        foreach (['bank', 'metode_bayar'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
            DB::table($name)->insert(['id' => 1]);
        }
        return $this->payload([
            'stt_reg' => 2, 'jenis_pembelian' => 'KPR', 'id_bank' => 1, 'id_metode_bayar' => 1,
            'foto_ktp' => UploadedFile::fake()->image('new.png'),
        ]);
    }

    public function test_booking_state_tracks_pending_rejected_and_deleted_records_without_status_column(): void
    {
        $this->assertFalse(Schema::hasColumn('kavling_peta', 'status'));
        $this->assertTrue(\App\Models\KavlingPeta::withBookingState()->findOrFail(1)->is_booked);
        DB::table('pengajuan_hold')->where('id', 1)->update(['stt_reg' => 3]);
        $this->assertFalse(\App\Models\KavlingPeta::withBookingState()->findOrFail(1)->is_booked);
        $this->assertTrue(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        DB::table('pengajuan_hold')->where('id', 1)->update(['stt_reg' => 1]);
        PengajuanHold::findOrFail(1)->delete();
        $this->assertFalse(\App\Models\KavlingPeta::findOrFail(1)->is_booked);
        $this->assertTrue(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
    }

    public function test_active_customer_reserves_unit_but_archived_customer_and_old_approved_booking_do_not(): void
    {
        DB::table('pengajuan_hold')->where('id', 1)->update(['stt_reg' => 2]);
        $customer = \App\Models\Customer::create(['id_kavling' => 1, 'stt_arsip' => 0]);
        $this->assertFalse(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        $this->assertNotNull(\App\Models\KavlingPeta::findOrFail(1)->customer);
        $customer->update(['stt_arsip' => 1]);
        $this->assertTrue(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        $this->assertNull(\App\Models\KavlingPeta::findOrFail(1)->customer);
    }

    public function test_approval_uses_edited_customer_data_and_new_attachment(): void
    {
        $payload = $this->prepareApproval();
        $this->mock(\App\Http\Controllers\GenerateNumberController::class)
            ->shouldReceive('generateNomorDokumen')->once()->andReturn('TEST-001');
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseHas('customer', ['nama_lengkap' => 'Nama Diperbarui', 'nik' => '0012345678901234']);
        $data = PengajuanHold::findOrFail(1);
        $this->assertSame('2', $data->stt_reg);
        $this->assertFileExists(public_path('assets/customer/' . $data->foto_ktp));
        $this->assertFileDoesNotExist(public_path('assets/booking/' . $data->foto_ktp));
        $this->assertFalse(\App\Models\KavlingPeta::whereKey(1)->available()->exists());
        $this->assertFalse(\App\Models\KavlingPeta::findOrFail(1)->is_booked);
    }

    public function test_failed_approval_keeps_original_data_and_attachment(): void
    {
        $payload = $this->prepareApproval();
        $this->mock(\App\Http\Controllers\GenerateNumberController::class)
            ->shouldReceive('generateNomorDokumen')->once()->andThrow(new \RuntimeException('Test failure'));
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'stt_reg' => 1, 'nama_lengkap' => 'Nama Lama', 'foto_ktp' => 'existing.png']);
        $this->assertDatabaseCount('customer', 0);
        $this->assertFileExists(public_path('assets/booking/existing.png'));
        $this->assertCount(1, File::files(public_path('assets/booking')));
        $this->assertCount(0, File::files(public_path('assets/customer')));
    }
}
