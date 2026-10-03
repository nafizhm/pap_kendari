<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\JenisBerkas;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;

class JenisBerkasController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            return datatables()->of(JenisBerkas::orderBy('urutan')->orderBy('nama'))
                ->addIndexColumn()
                ->editColumn('aktif', fn ($row) => $row->aktif
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Tidak Aktif</span>')
                ->addColumn('action', function ($row) use ($permissions) {
                    $buttons = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $buttons .= '<button class="btn btn-primary btn-sm mx-1 edit-button" data-url="' . e(route('jenis-berkas.edit', $row->id)) . '">Edit</button>';
                    }
                    if ($permissions['hapus']) {
                        $buttons .= '<form action="' . e(route('jenis-berkas.destroy', $row->id)) . '" method="POST" style="display:inline">'
                            . csrf_field() . method_field('DELETE')
                            . '<button type="submit" class="btn btn-danger btn-sm mx-1 delete-button">Hapus</button></form>';
                    }
                    return $buttons . '</div>';
                })
                ->rawColumns(['aktif', 'action'])
                ->make(true);
        }

        return view('admin.master.jenis_berkas.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|max:255|unique:jenis_berkas,nama',
            'urutan' => 'required|integer|min:0',
            'aktif' => 'required|boolean',
        ], [
            'nama.required' => 'Nama jenis berkas wajib diisi.',
            'nama.unique' => 'Nama jenis berkas sudah digunakan.',
            'urutan.required' => 'Urutan wajib diisi.',
            'aktif.required' => 'Status wajib dipilih.',
        ]);

        $jenis = JenisBerkas::create($data);
        $this->logCreate('Jenis Berkas', $jenis->id);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => JenisBerkas::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $jenis = JenisBerkas::findOrFail($id);
        $data = $request->validate([
            'nama' => 'required|max:255|unique:jenis_berkas,nama,' . $jenis->id,
            'urutan' => 'required|integer|min:0',
            'aktif' => 'required|boolean',
        ]);

        $jenis->update($data);
        $this->logEdit('Jenis Berkas', $jenis->id);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $jenis = JenisBerkas::findOrFail($id);
        $this->logDelete('Jenis Berkas', $jenis->id);
        $jenis->delete();

        return response()->json(['status' => 'success']);
    }
}
