<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MarketingOffline;
use App\Models\MetodeBayar;
use App\Models\Pemasukan;
use App\Models\PengajuanHold;
use App\Models\PengajuanHoldTempo;
use App\Models\PersyaratanLegal;
use App\Models\Piutang;
use App\Models\PengaturanMedia;
use App\Models\ProgresListPenjualan;
use App\Models\UploudFile;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PengajuanHoldController extends Controller
{
    use LogAktivitasTrait;
    protected GenerateNumberController $generator;

    public function __construct(GenerateNumberController $generator)
    {
        $this->generator = $generator;
    }

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = PengajuanHold::with(['marketing', 'lokasi', 'kavling'])->where('stt_reg', '!=', 2)->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_lengkap', function ($row) {
                    $nama   = '<strong>' . e($row->nama_lengkap) . '</strong>';
                    $noTelp = $row->no_telp
                        ? '<br><small class="text-primary">' . e($row->no_telp) . '</small>'
                        : '';

                    return $nama . $noTelp;
                })

                ->addColumn('stt_reg', function ($row): string {
                    switch ($row->stt_reg) {
                        case 1:
                            return '<span class="badge bg-dark">Pending</span>';
                        case 2:
                            return '<span class="badge bg-success">Disetujui</span>';
                        case 3:
                            return '<span class="badge bg-danger">Ditolak</span>';
                        default:
                            return '<span class="badge bg-secondary">Unknown</span>';
                    }
                })

                ->addColumn('nama_marketing', function ($row) {
                    if ((int) $row->id_marketing === 0) {
                        return 'Non Marketing';
                    }

                    return $row->marketing->nama_marketing ?? '-';
                })

                ->addColumn('kode_kavling', function ($row) {
                    $namaLokasi  = '<strong>' . ($row->lokasi->nama_kavling ?? '-') . '</strong>';
                    $kodeKavling = $row->kavling->kode_kavling ?? '-';

                    return $namaLokasi . '<br>' . $kodeKavling;
                })
                ->addColumn('action', function ($row) use ($permissions): string {
                    $deleteUrl   = route('pengajuan-hold.destroy', $row->id);
                    $verifUrl    = route('pengajuan-hold.verifikasi', $row->id);

                    $btn = '<div class="text-start">';

                    if ($permissions['edit'] && $row->stt_reg != 2) {
                        $btn .= '<a class="btn btn-primary btn-xs mr-1" href="' . e($verifUrl) . '">Verifikasi</a>';
                    }

                    if ($permissions['hapus'] && $row->stt_reg != 2) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="delete-button btn btn-danger btn-xs">Hapus</button></form>';
                    }

                    $btn .= '</div>';

                    return $btn;
                })

                ->rawColumns([
                    'nama_marketing',
                    'nama_lengkap',
                    'kode_kavling',
                    'stt_reg',
                    'action',
                ])

                ->make(true);
        }

        Carbon::setLocale('id');

        $marketing = MarketingOffline::all();
        $bank      = Bank::all();
        $progres   = ProgresListPenjualan::all();
        $lokasi    = LokasiKavling::all();

        return view('admin.pengajuan_hold.index', compact('permissions', 'marketing', 'lokasi', 'progres', 'bank'));
    }

    public function viewArsip(Request $request)
    {

        if ($request->ajax()) {
            $data = PengajuanHold::with(['marketing', 'lokasi', 'kavling'])->where('stt_reg', 2)->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_lengkap', function ($row) {
                    $nama   = '<strong>' . e($row->nama_lengkap) . '</strong>';
                    $noTelp = $row->no_telp ? '<br><small class="text-muted">' . e($row->no_telp) . '</small>' : '';

                    return $nama . $noTelp;
                })
                ->addColumn('stt_reg', function ($row): string {
                    switch ($row->stt_reg) {
                        case 1:
                            return '<span class="badge bg-warning">Pending</span>';
                        case 2:
                            return '<span class="badge bg-success">Disetujui</span>';
                        case 3:
                            return '<span class="badge bg-danger">Ditolak</span>';
                        default:
                            return '<span class="badge bg-secondary">Unknown</span>';
                    }
                })

                ->addColumn('nama_marketing', function ($row) {
                    return $row->marketing->nama_marketing ?? '-';
                })
                ->addColumn('kode_kavling', function ($row) {
                    $namaLokasi  = '<strong>' . ($row->lokasi->nama_kavling ?? '-') . '</strong>';
                    $kodeKavling = $row->kavling->kode_kavling ?? '-';

                    return $namaLokasi . '<br>' . $kodeKavling;
                })
                ->addColumn('action', function ($row) {
                    $verifUrl  = route('pengajuan-hold.arsip.detail', $row->id);
                    $btn       = '<div class="text-start">';
                    $btn      .= '<a class="btn btn-primary btn-sm mr-1" href="' . e($verifUrl) . '">Detail</a>';
                    $btn      .= '</div>';

                    return $btn;
                })

                ->rawColumns([
                    'nama_marketing',
                    'nama_lengkap',
                    'kode_kavling',
                    'stt_reg',
                    'action',
                ])
                ->make(true);
        }

        Carbon::setLocale('id');

        $marketing = MarketingOffline::all();
        $bank      = Bank::all();
        $progres   = ProgresListPenjualan::all();
        $lokasi    = LokasiKavling::all();

        return view('admin.pengajuan_hold.arsip', compact('marketing', 'lokasi', 'progres', 'bank'));
    }

    public function arsipDetail($id, Request $request)
    {
        Carbon::setLocale('id');

        $data            = PengajuanHold::findOrFail($id);
        $bankList        = Bank::all();
        $metodeBayarList = MetodeBayar::all();

        if (! empty($data->tgl_booking)) {
            $data->tgl_booking_formatted = Carbon::createFromFormat('Y-m-d', $data->tgl_booking)
                ->locale('id')
                ->translatedFormat('j F Y');
        } else {
            $data->tgl_booking_formatted = null;
        }

        if (! empty($data->tgl_lahir)) {
            $data->tgl_lahir_formatted = Carbon::createFromFormat('Y-m-d', $data->tgl_lahir)
                ->locale('id')
                ->translatedFormat('j F Y');
        } else {
            $data->tgl_lahir_formatted = null;
        }

        return view('admin.pengajuan_hold.detail', compact('data', 'bankList', 'metodeBayarList'));
    }

    public function edit($id)
    {
        $list = PengajuanHold::with('kavling')->findOrFail($id);

        if (! empty($list->tgl_booking)) {
            $list->tgl_booking_formatted = Carbon::createFromFormat('Y-m-d', $list->tgl_booking)
                ->locale('id')
                ->translatedFormat('j F Y');
        } else {
            $list->tgl_booking_formatted = null;
        }

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PengajuanHold::findOrFail($id);

        $request->merge([
            'booking_fee' => $request->booking_fee ? str_replace('.', '', $request->booking_fee) : 0,
            'total_harga' => $request->total_harga ? str_replace('.', '', $request->total_harga) : 0,
        ]);

        $request->validate([
            'nama_lengkap'     => 'required',
            'nik'              => 'required',
            'tempat_lahir'     => 'required',
            'tgl_lahir'        => 'required|date',
            'jenis_kelamin'    => 'required',
            'no_telp'          => 'required',
            'alamat_ktp'       => 'required',
            'alamat_domisili'  => 'required',
            'email'            => 'nullable|email',
            'id_lokasi'        => 'required',
            'id_kavling'       => 'required',
            'total_harga'      => 'required',
            'id_marketing'     => 'required',
            'booking_fee'      => 'required|gt:0',
            'jenis_perumahan'  => 'required',
            'jenis_pembelian'  => 'required',
        ], [
            'nama_lengkap.required'     => 'Nama lengkap wajib diisi.',
            'nik.required'              => 'NIK wajib diisi.',
            'tempat_lahir.required'     => 'Tempat lahir wajib diisi.',
            'tgl_lahir.required'        => 'Tanggal lahir wajib diisi.',
            'tgl_lahir.date'            => 'Tanggal lahir harus berupa tanggal yang valid.',
            'jenis_kelamin.required'    => 'Jenis kelamin wajib dipilih.',
            'no_telp.required'          => 'Nomor telepon wajib diisi.',
            'alamat_ktp.required'       => 'Alamat KTP wajib diisi.',
            'alamat_domisili.required'  => 'Alamat domisili wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'id_lokasi.required'        => 'Lokasi wajib dipilih.',
            'id_kavling.required'       => 'Kavling wajib dipilih.',
            'total_harga.required'      => 'Total harga wajib diisi.',
            'id_marketing.required'     => 'Marketing wajib dipilih.',
            'booking_fee.required'      => 'Booking fee wajib diisi.',
            'booking_fee.gt'            => 'Booking fee harus lebih dari 0.',
            'jenis_perumahan.required'  => 'Jenis Perumahan wajib dipilih.',
            'jenis_pembelian.required'  => 'Jenis Pembelian wajib dipilih.',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            $db = [
                'nama_lengkap'      => $request->nama_lengkap,
                'nik'               => $request->nik,
                'no_telp'           => $request->no_telp,
                'email'             => $request->email,
                'alamat_ktp'        => $request->alamat_ktp,
                'alamat_domisili'   => $request->alamat_domisili,
                'jenis_kelamin'     => $request->jenis_kelamin,
                'tempat_lahir'      => $request->tempat_lahir,
                'tgl_lahir'         => $request->tgl_lahir,
                'npwp'              => $request->npwp,
                'pekerjaan'         => $request->pekerjaan,
                'status_pernikahan' => $request->status_pernikahan,
                'nama_p'            => $request->nama_p,
                'nik_p'             => $request->nik_p,
                'nama_saudara'      => $request->nama_saudara,
                'no_telp_saudara'   => $request->no_telp_saudara,
                'no_bpjs_kes'       => $request->no_bpjs_kes,
                'id_lokasi'         => $request->id_lokasi,
                'id_kavling'        => $request->id_kavling,
                'booking_fee'       => $request->booking_fee,
                'total_harga'       => $request->total_harga,
                'id_marketing'      => $request->id_marketing,
                'jenis_perumahan'   => $request->jenis_perumahan,
                'jenis_pembelian'   => $request->jenis_pembelian,
            ];

            if ($user->role == 2) {
                $existingData = $data->toArray();
                unset($existingData['id']);

                $tempoData = array_merge($existingData, $db, [
                    'id_pengajuan_hold' => $data->id,
                    'id_user'           => $user->id,
                ]);

                PengajuanHoldTempo::create($tempoData);
            } else {
                $kavling = KavlingPeta::lockForUpdate()->findOrFail($request->id_kavling);
                if (! KavlingPeta::whereKey($kavling->id)->available($data->id)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['id_kavling' => 'Kavling sudah digunakan booking atau customer lain.']);
                }

                $data->update($db);
                $this->logEdit('Pengajuan Hold', $data->id);
            }

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

    public function show($id)
    {
        $data = PengajuanHold::findOrFail($id);

        return view('admin.pengajuan_hold.lampiran', compact('data'));
    }

    public function booking(Request $request)
    {
        Carbon::setLocale('id');

        $marketing = MarketingOffline::all();
        $bank      = Bank::all();
        $progres   = ProgresListPenjualan::all();
        $lokasi    = LokasiKavling::all();
        $bg        = PengaturanMedia::where('jenis_data', 'Background booking')->first();
        $tgl = Carbon::now()->translatedFormat('j F Y');

        return view('frontend.booking.index', compact('marketing', 'lokasi', 'progres', 'bank', 'tgl', 'bg'));
    }

    public function bookingSukses()
    {
        $bg        = PengaturanMedia::where('jenis_data', 'Background booking')->first();
        return view('frontend.booking.sukses', compact('bg'));
    }

    public function upload(Request $request, $id)
    {
        $data = PengajuanHold::findOrFail($id);

        $fotoKtpRule = empty($data->foto_ktp) ? 'required|mimes:jpg,jpeg,png' : 'nullable|mimes:jpg,jpeg,png';

        $rules = [
            'foto_ktp'     => $fotoKtpRule,
            'foto_npwp'    => 'nullable|mimes:jpg,jpeg,png',
            'foto_kk'      => 'nullable|mimes:jpg,jpeg,png',
            'foto_bpjs'    => 'nullable|mimes:jpg,jpeg,png',
            'foto_ktp_p'   => 'nullable|mimes:jpg,jpeg,png',
            'file_bukti'   => 'nullable|mimes:jpg,jpeg,png,pdf',
            'foto_pemohon' => 'nullable|mimes:jpg,jpeg,png',
        ];

        $messages = [
            'foto_ktp.required'  => 'Foto KTP wajib diunggah.',
            'foto_ktp.mimes'     => 'Foto KTP harus berformat JPG atau PNG.',
            'foto_npwp.mimes'    => 'Foto NPWP harus berformat JPG atau PNG.',
            'foto_kk.mimes'      => 'Foto KK harus berformat JPG atau PNG.',
            'foto_bpjs.mimes'    => 'Foto BPJS harus berformat JPG atau PNG.',
            'foto_ktp_p.mimes'   => 'Foto KTP pasangan harus berformat JPG atau PNG.',
            'file_bukti.mimes'   => 'File bukti harus berformat JPG, PNG, atau PDF.',
            'foto_pemohon.mimes' => 'Foto pemohon harus berformat JPG atau PNG.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {

            $folder = public_path('assets/booking');

            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $nama_file_ktp     = $this->simpanFile($request->file('foto_ktp'), $folder);
            $nama_file_npwp    = $this->simpanFile($request->file('foto_npwp'), $folder);
            $nama_file_kk      = $this->simpanFile($request->file('foto_kk'), $folder);
            $nama_file_bpjs    = $this->simpanFile($request->file('foto_bpjs'), $folder);
            $nama_file_bukti   = $this->simpanFile($request->file('file_bukti'), $folder);
            $nama_file_pemohon = $this->simpanFile($request->file('foto_pemohon'), $folder);
            $nama_file_ktp_p   = $this->simpanFile($request->file('foto_ktp_p'), $folder);

            $nama_file_ktp_p = null;
            if ($request->hasFile('foto_ktp_p')) {
                $nama_file_ktp_p = $this->simpanFile($request->file('foto_ktp_p'), $folder);
            }

            $db = [
                'foto_ktp'     => $nama_file_ktp ?? $data->foto_ktp,
                'foto_npwp'    => $nama_file_npwp ?? $data->foto_npwp,
                'foto_kk'      => $nama_file_kk ?? $data->foto_kk,
                'foto_bpjs'    => $nama_file_bpjs ?? $data->foto_bpjs,
                'foto_ktp_p'   => $nama_file_ktp_p ?? $data->foto_ktp_p,
                'file_bukti'   => $nama_file_bukti ?? $data->file_bukti,
                'foto_pemohon' => $nama_file_pemohon ?? $data->foto_pemohon,
            ];

            $data->update($db);

            DB::commit();

            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui Booking.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteFile(Request $request, $id)
    {
        $field   = $request->input('field');
        $allowed = ['foto_pemohon', 'foto_ktp_p', 'file_bukti', 'foto_ktp', 'foto_npwp', 'foto_kk', 'foto_bpjs'];

        if (! in_array($field, $allowed)) {
            return response()->json(['success' => false]);
        }

        $data = PengajuanHold::findOrFail($id);

        if ($data->$field) {
            $filePath = public_path('assets/booking/' . $data->$field);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $data->$field = null;
            $data->save();
        }

        return response()->json(['success' => true]);
    }

    public function getKavling($id)
    {
        $kavling = KavlingPeta::where('id_lokasi', $id)
            ->available()
            ->orderBy('kode_kavling', 'asc')
            ->get(['id', 'kode_kavling']);

        return response()->json($kavling);
    }

    public function getHargaKavling($id)
    {
        $data = KavlingPeta::findOrFail($id);

        $rincian = $data->rincian_biaya ?? [];
        $total   = collect($rincian)->sum('nilai');

        $formatted = [];
        foreach ($rincian as $item) {
            $formatted[] = [
                'nama'   => $item['nama'] ?? '',
                'nilai'  => (int) ($item['nilai'] ?? 0),
                'format' => number_format((int) ($item['nilai'] ?? 0), 0, ',', '.'),
            ];
        }

        return response()->json([
            'rincian_biaya' => $rincian,
            'total_harga'   => $total,
            'formatted'     => $formatted,
        ]);
    }

    public function getKavlingHold($id)
    {
        $kavling = KavlingPeta::where('id_lokasi', $id)
            ->get(['id', 'kode_kavling']);

        return response()->json($kavling);
    }

    public function getHargaKavlingHold($id)
    {
        $data   = KavlingPeta::findOrFail($id);
        $rincian = $data->rincian_biaya ?? [];
        $total   = collect($rincian)->sum('nilai');

        $formatted = [];
        foreach ($rincian as $item) {
            $formatted[] = [
                'nama'   => $item['nama'] ?? '',
                'nilai'  => (int) ($item['nilai'] ?? 0),
                'format' => number_format((int) ($item['nilai'] ?? 0), 0, ',', '.'),
            ];
        }

        return response()->json([
            'rincian_biaya' => $rincian,
            'total_harga'   => $total,
            'formatted'     => $formatted,
        ]);
    }

    public function bookingStore(Request $request)
    {
        $request->merge([
            'booking_fee' => $request->booking_fee ? str_replace('.', '', $request->booking_fee) : 0,
            'total_harga' => $request->total_harga ? str_replace('.', '', $request->total_harga) : 0,
        ]);

        $request->validate([
            'nama_lengkap'     => 'required',
            'nik'              => 'required|digits:16',
            'tempat_lahir'     => 'required',
            'tgl_lahir'        => 'required|date',
            'jenis_kelamin'    => 'required',
            'no_telp'          => 'required',
            'npwp'             => 'nullable',
            'alamat_ktp'       => 'required',
            'alamat_domisili'  => 'required',
            'email'            => 'nullable|email',
            'id_lokasi'        => 'required|exists:lokasi_kavling,id',
            'id_kavling'       => 'required|exists:kavling_peta,id',
            'total_harga'      => 'required|numeric|gt:0',
            'id_marketing'     => 'required',
            'status_pernikahan'=> 'required',
            'booking_fee'      => 'required|gt:0',
            'jenis_perumahan'  => 'required',
            'jenis_pembelian'  => 'required',
            'foto_ktp'         => 'required|mimes:jpg,jpeg,png|max:2048',
            'foto_npwp'        => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_kk'          => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_bpjs'        => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_ktp_p'       => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'file_bukti'       => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'file_sppr'        => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'foto_pemohon'     => 'nullable|mimes:jpg,jpeg,png|max:2048',
        ], [
            'nama_lengkap.required'     => 'Nama lengkap wajib diisi.',
            'nik.required'              => 'NIK wajib diisi.',
            'nik.digits'                => 'NIK harus 16 digit.',
            'tempat_lahir.required'     => 'Tempat lahir wajib diisi.',
            'tgl_lahir.required'        => 'Tanggal lahir wajib diisi.',
            'tgl_lahir.date'            => 'Tanggal lahir harus berupa tanggal yang valid.',
            'jenis_kelamin.required'    => 'Jenis kelamin wajib dipilih.',
            'no_telp.required'          => 'Nomor telepon wajib diisi.',
            // 'npwp.required'             => 'NPWP wajib diisi.',
            'alamat_ktp.required'       => 'Alamat KTP wajib diisi.',
            'alamat_domisili.required'  => 'Alamat domisili wajib diisi.',
            'email.email'               => 'Format email tidak valid.',
            'id_lokasi.required'        => 'Lokasi wajib dipilih.',
            'id_lokasi.exists'          => 'Lokasi yang dipilih tidak ditemukan.',
            'status_pernikahan.required'=> 'Status pernikahan wajib dipilih.',
            'id_kavling.required'       => 'Kavling wajib dipilih.',
            'id_kavling.exists'         => 'Kavling yang dipilih tidak ditemukan.',
            'total_harga.required'      => 'Total harga wajib diisi.',
            'total_harga.numeric'       => 'Total harga harus berupa angka.',
            'total_harga.gt'            => 'Harga kavling belum ditentukan.',
            'id_marketing.required'     => 'Marketing wajib dipilih.',
            'booking_fee.required'      => 'Booking fee wajib diisi.',
            'booking_fee.gt'            => 'Booking fee harus lebih dari 0.',
            'jenis_perumahan.required'  => 'Jenis Perumahan wajib dipilih.',
            'jenis_pembelian.required'  => 'Jenis Pembelian wajib dipilih.',
            'foto_ktp.required'         => 'Foto KTP wajib diunggah.',
            'foto_ktp.mimes'            => 'Foto KTP harus berformat JPG atau PNG.',
            'foto_ktp.max'              => 'Foto KTP maksimal 2 MB.',
            'foto_npwp.mimes'           => 'Foto NPWP harus berformat JPG atau PNG.',
            'foto_npwp.max'             => 'Foto NPWP maksimal 2 MB.',
            'foto_kk.mimes'             => 'Foto KK harus berformat JPG atau PNG.',
            'foto_kk.max'               => 'Foto KK maksimal 2 MB.',
            'foto_bpjs.mimes'           => 'Foto BPJS harus berformat JPG atau PNG.',
            'foto_bpjs.max'             => 'Foto BPJS maksimal 2 MB.',
            'foto_ktp_p.mimes'          => 'Foto KTP pasangan harus berformat JPG atau PNG.',
            'foto_ktp_p.max'            => 'Foto KTP pasangan maksimal 2 MB.',
            'file_sppr.mimes'           => 'File SPPR harus berformat JPG atau PNG.',
            'file_sppr.max'             => 'File SPPR maksimal 2 MB.',
            'foto_pemohon.mimes'        => 'Foto pemohon harus berformat JPG atau PNG.',
            'foto_pemohon.max'          => 'Foto pemohon maksimal 2 MB.',
        ]);

        $kavling = KavlingPeta::find($request->id_kavling);
        if ((int) $kavling->id_lokasi !== (int) $request->id_lokasi) {
            return response()->json([
                'message' => 'Kavling tidak sesuai dengan lokasi yang dipilih.',
                'errors' => [
                    'id_kavling' => ['Kavling tidak sesuai dengan lokasi yang dipilih.']
                ]
            ], 422);
        }

        if (collect($kavling->rincian_biaya ?? [])->sum('nilai') <= 0) {
            return response()->json([
                'message' => 'Harga kavling belum ditentukan.',
                'errors' => [
                    'id_kavling' => ['Harga kavling belum ditentukan.']
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $kavling = KavlingPeta::lockForUpdate()->findOrFail($request->id_kavling);
            if (! KavlingPeta::whereKey($kavling->id)->available()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_kavling' => 'Kavling sudah digunakan booking atau customer lain.',
                ]);
            }

            $folder = public_path('assets/booking');

            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $files = [
                'foto_ktp',
                'foto_npwp',
                'foto_kk',
                'foto_bpjs',
                'file_bukti',
                'file_sppr',
                'foto_pemohon',
                'foto_ktp_p',
            ];
            $fileNames = [];
            foreach ($files as $file) {
                $fileNames[$file] = $request->hasFile($file) ? $this->simpanFile($request->file($file), $folder) : null;
            }

            $no_registrasi = $this->generateNoRegistrasi();

            PengajuanHold::create([
                'no_registrasi'     => $no_registrasi,
                'tgl_booking'       => $request->tanggal ?? Carbon::now()->format('Y-m-d'),
                'nama_lengkap'      => $request->nama_lengkap,
                'nik'               => $request->nik ?? '',
                'no_telp'           => $request->no_telp ?? '',
                'email'             => $request->email ?? '',
                'alamat_ktp'        => $request->alamat_ktp ?? '',
                'alamat_domisili'   => $request->alamat_domisili ?? '',
                'jenis_kelamin'     => $request->jenis_kelamin ?? '',
                'tempat_lahir'      => $request->tempat_lahir ?? '',
                'tgl_lahir'         => $request->tgl_lahir ?? null,
                'npwp'              => $request->npwp ?? '',
                'pekerjaan'         => $request->pekerjaan === 'Lain-lain' ? ($request->pekerjaan_lain ?? '') : ($request->pekerjaan ?? ''),
                'status_pernikahan' => $request->status_pernikahan ?? '',
                'nama_p'            => $request->nama_p ?? '',
                'nik_p'             => $request->nik_p ?? '',
                'nama_saudara'      => $request->nama_saudara ?? '',
                'no_telp_saudara'   => $request->no_telp_saudara ?? '',
                'no_bpjs_kes'       => $request->no_bpjs_kes ?? '',
                'id_lokasi'         => $request->id_lokasi,
                'id_kavling'        => $request->id_kavling,
                'total_harga'       => $request->total_harga,
                'booking_fee'       => $request->booking_fee ?? 0,
                'id_marketing'      => $request->id_marketing ?? 0,
                'jenis_perumahan'   => $request->jenis_perumahan ?? '',
                'jenis_pembelian'   => $request->jenis_pembelian ?? '',
                'foto_ktp'          => $fileNames['foto_ktp'] ?? null,
                'foto_npwp'         => $fileNames['foto_npwp'] ?? null,
                'foto_kk'           => $fileNames['foto_kk'] ?? null,
                'foto_bpjs'         => $fileNames['foto_bpjs'] ?? null,
                'foto_ktp_p'        => $fileNames['foto_ktp_p'] ?? null,
                'file_bukti'        => $fileNames['file_bukti'] ?? null,
                'file_sppr'         => $fileNames['file_sppr'] ?? null,
                'foto_pemohon'      => $fileNames['foto_pemohon'] ?? null,
                'stt_reg'           => 1,
            ]);


            DB::commit();

            session([
                'nama'   => $request->nama_lengkap,
                'lokasi' => LokasiKavling::find($request->id_lokasi)->nama_kavling,
                'blok'   => KavlingPeta::find($request->id_kavling)->kode_kavling,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking berhasil.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            Log::error('Gagal menyimpan booking.', [
                'exception' => $e,
                'nik' => $request->nik,
                'id_lokasi' => $request->id_lokasi,
                'id_kavling' => $request->id_kavling,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => app()->hasDebugModeEnabled()
                    ? 'Booking gagal disimpan: ' . $e->getMessage()
                    : 'Booking gagal disimpan karena terjadi kesalahan pada server. Silakan coba lagi atau hubungi admin.',
            ], 500);
        }
    }

    private function simpanFile($file, $folder)
    {
        if (! $file) {
            return null;
        }

        $filename   = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename   = preg_replace('/[^a-zA-Z0-9-_]/', '_', $filename);
        $filename   = $filename . '_' . time() . '.webp';
        $outputPath = $folder . '/' . $filename;

        $ext    = strtolower($file->getClientOriginalExtension());
        $source = null;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($file->getPathname());
                break;
            case 'png':
                $source = imagecreatefrompng($file->getPathname());
                break;
            default:
                return null;
        }

        if ($source) {
            imagewebp($source, $outputPath, 80);
            imagedestroy($source);

            return $filename;
        }

        return null;
    }

    private function generateNoRegistrasi()
    {
        $latest = PengajuanHold::orderBy('no_registrasi', 'desc')->first();

        if (! $latest || ! preg_match('/^\d{3}$/', $latest->no_registrasi)) {
            return '001';
        }

        $lastNumber = intval($latest->no_registrasi);

        return str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    private function createCustomer($id, $request)
    {
        $data = PengajuanHold::findOrFail($id);

        $lokasi = LokasiKavling::findOrFail($data->id_lokasi);
        $prefix = $lokasi->nama_singkat;

        $latest = Customer::where('kode_customer', 'LIKE', $prefix . '-%')
            ->latest('kode_customer')
            ->first();

        if ($latest && preg_match('/' . $prefix . '-(\d+)/', $latest->kode_customer, $match)) {
            $number  = (int) $match[1] + 1;
            $newKode = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        } else {
            $newKode = $prefix . '-0001';
        }

        $files = [
            'file_bukti'   => 'Bukti Transfer Booking',
            'foto_ktp'     => 'Foto KTP',
            'foto_kk'      => 'Foto KK',
            'foto_npwp'    => 'Foto NPWP',
            'foto_bpjs'    => 'Foto BPJS',
            'foto_ktp_p'   => 'Foto KTP Pasangan',
            'foto_pemohon' => 'Foto Pemohon',
        ];

        // Folder ini dapat belum ada pada instalasi baru di hosting. Pastikan
        // tersedia sebelum lampiran booking dipindahkan ke data customer.
        foreach ([
            public_path('assets/customer'),
            public_path('assets/keuangan/pemasukan'),
        ] as $directory) {
            File::ensureDirectoryExists($directory, 0755, true);
        }

        $customerFiles = [];
        foreach ($files as $field => $label) {
            $oldPath = public_path('assets/booking/' . $data->$field);

            if ($data->$field && File::exists($oldPath)) {
                $customerPath = public_path('assets/customer/' . $data->$field);

                if (! File::copy($oldPath, $customerPath)) {
                    throw new \RuntimeException('Gagal menyalin lampiran customer.');
                }

                if ($field === 'file_bukti') {
                    $keuanganPath = public_path('assets/keuangan/pemasukan/' . $data->$field);
                    if (! File::copy($oldPath, $keuanganPath)) {
                        throw new \RuntimeException('Gagal menyalin bukti pembayaran.');
                    }
                }

                $customerFiles[$field] = $data->$field;
            } else {
                $customerFiles[$field] = null;
            }
        }

        $cust = [
            'kode_customer'     => $newKode,
            'tanggal_verif'     => Carbon::now('Asia/Jakarta'),
            'id_lokasi'         => $data->id_lokasi,
            'id_kavling'        => $data->id_kavling,
            'hrg_jual'          => collect($data->rincian_biaya ?? [])->firstWhere('nama', 'Harga Rumah')['nilai'] ?? 0,
            'biaya_surat'       => collect($data->rincian_biaya ?? [])->firstWhere('nama', 'Biaya Surat')['nilai'] ?? 0,
            'peningkatan_mutu'  => collect($data->rincian_biaya ?? [])->firstWhere('nama', 'Peningkatan Mutu')['nilai'] ?? 0,
            'total_harga'       => $data->total_harga,
            'nama_lengkap'      => $data->nama_lengkap,
            'nik'               => $data->nik,
            'jenis_kelamin'     => $data->jenis_kelamin,
            'tempat_lahir'      => $data->tempat_lahir,
            'tgl_lahir'         => $data->tgl_lahir,
            'alamat_ktp'        => $data->alamat_ktp,
            'alamat_domisili'   => $data->alamat_domisili,
            'status_pernikahan' => $data->status_pernikahan,
            'nama_p'            => $data->nama_p,
            'nik_p'             => $data->nik_p,
            'nama_saudara'      => $data->nama_saudara,
            'no_telp_saudara'   => $data->no_telp_saudara,
            'jenis_perumahan'   => $data->jenis_perumahan,
            'jenis_pembelian'   => $data->jenis_pembelian,
            'id_marketing'      => $data->id_marketing,
            'no_telp'           => $data->no_telp,
            'email'             => $data->email,
            'npwp'              => $data->npwp,
            'no_bpjs_kes'       => $data->no_bpjs_kes,
            'pekerjaan'         => $data->pekerjaan,
            'id_bank'           => $request->id_bank,
            'id_status_progres' => 2,
            'an_surat_cash'     => $request->an_surat_cash,
            'termin_x_cash_b'   => $request->termin_x_cash_b ?? 0,
        ];

        $customer = Customer::create($cust);

        KavlingPeta::find($data->id_kavling)->update(['id_customer' => $customer->id]);

        PersyaratanLegal::create([
            'id_customer' => $customer->id,
        ]);

        foreach ($files as $field => $label) {
            if ($customerFiles[$field]) {
                UploudFile::create([
                    'tanggal'     => Carbon::now(),
                    'id_customer' => $customer->id,
                    'nama_file'   => $label,
                    'keterangan'  => '',
                    'lampiran'    => $customerFiles[$field],
                ]);
            }
        }

        return $customer;
    }

    public function verifikasi($id, Request $request)
    {
        Carbon::setLocale('id');

        $data            = PengajuanHold::findOrFail($id);
        $bankList        = Bank::all();
        $metodeBayarList = MetodeBayar::all();

        if (! empty($data->tgl_booking)) {
            $data->tgl_booking_formatted = Carbon::createFromFormat('Y-m-d', $data->tgl_booking)
                ->locale('id')
                ->translatedFormat('j F Y');
        } else {
            $data->tgl_booking_formatted = null;
        }

        if (! empty($data->tgl_lahir)) {
            $data->tgl_lahir_formatted = Carbon::createFromFormat('Y-m-d', $data->tgl_lahir)
                ->locale('id')
                ->translatedFormat('j F Y');
        } else {
            $data->tgl_lahir_formatted = null;
        }

        abort_if((int) $data->stt_reg === 2, 422, 'Booking ini sudah disetujui.');
        $marketingList = MarketingOffline::orderBy('nama_marketing')->get();
        $lokasiList = LokasiKavling::orderBy('nama_kavling')->get();
        $kavlingList = KavlingPeta::available($data->id)->orderBy('kode_kavling', 'asc')->get();

        return view('admin.pengajuan_hold.verif', compact('data', 'bankList', 'metodeBayarList', 'marketingList', 'lokasiList', 'kavlingList'));
    }

    public function simpanVerifikasi(Request $request, $id)
    {
        $data = PengajuanHold::with(['kavling', 'lokasi'])->findOrFail($id);

        $request->merge([
            'termin_x_cash_b' => $request->termin_x_cash_b ? str_replace('.', '', $request->termin_x_cash_b) : 0,
            'booking_fee' => str_replace('.', '', (string) $request->booking_fee),
        ]);

        $rules = [
            'stt_reg'         => 'required|in:1,2,3',
            'jenis_pembelian' => 'nullable|required_if:stt_reg,2|in:Pembelian Cash,Cash Bertahap,KPR',
            'id_metode_bayar' => 'nullable|required_if:stt_reg,2|exists:metode_bayar,id',
            'id_bank'         => 'nullable|required_if:stt_reg,2|exists:bank,id',
            'an_surat_cash'   => 'nullable|string|max:255',
            'termin_x_cash_b' => 'integer|min:0',
            'tgl_booking' => 'required|date',
            'nama_lengkap' => 'required|string|max:255',
            'nik' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'required|string|max:255',
            'tgl_lahir' => 'required|date',
            'alamat_ktp' => 'required|string',
            'alamat_domisili' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'no_telp' => 'required|string|max:255',
            'id_lokasi' => 'required|exists:lokasi_kavling,id',
            'id_kavling' => 'required|exists:kavling_peta,id',
            'id_marketing' => 'required|integer|min:0',
            'jenis_perumahan' => 'required|in:Subsidi,Komersil',
            'booking_fee' => 'required|numeric|gt:0',
        ];
        foreach (['npwp', 'no_bpjs_kes', 'nama_saudara', 'no_telp_saudara', 'pekerjaan', 'status_pernikahan', 'nama_p', 'nik_p'] as $field) {
            $rules[$field] = 'nullable|string|max:255';
        }
        $attachmentFields = ['foto_ktp', 'foto_npwp', 'foto_kk', 'foto_bpjs', 'foto_ktp_p', 'file_bukti', 'foto_pemohon'];
        foreach ($attachmentFields as $field) {
            $rules[$field] = $field === 'file_bukti'
                ? 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240'
                : 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240';
        }
        if ((int) $request->id_marketing !== 0) {
            $rules['id_marketing'] .= '|exists:marketing_offline,id';
        }
        if ((int) $request->stt_reg === 2 && $request->jenis_pembelian === 'Pembelian Cash') {
            $rules['an_surat_cash'] = 'required|string|max:255';
        }
        if ((int) $request->stt_reg === 2 && $request->jenis_pembelian === 'Cash Bertahap') {
            $rules['termin_x_cash_b'] = 'required|integer|min:1';
        }

        $messages = [
            'stt_reg.required'            => 'Status Verifikasi wajib dipilih!',
            'stt_reg.in'                  => 'Status verifikasi harus Pending, Disetujui atau Ditolak.',
            'jenis_pembelian.required_if' => 'Jenis Pembelian wajib dipilih saat booking disetujui!',
            'jenis_pembelian.in'          => 'Jenis Pembelian tidak valid.',
            'id_metode_bayar.required_if' => 'Metode Pembayaran wajib dipilih saat booking disetujui!',
            'id_metode_bayar.exists'      => 'Metode Pembayaran yang dipilih tidak ditemukan.',
            'id_bank.required_if'         => 'Rekening Pembayaran wajib dipilih saat booking disetujui!',
            'id_bank.exists'              => 'Rekening Pembayaran yang dipilih tidak ditemukan.',
            'an_surat_cash.required_if'   => 'Atas Nama Surat wajib diisi!',
            'termin_x_cash_b.required_if' => 'Termin wajib diisi!',
            'termin_x_cash_b.integer'     => 'Termin harus berupa angka.',
            'termin_x_cash_b.min'         => 'Termin minimal 1 bulan.',
        ];

        $validated = $request->validate($rules, $messages);

        $newFiles = [];
        $obsoleteFiles = [];
        DB::beginTransaction();
        try {
            $data = PengajuanHold::lockForUpdate()->findOrFail($id);
            if ((int) $data->stt_reg === 2) {
                throw \Illuminate\Validation\ValidationException::withMessages(['stt_reg' => 'Booking ini sudah disetujui.']);
            }
            $kavling = KavlingPeta::lockForUpdate()->findOrFail($validated['id_kavling']);
            if ((int) $kavling->id_lokasi !== (int) $validated['id_lokasi'] ||
                ! KavlingPeta::whereKey($kavling->id)->available($data->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['id_kavling' => 'Kavling tidak tersedia atau tidak sesuai lokasi.']);
            }
            $bookingValues = collect($validated)->except(array_merge($attachmentFields, ['stt_reg']))->all();
            $bookingValues['total_harga'] = collect($kavling->rincian_biaya ?? [])->sum('nilai');
            foreach ($attachmentFields as $field) {
                if ($request->hasFile($field)) {
                    File::ensureDirectoryExists(public_path('assets/booking'));
                    $file = $request->file($field);
                    $filename = (string) \Illuminate\Support\Str::uuid() . '.' . $file->extension();
                    $newFiles[] = $filename;
                    $file->move(public_path('assets/booking'), $filename);
                    if ($data->$field) {
                        $obsoleteFiles[] = public_path('assets/booking/' . $data->$field);
                    }
                    $bookingValues[$field] = $filename;
                }
            }
            $data->update($bookingValues);
            $data->load(['kavling', 'lokasi']);
            if ($request->stt_reg == 2) {
                if (! $data->kavling || ! $data->lokasi) {
                    throw new \RuntimeException('Data kavling atau lokasi booking tidak ditemukan.');
                }

                if ($request->jenis_pembelian === 'Pembelian Cash') {
                    $db = [
                        'stt_reg'         => $request->stt_reg,
                        'jenis_pembelian' => $request->jenis_pembelian,
                        'an_surat_cash'   => $request->an_surat_cash,
                    ];
                } elseif ($request->jenis_pembelian === 'Cash Bertahap') {
                    $db = [
                        'stt_reg'         => $request->stt_reg,
                        'jenis_pembelian' => $request->jenis_pembelian,
                        'termin_x_cash_b' => $request->termin_x_cash_b,
                    ];
                } elseif ($request->jenis_pembelian === 'KPR') {
                    $db = [
                        'stt_reg'         => $request->stt_reg,
                        'jenis_pembelian' => $request->jenis_pembelian,
                    ];
                }

                $data->update($db);

                $customer = $this->createCustomer($data->id, $request);
                $tglNow   = Carbon::now('Asia/Jakarta')->toDateString();

                $rincian = $data->rincian_biaya ?? [];
                $bookingFee = (int) ($data->booking_fee ?? 0);
                $sisaBayar  = $data->total_harga - $bookingFee;

                foreach ($rincian as $item) {
                    $nama  = $item['nama'] ?? '';
                    $nilai = (int) ($item['nilai'] ?? 0);
                    if ($nilai <= 0) continue;

                    $terbayar = 0;
                    $sisa     = $nilai;

                    Piutang::create([
                        'id_customer'     => $customer->id,
                        'id_bank'         => $request->id_bank,
                        'tanggal_piutang' => $tglNow,
                        'deskripsi'       => $nama . ' tipe ' . $data->kavling->tipe_bangunan . ' ' . $data->lokasi->nama_kavling . ' Blok ' . $data->kavling->kode_kavling,
                        'nominal'         => $nilai,
                        'lampiran'        => '',
                        'status'          => 1,
                        'terbayar'        => $terbayar,
                        'sisa_bayar'      => $sisa,
                        'tgl_pelunasan'   => null,
                    ]);
                }

                $no_kwitansi = $this->generator->generateNomorDokumen(
                    $data->lokasi,
                    'no_kwitansi',
                    Pemasukan::class
                );

                $p1 = [
                    'id_bank'               => $request->id_bank,
                    'id_metode_bayar'       => $request->id_metode_bayar,
                    'id_customer'           => $customer->id,
                    'tanggal'               => $data->tgl_booking,
                    'no_kwitansi'           => $no_kwitansi,
                    'nominal'               => $data->booking_fee,
                    'lampiran'              => $data->file_bukti ?? '',
                    'id_kategori_transaksi' => 1,
                    'keterangan'            => 'Booking Fee Rumah tipe ' . $data->kavling->tipe_bangunan . ' ' . $data->lokasi->nama_kavling . ' Blok ' . $data->kavling->kode_kavling,
                ];

                Pemasukan::create($p1);
            } else {
                $db = [
                    'stt_reg' => $request->stt_reg,
                ];

                $data->update($db);
            }


            $this->logCreate('Verifikasi Data Booking', $data->id);
            DB::commit();

            if ((int) $request->stt_reg === 2) {
                foreach ($attachmentFields as $field) {
                    if ($data->$field) {
                        $obsoleteFiles[] = public_path('assets/booking/' . $data->$field);
                    }
                }
            }
            try {
                File::delete($obsoleteFiles);
            } catch (\Throwable $cleanupError) {
                Log::warning('Data booking tersimpan, tetapi lampiran lama belum dapat dibersihkan.', ['pengajuan_hold_id' => $data->id]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Verifikasi booking berhasil disimpan.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($newFiles as $filename) {
                File::delete([
                    public_path('assets/booking/' . $filename),
                    public_path('assets/customer/' . $filename),
                    public_path('assets/keuangan/pemasukan/' . $filename),
                ]);
            }
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            Log::error('Gagal menyimpan verifikasi booking.', [
                'pengajuan_hold_id' => $data->id,
                'request'           => $request->except(['_token']),
                'exception'         => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Verifikasi booking gagal disimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
        $data = PengajuanHold::with(['kavling', 'lokasi'])->findOrFail($id);

            $files = [
                $data->foto_ktp,
                $data->foto_npwp,
                $data->foto_kk,
                $data->foto_bpjs,
                $data->foto_pemohon,
                $data->foto_ktp_p,
                $data->file_bukti,
            ];

            foreach ($files as $file) {
                if (! empty($file) && file_exists(public_path('assets/booking/' . $file))) {
                    unlink(public_path('assets/booking/' . $file));
                }
            }


            $this->logDelete('Pengajuan Hold', $id);
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
