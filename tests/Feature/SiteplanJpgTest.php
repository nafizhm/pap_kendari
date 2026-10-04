<?php

namespace Tests\Feature;

use App\Http\Controllers\Siteplan\SiteplanPenjualanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteplanJpgTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([
            'konfigurasi' => ['nama_perusahaan'],
            'lokasi_kavling' => ['nama_kavling'],
            'master_svg' => ['id_lokasi', 'header_svg', 'polygon_svg', 'path_svg', 'footer_svg'],
            'kavling_peta' => ['id_lokasi', 'jenis_map', 'map', 'matrik', 'kode_kavling'],
            'pengajuan_hold' => ['id_kavling', 'stt_reg'],
            'customer' => ['id_kavling', 'stt_arsip'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->text($column)->nullable();
            });
        }
        Http::preventStrayRequests();
        Route::get('/_test/siteplan-jpg/{id}', [SiteplanPenjualanController::class, 'cetakJPG']);
        foreach (['Penjualan', 'Air', 'Listrik', 'BalikNama', 'BphtbSSP', 'UnitReady'] as $type) {
            Route::get('/_test/siteplan-pdf/'.$type.'/{id}', ['App\\Http\\Controllers\\Siteplan\\Siteplan'.$type.'Controller', 'cetakPDF']);
        }
    }

    public function test_jpg_uses_current_svg_and_booking_colors_without_external_requests(): void
    {
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Denah']);
        DB::table('master_svg')->insert([
            'id_lokasi' => 1,
            'header_svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="[[lebar]]" height="[[tinggi]]" viewBox="0 0 400 200">',
            'polygon_svg' => '<polygon points="[[1]]" fill="[[2]]"/><text>[[4]]</text>',
            'footer_svg' => '</svg>',
        ]);
        foreach ([1, 2] as $id) {
            DB::table('kavling_peta')->insert([
                'id' => $id, 'id_lokasi' => 1, 'jenis_map' => 'polygon',
                'map' => '0,0 10,0 10,10', 'kode_kavling' => 'A-'.$id,
            ]);
        }
        DB::table('pengajuan_hold')->insert(['id_kavling' => 2, 'stt_reg' => 1]);

        $response = $this->get('/_test/siteplan-jpg/1');
        $response->assertOk()->assertViewIs('admin.siteplan.siteplan_penjualan.jpg');
        $response->assertViewHas('filename', 'siteplan_1.jpg');
        $svg = $response->viewData('svgContent');
        $this->assertStringContainsString('fill="#ffffff"', $svg);
        $this->assertStringContainsString('fill="#42f202"', $svg);
        $this->assertStringContainsString('viewBox="0 0 400 200"', $svg);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertSee('js/siteplan-jpg.js');
        $pdf = $this->get('/_test/siteplan-pdf/Penjualan/1');
        $pdf->assertOk()->assertViewHas('svgContent', $svg);
        Http::assertNothingSent();
    }

    public function test_all_pdf_exports_use_local_assets_and_preserve_metadata(): void
    {
        DB::table('konfigurasi')->insert(['nama_perusahaan' => 'Perusahaan Uji']);
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Denah Uji']);
        DB::table('master_svg')->insert([
            'id_lokasi' => 1,
            'header_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200">',
            'footer_svg' => '</svg>',
        ]);
        foreach (['Penjualan', 'Air', 'Listrik', 'BalikNama', 'BphtbSSP', 'UnitReady'] as $type) {
            $response = $this->get('/_test/siteplan-pdf/'.$type.'/1');
            $response->assertOk()->assertViewIs('admin.siteplan.pdf');
            $response->assertViewHas('filename', 'siteplan_1.pdf');
            $response->assertSee('data-format="PDF"', false);
            $response->assertSee('assets/plugins/pdfmake/pdfmake.min.js');
            $response->assertSee('assets/plugins/pdfmake/vfs_fonts.js');
            $response->assertDontSee('aplikasikavling.com');
            $this->assertSame('Perusahaan Uji', $response->viewData('pdfMetadata')['company']);
            $this->assertSame('Denah Uji', $response->viewData('pdfMetadata')['location']);
            $this->assertSame(now()->translatedFormat('d F Y'), $response->viewData('pdfMetadata')['date']);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->get('/_test/siteplan-pdf/'.$type.'/999')->assertNotFound();
        }
        DB::table('master_svg')->delete();
        $this->get('/_test/siteplan-pdf/Penjualan/1')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_missing_location_or_template_returns_404(): void
    {
        $this->get('/_test/siteplan-jpg/999')->assertNotFound();
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Tanpa denah']);
        $this->get('/_test/siteplan-jpg/1')->assertNotFound();
        Http::assertNothingSent();
    }
}
