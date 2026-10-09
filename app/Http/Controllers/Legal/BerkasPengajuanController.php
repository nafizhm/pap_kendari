<?php
namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\JenisBerkas;
use App\Models\LokasiKavling;
use App\Models\PersyaratanLegal;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BerkasPengajuanController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        $jenisBerkas = JenisBerkas::where('aktif', 1)->orderBy('urutan')->orderBy('nama')->get();

        if ($request->ajax()) {
            $request->validate(['id_lokasi' => 'nullable|integer|exists:lokasi_kavling,id']);
            $data = PersyaratanLegal::with('customer')->orderBy('id', 'desc')
                ->when($request->filled('id_lokasi'), fn ($query) => $query->whereHas('customer', fn ($customer) => $customer->where('id_lokasi', $request->id_lokasi)));

            $table = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_customer', function ($row) {
                    return $row->customer?->nama_lengkap ?? '';
                });

            foreach ($jenisBerkas as $jenis) {
                $table->addColumn('berkas_' . $jenis->id, function ($row) use ($jenis) {
                    $status = !empty(($row->file_jenis_berkas ?? [])[$jenis->id]) ? 1 : (int) (($row->status_jenis_berkas ?? [])[$jenis->id] ?? 0);
                    $filename = ($row->file_jenis_berkas ?? [])[$jenis->id] ?? null;
                    if ($filename) {
                        return '<button type="button" class="btn btn-link p-0 legal-preview" data-url="' . e(asset('assets/legal/pengajuan_berkas/berkas/' . $filename)) . '" data-title="' . e($jenis->nama) . '" aria-label="Lihat ' . e($jenis->nama) . '"><i class="fas fa-check-circle text-success"></i></button>';
                    }
                    return $status === 1
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>';
                });
            }

            return $table

                ->addColumn('action', function ($row) use ($permissions): string {
                    $editUrl = route('pengajuan-berkas.edit', $row->id);
                    $hasCust = $row->customer ? true : false;

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button ' . ($hasCust ? '' : 'disabled') . '" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';

                    }

                    $btn .= '<button type="button" class="btn btn-secondary btn-sm ml-1 legal-print" data-url="' . e(route('pengajuan-berkas.edit', $row->id)) . '" data-print-url="' . e(route('pengajuan-berkas.pdf', $row->id)) . '"><i class="fas fa-print"></i> Cetak</button></div>';
                    return $btn;
                })
                ->rawColumns(array_merge(['nama_customer', 'action'], $jenisBerkas->map(fn ($jenis) => 'berkas_' . $jenis->id)->all()))
                ->make(true);
        }

        $lokasiList = LokasiKavling::orderBy('nama_kavling')->get();
        return view('admin.legal.pengajuan_berkas.index', compact('permissions', 'jenisBerkas', 'lokasiList'));
    }

    public function edit($id)
    {
        $list = PersyaratanLegal::with('customer')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
            'jenis' => JenisBerkas::orderBy('urutan')->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function pdf(Request $request, $id)
    {
        $validated = $request->validate(['urutan' => 'required|array|min:1', 'urutan.*' => 'required|integer|distinct|exists:jenis_berkas,id']);
        $data = PersyaratanLegal::findOrFail($id);
        $files = $data->file_jenis_berkas ?? [];
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        foreach ($validated['urutan'] as $jenisId) {
            $filename = $files[$jenisId] ?? null;
            abort_unless($filename && basename($filename) === $filename, 422, 'Berkas yang dipilih belum diunggah.');
            $path = public_path('assets/legal/pengajuan_berkas/berkas/' . $filename);
            abort_unless(is_file($path), 422, 'File berkas tidak ditemukan.');
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                try {
                    $pages = $pdf->setSourceFile($path);
                    for ($page = 1; $page <= $pages; $page++) {
                        $template = $pdf->importPage($page);
                        $size = $pdf->getTemplateSize($template);
                        $pdf->AddPage('P', 'A4');
                        $scale = min(1, 190 / $size['width'], 277 / $size['height']);
                        $width = $size['width'] * $scale;
                        $height = $size['height'] * $scale;
                        $pdf->useTemplate($template, ($pdf->getPageWidth() - $width) / 2,
                            ($pdf->getPageHeight() - $height) / 2, $width, $height);
                    }
                } catch (\Throwable $e) {
                    abort(422, 'PDF ini tidak dapat digabung. Gunakan PDF tanpa proteksi atau unggah sebagai gambar.');
                }
            } else {
                $image = file_get_contents($path);
                if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'webp') {
                    $resource = imagecreatefromwebp($path);
                    ob_start(); imagepng($resource); $image = ob_get_clean(); imagedestroy($resource);
                }
                $size = getimagesizefromstring($image);
                abort_unless($size, 422, 'Gambar tidak dapat dibaca.');
                $width = $size[0] * 25.4 / 96;
                $height = $size[1] * 25.4 / 96;
                $scale = min(1, 190 / $width, 277 / $height);
                $pdf->AddPage('P', 'A4');
                $width *= $scale;
                $height *= $scale;
                $pdf->Image('@' . $image, ($pdf->getPageWidth() - $width) / 2,
                    ($pdf->getPageHeight() - $height) / 2, $width, $height);
            }
        }
        return response($pdf->Output('berkas-customer-' . $data->id . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="berkas-customer-' . $data->id . '.pdf"',
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PersyaratanLegal::findOrFail($id);

        $ids = JenisBerkas::where('aktif', 1)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $arrayRule = $ids ? 'array:' . implode(',', $ids) : 'array|size:0';
        $request->validate([
            'status_berkas'      => 'nullable|' . $arrayRule,
            'status_berkas.*'    => 'required|in:0,1',
            'catatan_kekurangan' => 'nullable|string',
            'berkas_files' => 'nullable|' . $arrayRule,
            'berkas_files.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'status_berkas.required'   => 'Status jenis berkas wajib diisi!',
            'status_berkas.*.required' => 'Setiap status jenis berkas wajib dipilih!',
        ]);

        $newFiles = [];
        $oldFiles = [];
        $folder = public_path('assets/legal/pengajuan_berkas/berkas');
        DB::beginTransaction();
        try {
            $data = PersyaratanLegal::lockForUpdate()->findOrFail($id);
            $statusBerkas = array_replace($data->status_jenis_berkas ?? [], collect($request->input('status_berkas', []))
                ->mapWithKeys(fn ($status, $jenisId) => [(string) $jenisId => (int) $status])
                ->all());
            $files = $data->file_jenis_berkas ?? [];
            foreach ($request->file('berkas_files', []) as $jenisId => $upload) {
                File::ensureDirectoryExists($folder);
                $filename = (string) Str::uuid() . '.' . $upload->extension();
                $newFiles[] = $folder . '/' . $filename;
                $upload->move($folder, $filename);
                if (!empty($files[$jenisId])) $oldFiles[] = $folder . '/' . $files[$jenisId];
                $files[$jenisId] = $filename;
            }
            foreach ($files as $jenisId => $filename) {
                if ($filename) $statusBerkas[$jenisId] = 1;
            }

            $update = [
                'status_jenis_berkas' => $statusBerkas,
                'catatan_kekurangan' => $request->catatan_kekurangan ?? '',
                'file_jenis_berkas' => $files,
            ];

            $legacyColumns = [
                'IPH' => 'IPH', 'SHGB' => 'SHGB', 'SSP' => 'SSP', 'BPHTB' => 'BPHTB',
                'SIKUMBANG' => 'SIKUMBANG', 'DAFTAR SIKASEP' => 'DAFTAR_SIKASEP',
                'FOTO SIKASEP' => 'FOTO_SIKASEP', 'TRILOGI' => 'TRILOGI',
            ];
            foreach (JenisBerkas::whereIn('nama', array_keys($legacyColumns))->get() as $jenis) {
                $update[$legacyColumns[$jenis->nama]] = $statusBerkas[$jenis->id] ?? 0;
            }

            $data->update($update);

            $this->logEdit('Berkas Pengajuan', $data->id);

            DB::commit();
            try {
                File::delete($oldFiles);
            } catch (\Throwable $cleanupError) {
                \Illuminate\Support\Facades\Log::warning('Berkas legal tersimpan, file lama belum dapat dibersihkan.', ['id' => $data->id]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            DB::rollBack();
            File::delete($newFiles);
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
