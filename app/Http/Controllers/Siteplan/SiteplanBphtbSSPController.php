<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Pemasukan;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SiteplanBphtbSSPController extends Controller
{
    public function index()
    {
        $lokasiKavling = LokasiKavling::orderBy('urutan', 'asc')->get();

        return view('admin.siteplan.siteplan_bphtb_ssp.index', compact('lokasiKavling'));
    }

    public function show($id)
    {
        $data = KavlingPeta::with(['lokasi', 'customer'])->findOrFail($id);

        $tagihanList   = $data->customer ? Piutang::where('id_customer', $data->customer->id)->orderBy('id')->get() : collect();
        $pemasukanList = $data->customer ? Pemasukan::with('kategori')->where('id_customer', $data->customer->id)->get() : collect();

        return response()->json([
            'success'         => true,
            'data'            => $data,
            'tagihan'         => $tagihanList,
            'pemasukan'       => $pemasukanList,
            'total_tagihan'   => $tagihanList->sum('nominal'),
            'total_pemasukan' => $pemasukanList->sum('nominal'),
        ]);
    }

    private function generateSVG($id_lokasi, $width = '100%', $height = '100%')
    {
        $lokasi = LokasiKavling::with([
            'masterSvg',
            'kavlingPeta.BphtbSsp'
        ])->findOrFail($id_lokasi);


        if (! $lokasi->masterSvg) {
            abort(404, "Data master_svg tidak ditemukan");
        }

        ob_start();

        echo str_replace(['[[lebar]]', '[[tinggi]]'], [$width, $height], $lokasi->masterSvg->header_svg);

        foreach ($lokasi->kavlingPeta as $pt) {

                $warna = '#ffffff';

                if ($pt->BphtbSsp) {
                    $hasBphtb = $pt->BphtbSsp->status_bphtb === 'ada';
                    $hasSsp   = $pt->BphtbSsp->status_ssp === 'ada';

                    if ($hasBphtb && $hasSsp) {
                        $warna = '#ff5454';
                    } elseif ($hasBphtb) {
                        $warna = '#ffb554';
                    } elseif ($hasSsp) {
                        $warna = '#ffe254'; 
                    }
                }

                $replacements = [
                    $pt->map,
                    $warna,
                    $pt->matrik,
                    $pt->kode_kavling
                ];

                if ($pt->jenis_map === 'polygon') {
                    echo str_replace(
                        ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                        $replacements,
                        $lokasi->masterSvg->polygon_svg
                    );
                } elseif ($pt->jenis_map === 'path') {
                    echo str_replace(
                        ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                        $replacements,
                        $lokasi->masterSvg->path_svg
                    );
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
        $clientName = 'rhabayu_marketing';

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
