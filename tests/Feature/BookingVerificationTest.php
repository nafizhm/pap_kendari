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

        Schema::create('berkas_booking', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->integer('urutan')->default(0);
            $table->boolean('wajib')->default(false);
            $table->boolean('aktif')->default(true);
            $table->softDeletes();
        });
        foreach (\App\Models\BerkasBooking::LEGACY as $kode => $nama) {
            \App\Models\BerkasBooking::create(['kode' => $kode, 'nama' => $nama, 'wajib' => $kode === 'foto_ktp']);
        }

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
        Route::post('/_test/booking/{id}/upload', [PengajuanHoldController::class, 'upload']);
        Route::post('/_test/booking/{id}/delete-file', [PengajuanHoldController::class, 'deleteFile']);
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

    public function test_all_five_emergency_contacts_are_saved_and_displayed(): void
    {
        $contacts = [];
        for ($i = 1; $i <= 5; $i++) {
            $contacts['kontak_darurat_' . $i] = '08123456700' . $i;
            $contacts['nama_pemilik_kontak_darurat_' . $i] = 'Pemilik ' . $i;
            $contacts['keterangan_kontak_darurat_' . $i] = 'Saudara ' . $i;
        }
        $this->postJson('/_test/booking/1', $this->payload($contacts))->assertOk();
        $this->assertDatabaseHas('pengajuan_hold', array_merge(['id' => 1], $contacts));
        $html = view('shared.emergency-contacts', ['data' => PengajuanHold::findOrFail(1), 'readonly' => true])->render();
        foreach ($contacts as $field => $value) {
            $this->assertStringContainsString('name="' . $field . '"', $html);
            $this->assertStringContainsString('value="' . $value . '"', $html);
        }
        $this->postJson('/_test/booking/1', $this->payload(['kontak_darurat_5' => str_repeat('0', 256)]))
            ->assertUnprocessable()->assertJsonValidationErrors('kontak_darurat_5');
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

    private function customAttachment(bool $required = false): \App\Models\BerkasBooking
    {
        return \App\Models\BerkasBooking::create([
            'kode' => 'berkas_surat_kerja', 'nama' => 'Surat Keterangan Kerja',
            'wajib' => $required, 'aktif' => true, 'urutan' => 9,
        ]);
    }

    public function test_custom_booking_upload_can_be_replaced_and_deleted(): void
    {
        $type = $this->customAttachment();
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->createWithContent('surat.pdf', '%PDF-1.4 test')],
        ], ['Accept' => 'application/json'])->assertOk();
        $first = PengajuanHold::findOrFail(1)->berkas_booking[$type->kode]['file'];
        $this->assertFileExists(public_path('assets/booking/' . $first));
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->image('surat.png')],
        ], ['Accept' => 'application/json'])->assertOk();
        $second = PengajuanHold::findOrFail(1)->berkas_booking[$type->kode]['file'];
        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist(public_path('assets/booking/' . $first));
        $this->postJson('/_test/booking/1/delete-file', ['field' => $type->kode])->assertOk();
        $this->assertArrayNotHasKey($type->kode, PengajuanHold::findOrFail(1)->berkas_booking);
        $this->assertFileDoesNotExist(public_path('assets/booking/' . $second));
    }

    public function test_required_booking_types_follow_master_settings(): void
    {
        $type = $this->customAttachment(true);
        $key = $type->inputKey();
        $this->postJson('/_test/booking/1/upload')->assertUnprocessable()->assertJsonValidationErrors($key);
        $this->postJson('/_test/booking/1', $this->payload())->assertOk();
        $type->update(['aktif' => false]);
        $this->postJson('/_test/booking/1/upload')->assertOk();
        $html = view('shared.booking-attachments', ['frontend' => true])->render();
        $this->assertStringNotContainsString($type->nama, $html);
        \App\Models\BerkasBooking::where('kode', 'foto_ktp')->update(['aktif' => false]);
        $rules = app(\App\Services\BookingAttachments::class)->rules();
        $this->assertSame('prohibited', $rules['foto_ktp']);
    }

    public function test_unknown_types_and_invalid_files_are_rejected(): void
    {
        $type = $this->customAttachment();
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => ['unknown' => UploadedFile::fake()->image('file.png')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('berkas_booking_files');
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->create('file.exe', 10, 'application/octet-stream')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors($type->inputKey());
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->create('file.pdf', 10241, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors($type->inputKey());
    }

    public function test_deleted_type_keeps_historical_file_visible(): void
    {
        $type = $this->customAttachment();
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->image('surat.png')],
        ], ['Accept' => 'application/json'])->assertOk();
        $type->delete();
        $booking = PengajuanHold::findOrFail(1);
        $file = $booking->berkas_booking[$type->kode]['file'];
        $this->assertFileExists(public_path('assets/booking/' . $file));
        $this->assertStringNotContainsString($type->nama, view('shared.booking-attachments')->render());
        $html = view('shared.booking-attachments', ['data' => $booking, 'readonly' => true])->render();
        $this->assertStringContainsString($type->nama, $html);
        $this->assertStringContainsString($file, $html);
    }

    public function test_custom_files_transfer_to_customer_and_archive_on_approval(): void
    {
        $type = $this->customAttachment(true);
        $payload = $this->prepareApproval();
        $this->mock(\App\Http\Controllers\GenerateNumberController::class)
            ->shouldReceive('generateNomorDokumen')->once()->andReturn('TEST-002');
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors($type->inputKey());
        $payload['berkas_booking_files'] = [$type->kode => UploadedFile::fake()->createWithContent('surat.pdf', '%PDF-1.4 test')];
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])->assertOk();
        $booking = PengajuanHold::findOrFail(1);
        $file = $booking->berkas_booking[$type->kode]['file'];
        $this->assertDatabaseHas('upload_file', ['nama_file' => $type->nama, 'lampiran' => $file]);
        $this->assertFileExists(public_path('assets/customer/' . $file));
        $this->assertFileDoesNotExist(public_path('assets/booking/' . $file));
        $html = view('shared.booking-attachments', ['data' => $booking, 'readonly' => true])->render();
        $this->assertStringContainsString('assets/customer/' . $file, $html);
    }

    public function test_public_booking_saves_custom_files_and_honors_removed_ktp_requirement(): void
    {
        $type = $this->customAttachment(true);
        \App\Models\BerkasBooking::where('kode', 'foto_ktp')->update(['aktif' => false]);
        $payload = $this->payload([
            'id_kavling' => 2, 'alamat_domisili' => 'Alamat domisili', 'status_pernikahan' => 'Belum Menikah',
            'jenis_pembelian' => 'KPR', 'total_harga' => 160000000,
        ]);
        $this->postJson('/booking/store', $payload)->assertUnprocessable()->assertJsonValidationErrors($type->inputKey());
        $payload['berkas_booking_files'] = [$type->kode => UploadedFile::fake()->image('surat.png')];
        $this->post('/booking/store', $payload, ['Accept' => 'application/json'])->assertOk();
        $booking = PengajuanHold::where('id_kavling', 2)->firstOrFail();
        $this->assertNull($booking->foto_ktp);
        $this->assertFileExists(public_path('assets/booking/' . $booking->berkas_booking[$type->kode]['file']));
    }

    public function test_master_booking_types_can_be_managed_with_permissions(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_menu');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $field) $table->boolean($field)->default(false);
        });
        Schema::create('log_aktivitas_pengguna', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('user_name')->nullable();
            $table->text('aktivitas');
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'berkas-booking.index']);
        DB::table('hak_akses')->insert(['id_user' => 99, 'id_menu' => 1, 'lihat' => 1]);
        $this->actingAs((new \App\Models\User)->forceFill(['id' => 99, 'username' => 'tester']));
        $payload = ['nama' => 'Surat Kerja', 'urutan' => 10, 'wajib' => 1, 'aktif' => 1];
        $this->postJson('/admin/master/berkas-booking', $payload)->assertForbidden();
        DB::table('hak_akses')->update(['tambah' => 1, 'edit' => 1, 'hapus' => 1]);
        $this->postJson('/admin/master/berkas-booking', $payload)->assertOk();
        $type = \App\Models\BerkasBooking::where('nama', 'Surat Kerja')->firstOrFail();
        $this->assertStringStartsWith('berkas_', $type->kode);
        $this->getJson('/admin/master/berkas-booking/' . $type->id . '/edit')->assertOk()->assertJsonPath('data.wajib', true);
        $this->putJson('/admin/master/berkas-booking/' . $type->id, array_merge($payload, ['nama' => 'Surat Kerja Baru', 'aktif' => 0]))->assertOk();
        $this->assertDatabaseHas('berkas_booking', ['id' => $type->id, 'nama' => 'Surat Kerja Baru', 'aktif' => 0]);
        $this->deleteJson('/admin/master/berkas-booking/' . $type->id)->assertOk();
        $this->assertNull(\App\Models\BerkasBooking::find($type->id));
        $this->assertNotNull(\App\Models\BerkasBooking::withTrashed()->find($type->id));
    }

    public function test_failed_approval_removes_custom_copies_and_preserves_original_file(): void
    {
        $type = $this->customAttachment();
        $this->post('/_test/booking/1/upload', [
            'berkas_booking_files' => [$type->kode => UploadedFile::fake()->image('surat.png')],
        ], ['Accept' => 'application/json'])->assertOk();
        $file = PengajuanHold::findOrFail(1)->berkas_booking[$type->kode]['file'];
        $payload = $this->prepareApproval();
        $this->mock(\App\Http\Controllers\GenerateNumberController::class)
            ->shouldReceive('generateNomorDokumen')->once()->andThrow(new \RuntimeException('Test failure'));
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertFileExists(public_path('assets/booking/' . $file));
        $this->assertFileDoesNotExist(public_path('assets/customer/' . $file));
        $this->assertDatabaseCount('upload_file', 0);
        $this->assertSame($file, PengajuanHold::findOrFail(1)->berkas_booking[$type->kode]['file']);
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
        $contacts = [];
        for ($i = 1; $i <= 5; $i++) {
            $contacts['kontak_darurat_' . $i] = '08123456700' . $i;
            $contacts['nama_pemilik_kontak_darurat_' . $i] = 'Pemilik ' . $i;
            $contacts['keterangan_kontak_darurat_' . $i] = 'Saudara ' . $i;
        }
        $payload = array_merge($payload, $contacts);
        $this->mock(\App\Http\Controllers\GenerateNumberController::class)
            ->shouldReceive('generateNomorDokumen')->once()->andReturn('TEST-001');
        $this->post('/_test/booking/1', $payload, ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseHas('customer', ['nama_lengkap' => 'Nama Diperbarui', 'nik' => '0012345678901234']);
        $this->assertDatabaseHas('customer', $contacts);
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
