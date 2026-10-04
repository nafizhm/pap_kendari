<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Support\DashboardPeriod;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-03 12:00:00'));
        Route::get('/_test/dashboard-period', fn (Request $request) => response()->json(DashboardPeriod::fromRequest($request)->parameters()));
    }

    public function test_presets_month_and_custom_dates_resolve_correctly(): void
    {
        foreach ([
            [[], null, null],
            [['period' => 'this_month'], '2026-10-01', '2026-10-31'],
            [['period' => 'last_month'], '2026-09-01', '2026-09-30'],
            [['period' => 'month', 'month' => '2024-02'], '2024-02-01', '2024-02-29'],
            [['period' => 'custom', 'start_date' => '2026-09-30', 'end_date' => '2026-10-02'], '2026-09-30', '2026-10-02'],
        ] as [$query, $start, $end]) {
            $period = DashboardPeriod::fromRequest(Request::create('/', 'GET', $query));
            $this->assertSame($start, $period->start?->toDateString());
            $this->assertSame($end, $period->end?->toDateString());
        }
        $this->travelTo(Carbon::parse('2026-01-31'));
        $period = DashboardPeriod::fromRequest(Request::create('/', 'GET', ['period' => 'last_month']));
        $this->assertSame('2025-12-01', $period->start->toDateString());
        $this->assertSame('2025-12-31', $period->end->toDateString());
    }

    public function test_invalid_and_reversed_dates_are_rejected(): void
    {
        foreach ([
            ['period' => 'invalid'],
            ['period' => 'month', 'month' => '2026-13'],
            ['period' => 'month'],
            ['period' => 'custom', 'start_date' => '2026-02-30', 'end_date' => '2026-03-01'],
            ['period' => 'custom', 'start_date' => '2026-10-03', 'end_date' => '2026-10-02'],
            ['period' => 'custom'],
        ] as $query) {
            $this->getJson('/_test/dashboard-period?'.http_build_query($query))->assertUnprocessable();
        }
        $this->getJson('/_test/dashboard-period?period=all&start_date=invalid')->assertOk()->assertExactJson(['period' => 'all']);
    }

    public function test_dashboard_filters_counts_inclusively_and_keeps_inventory(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([
            'customer' => ['tanggal_verif', 'id_status_progres', 'id_lokasi', 'id_kavling', 'id_marketing', 'jenis_pembelian', 'stt_arsip'],
            'kavling_peta' => ['id_lokasi'],
            'lokasi_kavling' => ['nama_kavling', 'nama_singkat'],
            'progres_list_penjualan' => ['status_progres', 'short_name', 'stt_tampil', 'urutan'],
            'pengajuan_hold' => ['id_kavling', 'stt_reg', 'tgl_booking'],
            'bank_kpr' => ['nama'],
            'marketing_offline' => ['nama_marketing'],
            'wawancara' => ['id_customer'],
            'wawancara_sp3k' => ['id_wawancara', 'id_bank_kpr', 'status'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->text($column)->nullable();
            });
        }
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Lokasi A']);
        DB::table('marketing_offline')->insert(['id' => 1, 'nama_marketing' => 'Marketing A']);
        DB::table('bank_kpr')->insert(['id' => 1, 'nama' => 'Bank A']);
        DB::table('progres_list_penjualan')->insert(['id' => 2, 'status_progres' => 'Booking', 'short_name' => 'booking', 'stt_tampil' => 1, 'urutan' => 1]);
        foreach (['2026-09-30 23:59:59', '2026-10-01 00:00:00', '2026-10-31 23:59:59', '2026-11-01 00:00:00', null] as $index => $date) {
            $id = $index + 1;
            DB::table('kavling_peta')->insert(['id' => $id, 'id_lokasi' => 1]);
            DB::table('customer')->insert(['id' => $id, 'tanggal_verif' => $date, 'id_lokasi' => 1, 'id_kavling' => $id, 'id_status_progres' => 2, 'id_marketing' => 1, 'jenis_pembelian' => 'KPR', 'stt_arsip' => 0]);
            DB::table('wawancara')->insert(['id' => $id, 'id_customer' => $id]);
            DB::table('wawancara_sp3k')->insert(['id_wawancara' => $id, 'id_bank_kpr' => 1, 'status' => 1]);
        }
        foreach ([6 => '2026-10-31', 7 => '2026-09-30'] as $id => $date) {
            DB::table('kavling_peta')->insert(['id' => $id, 'id_lokasi' => 1]);
            DB::table('pengajuan_hold')->insert(['id_kavling' => $id, 'stt_reg' => 1, 'tgl_booking' => $date]);
        }
        Auth::shouldReceive('check')->andReturn(true);
        foreach (['this_month' => 2, 'last_month' => 1, 'all' => 5] as $mode => $expected) {
            $data = app(DashboardController::class)->dashboard(Request::create('/', 'GET', ['period' => $mode]))->getData();
            $this->assertSame($expected, $data['merah']);
            $this->assertSame(7, $data['totalKavling']);
            $this->assertSame($expected, $data['totalSemua']['total_customer']);
            $this->assertSame($expected, $data['dataLokasi'][0]['kpr']);
            $this->assertSame($expected, $data['dataProgres'][0]['jumlah']);
            $this->assertSame($expected, $data['dataMarketing'][0]['jumlah']);
            $this->assertSame($expected, $data['dataBank'][0]['jumlah']);
            $this->assertSame($mode === 'all' ? 2 : 1, $data['totalSemua']['hold']);
        }
    }

    public function test_filter_controls_render_selected_period(): void
    {
        $period = DashboardPeriod::fromRequest(Request::create('/', 'GET', ['period' => 'custom', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']));
        $html = view('admin.dashboard.period-filter', ['period' => $period, 'errors' => new ViewErrorBag()])->render();
        foreach (['Bulan Ini', 'Bulan Kemarin', 'Pilih Bulan', 'Custom Tanggal', 'Semua Waktu', 'value="2026-10-01"', 'value="2026-10-31"', 'Aktif:'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
