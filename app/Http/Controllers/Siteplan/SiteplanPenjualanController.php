<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Pemasukan;
use App\Models\Piutang;
use App\Models\ProgresListPenjualan;
use Illuminate\Support\Facades\DB;
use App\Models\ListrikAir;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\PengaturanMedia;

class SiteplanPenjualanController extends Controller
{
    public function index()
    {
         $lokasiKavling = LokasiKavling::with([
        'masterSvg',
        'kavlingPeta' => fn ($query) => $query->withBookingState(), 'kavlingPeta.customer.progres'  // ← tambahkan ini
    ])->orderBy('urutan', 'asc')->get();

        $legend = ProgresListPenjualan::whereNotNull('warna')
            ->where('warna', '!=', '')
            ->where('stt_tampil', 1)
            ->orderBy('urutan', 'asc')
            ->get();

        return view('admin.siteplan.siteplan_penjualan.index', compact('lokasiKavling', 'legend'));
    }


    public function show($id)
    {
        $data = KavlingPeta::withBookingState()->with(['lokasi', 'customer.marketing', 'customer.progres', 'listrikAir'])->findOrFail($id);

        $customerId = $data->customer?->id;
        $tagihanList = $customerId ? Piutang::where('id_customer', $customerId)->orderBy('id')->get() : collect();
        $pemasukanList = $customerId ? Pemasukan::with('kategori')->where('id_customer', $customerId)->get() : collect();

        return response()->json([
            'success'         => true,
            'data'            => $data,
            'tagihan'         => $tagihanList,
            'listrik_air' => $data->listrikAir,
            'pemasukan'       => $pemasukanList,
            'total_tagihan'   => $tagihanList->sum('nominal'),
            'total_pemasukan' => $pemasukanList->sum('nominal'),
        ]);
    }

