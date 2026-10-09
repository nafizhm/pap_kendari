<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\BerkasBooking;
use App\Models\HakAkses;
use App\Models\Menu;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BerkasBookingController extends Controller
{
    use LogAktivitasTrait;

    private function authorizeAction(string $action): void
    {
        $menuId = Menu::where('route_name', 'berkas-booking.index')->value('id');
        abort_unless($menuId && HakAkses::where('id_user', auth()->id())->where('id_menu', $menuId)->where($action, 1)->exists(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAction('lihat');
        $permissions = HakAksesController::getUserPermissions();
        if ($request->ajax()) {
            return datatables()->of(BerkasBooking::orderBy('urutan')->orderBy('id'))
                ->addIndexColumn()
                ->editColumn('wajib', fn ($row) => $row->wajib ? 'Wajib' : 'Opsional')
                ->editColumn('aktif', fn ($row) => $row->aktif ? 'Aktif' : 'Tidak Aktif')
                ->addColumn('action', function ($row) use ($permissions) {
                    $buttons = '';
                    if ($permissions['edit']) {
                        $buttons .= '<button class="btn btn-primary btn-sm mx-1 edit-button" data-url="' . e(route('berkas-booking.edit', $row->id)) . '">Edit</button>';
                    }
                    if ($permissions['hapus']) {
                        $buttons .= '<form action="' . e(route('berkas-booking.destroy', $row->id)) . '" method="POST" style="display:inline">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-danger btn-sm mx-1 delete-button">Hapus</button></form>';
                    }
                    return $buttons;
                })->rawColumns(['action'])->make(true);
        }
        return view('admin.master.berkas_booking.index', compact('permissions'));
    }

    private function validated(Request $request, ?BerkasBooking $type = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('berkas_booking', 'nama')->whereNull('deleted_at')->ignore($type?->id)],
            'urutan' => 'required|integer|min:0|max:100000',
            'wajib' => 'required|boolean',
            'aktif' => 'required|boolean',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction('tambah');
        $type = BerkasBooking::create(array_merge($this->validated($request), ['kode' => 'berkas_' . Str::uuid()->getHex()]));
        $this->logCreate('Berkas Booking', $type->id);
        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $this->authorizeAction('edit');
        return response()->json(['status' => 'success', 'data' => BerkasBooking::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAction('edit');
        $type = BerkasBooking::findOrFail($id);
        $type->update($this->validated($request, $type));
        $this->logEdit('Berkas Booking', $type->id);
        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $this->authorizeAction('hapus');
        $type = BerkasBooking::findOrFail($id);
        $type->delete();
        $this->logDelete('Berkas Booking', $type->id);
        return response()->json(['status' => 'success']);
    }
}
