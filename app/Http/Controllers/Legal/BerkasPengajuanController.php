<?php
namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\JenisBerkas;
use App\Models\PersyaratanLegal;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $data = PersyaratanLegal::with('customer')->orderBy('id', 'desc');

            $table = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_customer', function ($row) {
                    return $row->customer?->nama_lengkap ?? '';
                });

            foreach ($jenisBerkas as $jenis) {
                $table->addColumn('berkas_' . $jenis->id, function ($row) use ($jenis) {
                    $status = (int) (($row->status_jenis_berkas ?? [])[$jenis->id] ?? 0);
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

                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(array_merge(['nama_customer', 'action'], $jenisBerkas->map(fn ($jenis) => 'berkas_' . $jenis->id)->all()))
                ->make(true);
        }

        return view('admin.legal.pengajuan_berkas.index', compact('permissions', 'jenisBerkas'));
    }

    public function edit($id)
    {
        $list = PersyaratanLegal::with('customer')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PersyaratanLegal::findOrFail($id);

        $request->validate([
            'status_berkas'      => 'required|array',
            'status_berkas.*'    => 'required|in:0,1',
            'catatan_kekurangan' => 'nullable',
            'percakapan_wa'      => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
        ], [
            'status_berkas.required'   => 'Status jenis berkas wajib diisi!',
            'status_berkas.*.required' => 'Setiap status jenis berkas wajib dipilih!',
            'percakapan_wa.file'      => 'File percakapan WA harus berupa file!',
            'percakapan_wa.mimes'     => 'File percakapan WA harus berupa gambar dengan format jpeg, png, atau jpg!',
            'percakapan_wa.max'       => 'Ukuran file percakapan WA maksimal 2MB!',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('percakapan_wa')) {
                if (! empty($data->percakapan_wa) && file_exists(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa))) {
                    unlink(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa));
                }

                $foto             = $request->file('percakapan_wa');
                $ext              = $foto->getClientOriginalExtension();
                $percakapanwaName = Str::random(25) . '.' . $ext;
                $foto->move(public_path('assets/legal/pengajuan_berkas/percakapan_wa/'), $percakapanwaName);
            }

            $statusBerkas = collect($request->status_berkas)
                ->mapWithKeys(fn ($status, $jenisId) => [(string) $jenisId => (int) $status])
                ->all();

            $update = [
                'status_jenis_berkas' => $statusBerkas,
                'catatan_kekurangan' => $request->catatan_kekurangan ?? '',
                'percakapan_wa'      => isset($percakapanwaName) ? $percakapanwaName : $data->percakapan_wa,
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

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