    private function generateSVG($id_lokasi, $width = '100%', $height = '100%')
    {
        $lokasi = LokasiKavling::with(['masterSvg', 'kavlingPeta' => fn ($query) => $query->withBookingState(), 'kavlingPeta.customer.progres'])
            ->findOrFail($id_lokasi);

        if (! $lokasi->masterSvg) {
            abort(404, "Data master_svg tidak ditemukan");
        }

        ob_start();

        echo str_replace(['[[lebar]]', '[[tinggi]]'], [$width, $height], $lokasi->masterSvg->header_svg);

        foreach ($lokasi->kavlingPeta as $pt) {
            $warna = '#ffffff';

            if ($pt->customer) {
                $warna = $pt->customer->progres->warna ?? '#ffffff';
            } elseif ($pt->is_booked) {
                $warna = '#42f202';
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
        // Rasterize in the browser so shared hosting needs no conversion service.
        return response()->view('admin.siteplan.siteplan_penjualan.jpg', [
            'svgContent' => $this->generateSVG($id_lokasi),
            'filename' => 'siteplan_' . (int) $id_lokasi . '.jpg',
        ])->header('Cache-Control', 'private, no-store');
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

    public function cetak(Request $request)
    {
         $request->validate([
        'kode_kavling' => 'required',
        'id_kavling'   => 'nullable|integer|exists:kavling_peta,id',
        'no_ktp'       => 'nullable',
        ]);

        $kavling = KavlingPeta::withBookingState()->with(['lokasi', 'listrikAir', 'customer.marketing'])
            ->when($request->filled('id_kavling'),
                fn ($query) => $query->whereKey($request->id_kavling),
                fn ($query) => $query->where('kode_kavling', $request->kode_kavling))
            ->first();

        if (!$kavling) {
            return back()->with('error', 'Kavling tidak ditemukan');
        }

        $cust = $kavling->customer;

        $listrikAir = $kavling->listrikAir;

        $namaLokasi = $kavling->lokasi->nama_kavling ?? '-';

        $kavlingData = [
            'status'        => $kavling->sales_status,
            'marketing'     => optional($cust?->marketing)->nama_marketing ?? '-',
            'lokasi'        => $namaLokasi,
            'blok'          => $kavling->blok ?? $kavling->kode_kavling ?? '-',
            'luas_tanah'    => $kavling->luas_tanah ?? '-',
            'luas_bangunan' => $kavling->luas_bangunan ?? '-',
            'daya_listrik'  => $kavling->daya_listrik ?? '-',
            'harga'         => (int) ($kavling->hrg_jual ?? 0),
            'no_sertifikat' => $kavling->no_sertifikat ?? '-',
            'foto'          => null,
        ];

        $customerData = [
            'nama'          => $cust->nama_lengkap ?? '-',
            'no_ktp'        => $cust->nik ?? '-',
            'tempat_lahir'  => $cust->tempat_lahir ?? '-',
            'tanggal_lahir' => $cust->tgl_lahir ?? '-',
            'alamat'        => $cust->alamat_ktp ?? '-',
            'no_hp'         => $cust->no_telp ?? '-',
            'pekerjaan'     => $cust->pekerjaan ?? '-',
        ];

        $listrikAirData = [
            'daya_listrik'   => $kavling->daya_listrik ?? '-',
            'no_sertifikat'  => $kavling->no_sertifikat ?? '-',
            'no_rek_listrik' => $listrikAir->norek_listrik ?? '-',
            'no_rek_air'     => $listrikAir->norek_air ?? '-',
        ];

        $kavlingData = [
            'status'        => $request->status ?? 'Terjual',
            'marketing'     => $cust->marketing->nama_marketing ?? '-',
            'lokasi'        => $namaLokasi,
            'blok'          => $request->blok ?? $request->kode_kavling ?? '-',
            'luas_tanah'    => $request->luas_tanah ?? '-',
            'luas_bangunan' => $request->luas_bangunan ?? '-',
            'daya_listrik'  => $request->daya_listrik ?? '-',
            'harga'         => $kavling->total_harga,
            'no_sertifikat' => $request->no_sertifikat ?? '-',
            'foto'          => null,
        ];

        $customerData = [
            'nama'          => $request->nama_customer ?? '-',
            'no_ktp'        => $request->no_ktp ?? '-',
            'tempat_lahir'  => $request->tempat_lahir ?? '-',
            'tanggal_lahir' => $request->tgl_lahir ?? '-',
            'alamat'        => $request->alamat ?? '-',
            'no_hp'         => $request->no_hp ?? '-',
            'pekerjaan'     => $request->pekerjaan ?? '-',
        ];

        $listrikAirData = [
            'daya_listrik'   => $kavling->daya_listrik ?? '-',
            'no_sertifikat'  => $kavling->no_sertifikat ?? '-',
            'no_rek_listrik' => $listrikAir->norek_listrik ?? '-',
            'no_rek_air'     => $listrikAir->norek_air ?? '-',
        ];

        $pengaturanMedia = PengaturanMedia::where('jenis_data', 'kop surat')->first();
        $logoPath        = null;
        if ($pengaturanMedia && $pengaturanMedia->nama_file) {
            $logoPath = public_path('config_media/' . $pengaturanMedia->nama_file);
        }

        $pdf = new \TCPDF('P', 'mm', 'A4');
        $pdf->SetCreator('Sistem Kavling');
        $pdf->SetAuthor('Sistem Kavling');
        $pdf->SetTitle('Data Unit');
        $pdf->SetMargins(15, 45, 15);
        $pdf->AddPage();

        $tanggalCetak = \Carbon\Carbon::now()->translatedFormat('d F Y');

        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, 0, 5, 210);
        }

        $pdf->SetY(35);

        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 8, 'Tanggal Cetak : ' . $tanggalCetak, 0, 1, 'R');
        $pdf->Ln(3);

        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('times', 'BU', 14);
        $pdf->Cell(0, 4, 'DATA UNIT RUMAH', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('times', '', 8);
        $pdf->Cell(0, 2, 'Status Rumah : ' . $kavlingData['status'], 0, 1, 'C');
        $pdf->Cell(0, 2, 'Marketing : ' . $kavlingData['marketing'], 0, 1, 'C');
        $pdf->Ln(3);

        $pdf->SetFont('times', 'BU', 12);
        $pdf->Cell(0, 8, 'DATA UNIT RUMAH', 0, 1, 'L');
        $pdf->SetFont('times', '', 10);
        $this->rowData($pdf, 'Lokasi Perumahan', $namaLokasi);
        $this->rowData($pdf, 'No. Blok / Kav', $kavlingData['blok']);
        $this->rowData($pdf, 'Luas Tanah', $kavlingData['luas_tanah']);
        $this->rowData($pdf, 'Luas / Tipe Bangunan', $kavlingData['luas_bangunan']);
        $this->rowData($pdf, 'Daya Listrik', $listrikAirData['daya_listrik']);
        foreach ($kavling->rincian_biaya ?? [] as $biaya) {
            $this->rowData($pdf, $biaya['nama'] ?? '-', 'Rp ' . number_format((float) ($biaya['nilai'] ?? 0), 0, ',', '.'));
        }
        $this->rowData($pdf, 'Total Harga', 'Rp ' . number_format($kavlingData['harga'], 0, ',', '.'));
        $pdf->Ln(2);

        $pdf->SetFont('times', 'BU', 12);
        $pdf->Cell(0, 8, 'DATA LEGAL', 0, 1, 'L');
        $pdf->SetFont('times', '', 10);
        $this->rowData($pdf, 'ID Rumah', $kavling->id_rumah_sikumbang ?? '-');
        $this->rowData($pdf, 'No. Sertipikat', $listrikAirData['no_sertifikat']);
        $this->rowData($pdf, 'No. Rekening Listrik', $listrikAirData['no_rek_listrik']);
        $this->rowData($pdf, 'No. Rekening Air', $listrikAirData['no_rek_air']);
        $pdf->Ln(2);

        $pdf->SetFont('times', 'BU', 12);
        $pdf->Cell(0, 8, 'DATA CUSTOMER', 0, 1, 'L');
        $pdf->SetFont('times', '', 10);
        $this->rowData($pdf, 'Nama Lengkap', $customerData['nama']);
        $this->rowData($pdf, 'No. KTP', $customerData['no_ktp']);
        $this->rowData($pdf, 'Tempat / Tgl Lahir', ($customerData['tempat_lahir'] ?? '-') . ', ' . ($customerData['tanggal_lahir'] ?? '-'));
        $this->rowData($pdf, 'Alamat KTP', $customerData['alamat']);
        $this->rowData($pdf, 'No. Telp / WA', $customerData['no_hp']);
        $this->rowData($pdf, 'Pekerjaan', $customerData['pekerjaan']);
        $pdf->Ln(2);


        $pdf->Output('Data_Unit_' . ($kavlingData['blok'] ?? 'unknown') . '.pdf', 'I');
    }

     private function rowData($pdf, $label, $value)
    {
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(60, 7, $label, 0, 0, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->MultiCell(0, 7, ': ' . $value, 0, 'L', false, 1);
    }
}
