<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerProgressDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('id_status_progres');
            $table->date('tanggal_verif')->nullable();
        });
        Schema::create('progres_list_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('status_progres');
        });
        Schema::create('wawancara', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->date('tgl_wawancara')->nullable();
        });
        Schema::create('wawancara_sp3k', function (Blueprint $table) {
            $table->id();
            $table->integer('id_wawancara');
            $table->integer('status');
            $table->date('tgl_terbit_sp3k')->nullable();
        });
        Schema::create('akad', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_akad');
        });
        Schema::create('detail_akad', function (Blueprint $table) {
            $table->id();
            $table->integer('id_akad');
            $table->integer('id_customer');
            $table->integer('status');
        });
        Schema::create('bast', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->date('tanggal_bast');
        });
        foreach (['BOOKING FEE', 'WAWANCARA', 'SP3K', 'AKAD', 'SERAH TERIMA'] as $index => $status) {
            DB::table('progres_list_penjualan')->insert(['id' => $index + 1, 'status_progres' => $status]);
        }
        DB::table('customer')->insert(['id' => 1, 'id_status_progres' => 1, 'tanggal_verif' => '2026-09-01']);
        DB::table('wawancara')->insert([
            ['id' => 1, 'id_customer' => 1, 'tgl_wawancara' => '2026-09-02'],
            ['id' => 2, 'id_customer' => 1, 'tgl_wawancara' => '2026-09-05'],
        ]);
        DB::table('wawancara_sp3k')->insert([
            ['id_wawancara' => 1, 'status' => 1, 'tgl_terbit_sp3k' => '2026-09-06'],
            ['id_wawancara' => 2, 'status' => 1, 'tgl_terbit_sp3k' => '2026-09-07'],
            ['id_wawancara' => 2, 'status' => 2, 'tgl_terbit_sp3k' => '2026-09-30'],
        ]);
        DB::table('akad')->insert([
            ['id' => 1, 'tgl_akad' => '2026-09-10'],
            ['id' => 2, 'tgl_akad' => '2026-09-15'],
            ['id' => 3, 'tgl_akad' => '2026-10-20'],
        ]);
        DB::table('detail_akad')->insert([
            ['id_customer' => 1, 'id_akad' => 1, 'status' => 2],
            ['id_customer' => 1, 'id_akad' => 2, 'status' => 2],
            ['id_customer' => 1, 'id_akad' => 3, 'status' => 1],
        ]);
        DB::table('bast')->insert(['id_customer' => 1, 'tanggal_bast' => '2026-09-25']);
    }

    public function test_date_matches_current_progress_and_latest_completed_transaction(): void
    {
        foreach ([1 => '2026-09-01', 2 => '2026-09-05', 3 => '2026-09-07', 4 => '2026-09-15', 5 => '2026-09-25'] as $status => $expected) {
            DB::table('customer')->where('id', 1)->update(['id_status_progres' => $status]);
            $customer = Customer::withProgressDates()->with('progres')->findOrFail(1);
            $this->assertSame($expected, $customer->tanggal_progres);
        }
    }

    public function test_missing_transaction_does_not_use_another_progress_date(): void
    {
        DB::table('customer')->insert(['id' => 2, 'id_status_progres' => 4, 'tanggal_verif' => '2026-09-01']);
        $customer = Customer::withProgressDates()->with('progres')->findOrFail(2);
        $this->assertNull($customer->tanggal_progres);
    }
}
