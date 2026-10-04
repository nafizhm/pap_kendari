<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\ProgresUnitReady;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SiteplanUnitReadyController extends Controller
{
    public function index()
    {
        $lokasiKavling = LokasiKavling::orderBy('urutan', 'asc')->get();

        $legend = ProgresUnitReady::orderBy('id', 'asc')
            ->get();

        return view('admin.siteplan.siteplan_unit_ready.index', compact('lokasiKavling', 'legend'));
    }

    public function show($id)
    {
        $data = KavlingPeta::with(['lokasi', 'customer'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    private function generateSVG($id_lokasi, $width = 500, $height = 200)
    {
        $lokasi = LokasiKavling::with(['masterSvg', 'kavlingPeta.unitReady'])
            ->findOrFail($id_lokasi);

        if (! $lokasi->masterSvg) {
            abort(404, "Data master_svg tidak ditemukan");
        }

        ob_start();

        echo $lokasi->masterSvg->header_xml;
        $header = str_replace(['[[lebar]]', '[[tinggi]]'], [$width, $height], $lokasi->masterSvg->header_svg);
        echo $header;

        foreach ($lokasi->kavlingPeta as $pt) {
            $warna = '#ffffff';
            if ($pt->status_ready && $pt->unitReady) {
                $warna = $pt->unitReady->warna;
            }

            $replacements = [$pt->map, $warna, $pt->matrik, $pt->kode_kavling];

            if ($pt->jenis_map === 'polygon') {
                echo str_replace(['[[1]]', '[[2]]', '[[3]]', '[[4]]'], $replacements, $lokasi->masterSvg->polygon_svg);
            } elseif ($pt->jenis_map === 'path') {
                echo str_replace(['[[1]]', '[[2]]', '[[3]]', '[[4]]'], $replacements, $lokasi->masterSvg->path_svg);
            }
        }

        echo $lokasi->masterSvg->footer_svg;

        return ob_get_clean();
    }

    public function cetakJPG($id_lokasi)
    {
        $svgContent = $this->generateSVG($id_lokasi);

        $svgFilename = "siteplan_{$id_lokasi}.svg";

        $svgPath = public_path("svg/{$svgFilename}");
        if (! file_exists(dirname($svgPath))) {
            mkdir(dirname($svgPath), 0755, true);
        }
        file_put_contents($svgPath, $svgContent);

        $endpoint   = 'https://aplikasikavling.com/convert/proses.php';
        $clientName = 'rhabayu_ready';

        $response = Http::attach(
            'svg_file',
            file_get_contents($svgPath),
            $svgFilename
        )->post($endpoint, [
            'client' => $clientName,
        ]);

        if (! $response->successful()) {
            abort(500, "Gagal upload ke server convert: " . $response->body());
        }

        $data = $response->json();
        if (! isset($data['jpg_url'])) {
            abort(500, "Gagal convert ke JPG: " . json_encode($data));
        }

        $jpgUrl = $data['jpg_url'];

        $jpgContent  = Http::get($jpgUrl)->body();
        $jpgFilename = basename(parse_url($jpgUrl, PHP_URL_PATH));

        $jpgPath = public_path("hasil/{$jpgFilename}");
        if (! file_exists(dirname($jpgPath))) {
            mkdir(dirname($jpgPath), 0755, true);
        }
        file_put_contents($jpgPath, $jpgContent);

        return response()->download($jpgPath);
    }

    public function cetakPDF($id_lokasi)
    {
        return response()->view('admin.siteplan.pdf', [
            'svgContent' => $this->generateSVG($id_lokasi),
            'filename' => 'siteplan_' . (int) $id_lokasi . '.pdf',
            'format' => 'PDF',
            'pdfMetadata' => [
                'company' => DB::table('konfigurasi')->value('nama_perusahaan') ?? '',
                'location' => LokasiKavling::findOrFail($id_lokasi)->nama_kavling,
                'date' => now()->translatedFormat('d F Y'),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

}
