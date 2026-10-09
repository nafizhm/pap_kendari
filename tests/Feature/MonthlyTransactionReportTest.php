<?php

namespace Tests\Feature;

use App\Services\MonthlyTransactionReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlyTransactionReportTest extends TestCase
{
    public function test_report_counts_each_transaction_by_month_and_year(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        foreach (['pengajuan_hold' => 'tgl_booking', 'customer' => 'tanggal_verif', 'wawancara' => 'tgl_wawancara', 'wawancara_sp3k' => 'tgl_terbit_sp3k', 'akad' => 'tgl_akad'] as $tableName => $date) {
            Schema::create($tableName, function (Blueprint $table) use ($date, $tableName) {
                $table->id();
                $table->dateTime($date);
                if (in_array($tableName, ['customer', 'pengajuan_hold'])) {
                    foreach (['nama_lengkap', 'no_telp', 'kode_customer', 'no_registrasi'] as $field) $table->string($field)->nullable();
                    $table->integer('id_lokasi')->nullable();
                    $table->integer('id_kavling')->nullable();
                }
                if ($tableName === 'wawancara') $table->integer('id_customer')->default(1);
                if ($tableName === 'wawancara_sp3k') $table->integer('id_wawancara')->default(1);
                if ($tableName === 'pengajuan_hold') $table->integer('stt_reg')->default(1);
            });
            DB::table($tableName)->insert([
                ['id' => 1, $date => '2026-01-01 00:00:00'],
                ['id' => 2, $date => '2026-01-31 23:59:59'],
                ['id' => 3, $date => '2026-12-31 23:59:59'],
                ['id' => 4, $date => '2027-01-01 00:00:00'],
            ]);
        }
        Schema::create('detail_akad', function (Blueprint $table) {
            $table->id();
            $table->integer('id_akad');
            $table->integer('status');
            $table->integer('id_customer')->default(1);
        });
        DB::table('detail_akad')->insert([
            ['id_akad' => 1, 'status' => 2], ['id_akad' => 1, 'status' => 2],
            ['id_akad' => 2, 'status' => 1], ['id_akad' => 3, 'status' => 2],
            ['id_akad' => 4, 'status' => 2],
        ]);
        DB::table('pengajuan_hold')->insert(['tgl_booking' => '2026-01-10', 'stt_reg' => 3]);
        DB::table('pengajuan_hold')->insert(['tgl_booking' => '2026-01-10', 'stt_reg' => 2]);
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kavling');
        });
        foreach (array_keys(MonthlyTransactionReport::TYPES) as $type) {
            $rows = app(MonthlyTransactionReport::class)->counts(2026, $type);
            $this->assertCount(12, $rows);
            $this->assertSame(['bulan' => 'Januari', 'jumlah' => 2], $rows[0]);
            $this->assertSame(['bulan' => 'Februari', 'jumlah' => 0], $rows[1]);
            $this->assertSame(['bulan' => 'Desember', 'jumlah' => 1], $rows[11]);
            $this->assertSame(3, array_sum(array_column($rows, 'jumlah')));
            $report = app(MonthlyTransactionReport::class);
            $this->assertCount(2, $report->details(2026, $type, 1)->get());
            $this->assertCount(0, $report->details(2026, $type, 2)->get());
            $this->assertCount(1, $report->details(2026, $type, 12)->get());
            $this->assertCount(3, $report->details(2026, $type)->get());
        }
        Schema::create('menu', function (Blueprint $table) { $table->id(); $table->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id(); $table->integer('id_user'); $table->integer('id_menu'); $table->boolean('lihat');
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'laporan.index']);
        DB::table('hak_akses')->insert(['id_user' => 99, 'id_menu' => 1, 'lihat' => 1]);
        $this->actingAs((new \App\Models\User)->forceFill(['id' => 99]));
        $this->getJson('/admin/laporan/detail?tahun=2026&jenis_transaksi=akad&bulan=1')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/admin/laporan/detail?tahun=2026&jenis_transaksi=akad&bulan=13')->assertUnprocessable();
        $response = $this->get('/admin/laporan/excel?tahun=2026&jenis_transaksi=akad');
        $response->assertOk()->assertDownload('laporan-akad-2026.xlsx');
        $this->assertStringStartsWith('PK', $response->streamedContent());
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->getJson('/admin/laporan/detail?tahun=2026&jenis_transaksi=akad&bulan=1')->assertForbidden();
        $this->get('/admin/laporan/excel?tahun=2026&jenis_transaksi=akad')->assertForbidden();
    }
}
