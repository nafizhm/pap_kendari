<?php
namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Bank;
use App\Models\Hutang;
use App\Models\KategoriTransaksi;
use App\Models\MetodeBayar;
use App\Models\Pengeluaran;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use Yajra\DataTables\Facades\DataTables;

class PengeluaranController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        Carbon::setLocale('id');

        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = Pengeluaran::orderByDesc('tanggal');

            if ($request->filled('filter_tanggal')) {
                $data->whereDate('tanggal', $request->filter_tanggal);
            }
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('j F Y');
                })
                ->editColumn('nominal', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->nominal, 0, ',', '.') . '</span>
                    </div>';
                })
                ->addColumn('rekening', function ($row) {
                    $bank = Bank::find($row->id_bank);
                    return $bank ? $bank->nama : 'Bank Tidak Diketahui';
                })
                ->addColumn('id_kategori_transaksi', function ($row) {
                    $kategori = KategoriTransaksi::find($row->id_kategori_transaksi);
                    $nama     = $kategori ? $kategori->kategori : 'Tidak Diketahui';
                    if ($row->keterangan) {
                        return '<div><strong>' . $nama . '</strong><br><small>' . e($row->keterangan) . '</small></div>';
                    }
                    return $nama;
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');

                        $kategoriIds = KategoriTransaksi::where('kategori', 'like', "%$search%")->pluck('id');
                        $bankIds     = Bank::where('nama', 'like', "%$search%")->pluck('id');

                        $query->where(function ($q) use ($kategoriIds, $bankIds, $search) {
                            $q->whereIn('id_kategori_transaksi', $kategoriIds)
                                ->orWhereIn('id_bank', $bankIds)
                                ->orWhere('keterangan', 'like', "%$search%");
                        });
                    }
                })
                ->filterColumn('tanggal', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->WhereDate('tanggal', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl   = route('pengeluaran.edit', $row->id);
                    $detailUrl = route('pengeluaran.show', $row->id);
                    $deleteUrl = route('pengeluaran.destroy', $row->id);
                    $cetakUrl  = route('pengeluaran.cetak', $row->id);

                    $isDetailOnly =
                    $row->id_po != 0 ||
                    $row->id_mutasi != 0;

                    $btn = '<div class="d-flex justify-content-center">';
                    $btn .= '<a href="' . e($cetakUrl) . '" target="_blank" class="btn btn-success btn-sm mx-1" title="Cetak Tanda Terima">
                        <i class="fas fa-print"></i> Cetak
                    </a>';

                    if ($isDetailOnly) {
                        $btn .= '<button class="btn btn-primary btn-sm mx-1 detail-button"
            data-id="' . e($row->id) . '"
            data-url="' . e($detailUrl) . '">
            Detail
        </button>';
                    } else {
                        if ($permissions['edit']) {
                            $btn .= '<button class="btn btn-primary btn-sm mx-1 edit-button"
                data-id="' . e($row->id) . '"
                data-url="' . e($editUrl) . '">
                Edit
            </button>';
                        }
                    }

                    if ($permissions['hapus']) {
                        if ($isDetailOnly) {
                            $btn .= '<button class="btn btn-danger btn-sm mx-1" disabled>
                Hapus
            </button>';
                        } else {
                            $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                ' . csrf_field() . method_field('DELETE') . '
                <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">
                    Hapus
                </button>
            </form>';
                        }
                    }

                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['action', 'tanggal', 'nominal', 'id_kategori_transaksi'])
                ->make(true);
        }

        $kategoriTransaksi = KategoriTransaksi::where('jenis_kategori', 'PENGELUARAN')
            ->whereNotIn('id', [8, 9, 11, 13, 14, 15, 16, 18])
            ->get();

        $kategoriTransaksiDetail = KategoriTransaksi::where('jenis_kategori', 'PENGELUARAN')
            ->get();

        $HutangList = Hutang::where('status', 1)->get();

        $bankList = Bank::all();
        $metodeBayarList = MetodeBayar::whereIn('id', [1, 2])->get();

        return view('admin.keuangan.pengeluaran.index', compact('permissions', 'kategoriTransaksi', 'kategoriTransaksiDetail', 'HutangList', 'bankList', 'metodeBayarList'));
    }

    public function edit($id)
    {
        $list = Pengeluaran::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function show($id)
    {
        $list = Pengeluaran::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'               => 'required|date',
            'no_tanda_terima'       => 'required|max:100|unique:pengeluaran,no_tanda_terima',
            'diterima_dari'         => 'required|max:255',
            'nama_penerima'         => 'required|max:255',
            'nominal'               => 'required',
            'id_bank'               => 'required',
            'id_metode_bayar'       => 'required',
            'lampiran'              => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'id_kategori_transaksi' => 'required',
            'id_hutang'             => 'required_if:id_kategori_transaksi,3',
        ], [
            'tanggal.required'               => 'Tanggal wajib diisi.',
            'tanggal.date'                   => 'Tanggal harus berupa tanggal.',
            'no_tanda_terima.required'       => 'Nomor tanda terima wajib diisi.',
            'no_tanda_terima.unique'         => 'Nomor tanda terima sudah digunakan.',
            'diterima_dari.required'         => 'Nama pemberi/penyetor wajib diisi.',
            'nama_penerima.required'         => 'Nama penerima wajib diisi.',
            'nominal.required'               => 'Nominal wajib diisi.',
            'id_bank.required'               => 'Rekening wajib diisi.',
            'id_metode_bayar.required'       => 'Metode pembayaran wajib dipilih.',
            'lampiran.required'              => 'Lampiran wajib diisi.',
            'lampiran.file'                  => 'Lampiran harus berupa file.',
            'lampiran.mimes'                 => 'Format lampiran harus jpg, jpeg, png, atau pdf.',
            'lampiran.max'                   => 'Ukuran file maksimal 2MB.',
            'id_kategori_transaksi.required' => 'Kategori transaksi wajib dipilih.',
            'id_hutang.required_if'          => 'Hutang wajib dipilih',
        ]);

        if (! empty($request->id_hutang)) {
            $hutang = Hutang::find($request->id_hutang);

            if ($hutang) {
                $nominalInput = (int) str_replace('.', '', $request->nominal);

                if ($nominalInput > $hutang->sisa_bayar) {
                    return response()->json([
                        'errors' => ['nominal' => ['Nominal tidak boleh lebih besar dari sisa hutang.']],
                    ], 422);
                }
            }
        }

        $db = [
            'id_hutang'             => $request->id_hutang ?? 0,
            'id_piutang'            => 0,
            'id_po'                 => 0,
            'id_mutasi'             => 0,
            'id_bank'               => $request->id_bank,
            'tanggal'               => $request->tanggal,
            'no_tanda_terima'       => $request->no_tanda_terima,
            'diterima_dari'         => $request->diterima_dari,
            'nama_penerima'         => $request->nama_penerima,
            'nominal'               => str_replace('.', '', $request->nominal),
            'id_kategori_transaksi' => $request->id_kategori_transaksi,
            'id_metode_bayar'       => $request->id_metode_bayar,
            'keterangan'            => $request->keterangan ?? '',
        ];

        if ($request->hasFile('lampiran')) {

            $file = $request->file('lampiran');
            $ext  = $file->getClientOriginalExtension();

            $filename = Str::random(25) . '.' . $ext;
            $file->move(public_path('assets/keuangan/pengeluaran/'), $filename);

            $db['lampiran'] = $filename;
        }

        $pl = Pengeluaran::create($db);
        $this->logCreate('Pengeluaran', $pl->id);

        if (! empty($request->id_hutang)) {
            $hutang = Hutang::find($request->id_hutang);

            if ($hutang) {
                $nominalBayar  = $hutang->terbayar + str_replace('.', '', $request->nominal);
                $sisaBayarBaru = $hutang->sisa_bayar - str_replace('.', '', $request->nominal);

                $updateData = [
                    'terbayar'   => $nominalBayar,
                    'sisa_bayar' => $sisaBayarBaru,
                ];

                if ($sisaBayarBaru == 0) {
                    $updateData['status']        = 2;
                    $updateData['tgl_pelunasan'] = Carbon::now();
                }

                $hutang->update($updateData);
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function update(Request $request, $id)
    {
        $data = Pengeluaran::findOrFail($id);

        $rules = [
            'tanggal' => 'required|date',
            'no_tanda_terima' => 'required|max:100|unique:pengeluaran,no_tanda_terima,' . $id,
            'diterima_dari' => 'required|max:255',
            'nama_penerima' => 'required|max:255',
            'nominal' => 'required',
            'id_bank' => 'required',
            'id_metode_bayar' => 'required',
        ];

        if ($request->id_kategori_transaksi == 5) {
            $rules['id_kategori_transaksi'] = 'required';
        }

        if (empty($data->lampiran)) {
            $rules['lampiran'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
        } elseif ($request->hasFile('lampiran')) {
            $rules['lampiran'] = 'file|mimes:jpg,jpeg,png,pdf|max:2048';
        }

        $request->validate($rules, [
            'tanggal.required'               => 'Tanggal wajib diisi.',
            'tanggal.date'                   => 'Tanggal harus berupa tanggal.',
            'no_tanda_terima.required'       => 'Nomor tanda terima wajib diisi.',
            'no_tanda_terima.unique'         => 'Nomor tanda terima sudah digunakan.',
            'diterima_dari.required'         => 'Nama pemberi/penyetor wajib diisi.',
            'nama_penerima.required'         => 'Nama penerima wajib diisi.',
            'nominal.required'               => 'Nominal wajib diisi.',
            'id_bank.required'               => 'Rekening wajib diisi.',
            'id_metode_bayar.required'       => 'Metode pembayaran wajib dipilih.',
            'lampiran.required'              => 'Lampiran wajib diisi.',
            'lampiran.file'                  => 'Lampiran harus berupa file.',
            'lampiran.mimes'                 => 'Format lampiran harus jpg, jpeg, png, atau pdf.',
            'lampiran.max'                   => 'Ukuran file maksimal 2MB.',
            'id_kategori_transaksi.required' => 'Kategori transaksi wajib diisi jika memilih jenis 5.',
        ]);

        $db = [
            'tanggal'    => $request->tanggal,
            'no_tanda_terima' => $request->no_tanda_terima,
            'diterima_dari' => $request->diterima_dari,
            'nama_penerima' => $request->nama_penerima,
            'id_bank'    => $request->id_bank,
            'id_metode_bayar' => $request->id_metode_bayar,
            'keterangan' => $request->keterangan ?? '',
            'nominal'    => str_replace('.', '', $request->nominal),
        ];

        if ($request->hasFile('lampiran')) {
            if (! empty($data->lampiran) && file_exists(public_path('assets/keuangan/pengeluaran/' . $data->lampiran))) {
                unlink(public_path('assets/keuangan/pengeluaran/' . $data->lampiran));
            }

            $file = $request->file('lampiran');
            $ext  = $file->getClientOriginalExtension();

            $filename = Str::random(25) . '.' . $ext;
            $file->move(public_path('assets/keuangan/pengeluaran/'), $filename);

            $db['lampiran'] = $filename;
        }

        if ($data->id_kategori_transaksi == 10) {
            if ($request->id_hutang != $data->id_hutang) {
                $hutanglama = Hutang::find($data->id_hutang);
                if ($hutanglama) {
                    $hutanglama->update([
                        'terbayar'      => $hutanglama->terbayar - $data->nominal,
                        'sisa_bayar'    => $hutanglama->sisa_bayar + $data->nominal,
                        'status'        => 1,
                        'tgl_pelunasan' => null,
                    ]);
                }

                $hutangbaru = Hutang::find($request->id_hutang);
                if ($hutangbaru) {
                    $nominalBayar  = str_replace('.', '', $request->nominal);
                    $sisaBayarBaru = $hutangbaru->sisa_bayar - $nominalBayar;

                    $updateData = [
                        'terbayar'   => $nominalBayar,
                        'sisa_bayar' => $sisaBayarBaru,
                    ];

                    if ($sisaBayarBaru == 0) {
                        $updateData['status']        = 2;
                        $updateData['tgl_pelunasan'] = Carbon::now();
                    }

                    $hutangbaru->update($updateData);
                }
            } else {
                $hutanglama2 = Hutang::find($data->id_hutang);
                if ($hutanglama2) {
                    $hutanglama2->update([
                        'terbayar'      => $hutanglama2->terbayar - $data->nominal,
                        'sisa_bayar'    => $hutanglama2->sisa_bayar + $data->nominal,
                        'status'        => 1,
                        'tgl_pelunasan' => null,
                    ]);

                    $nominalBayar  = $hutanglama2->terbayar + str_replace('.', '', $request->nominal);
                    $sisaBayarBaru = $hutanglama2->sisa_bayar - str_replace('.', '', $request->nominal);

                    $updateData = [
                        'terbayar'   => $nominalBayar,
                        'sisa_bayar' => $sisaBayarBaru,
                    ];

                    if ($sisaBayarBaru == 0) {
                        $updateData['status']        = 2;
                        $updateData['tgl_pelunasan'] = Carbon::now();
                    }

                    $hutanglama2->update($updateData);
                }
            }
        }

        $data->update($db);
        $this->logEdit('Pengeluaran', $data->id);

        return response()->json(['status' => 'success']);
    }

    public function cetak($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $metode = MetodeBayar::find($pengeluaran->id_metode_bayar);
        $templatePath = public_path('templates/tanda-terima-pengeluaran-template.pdf');

        abort_unless(file_exists($templatePath), 500, 'Template tanda terima pengeluaran tidak ditemukan.');

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('Tanda Terima - ' . ($pengeluaran->no_tanda_terima ?: $pengeluaran->id));
        $pdf->SetAuthor('Dealaska');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $pdf->AddPage('P', 'A4');
        $pdf->useTemplate($templateId, 0, 0, 210, 297);

        $nomor = $pengeluaran->no_tanda_terima ?: 'TT-' . str_pad((string) $pengeluaran->id, 6, '0', STR_PAD_LEFT);
        $tanggal = Carbon::parse($pengeluaran->tanggal)->translatedFormat('d F Y');
        $metodeNama = strtolower($metode->jenis_bayar ?? 'tunai');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('times', 'B', 10);
        $pdf->SetXY(164, 22.2);
        $pdf->Cell(29, 5, $nomor, 0, 0, 'L');
        $pdf->SetXY(164, 27.2);
        $pdf->Cell(29, 5, $tanggal, 0, 0, 'L');

        $pdf->SetFont('times', '', 10);
        $pdf->SetXY(56, 40.8);
        $pdf->Cell(137, 5, $pengeluaran->diterima_dari ?: '-', 0, 0, 'L');
        $pdf->SetFont('times', 'I', 9.5);
        $pdf->SetXY(56, 47);
        $pdf->Cell(137, 5, '# ' . $this->terbilang((int) $pengeluaran->nominal) . ' rupiah #', 0, 0, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->SetXY(56, 53.2);
        $pdf->MultiCell(137, 5, $pengeluaran->keterangan ?: '-', 0, 'L', false, 1, '', '', true, 0, false, true, 10, 'T');

        $pdf->SetFont('times', 'B', 12);
        $pdf->SetXY(25, 62.8);
        $pdf->Cell(68, 6, number_format((int) $pengeluaran->nominal, 0, ',', '.') . ',-', 0, 0, 'L');

        $metodeX = str_contains($metodeNama, 'transfer')
            ? 37
            : (str_contains($metodeNama, 'cash') || str_contains($metodeNama, 'tunai') ? 15.3 : null);
        if ($metodeX !== null) {
            $pdf->SetFont('dejavusans', 'B', 13);
            $pdf->SetXY($metodeX, 73.5);
            $pdf->Cell(5, 5, '✓', 0, 0, 'C');
        }

        $pdf->SetFont('times', 'B', 9);
        $pdf->SetXY(133, 87.5);
        $pdf->Cell(30, 5, $pengeluaran->diterima_dari ?: '-', 0, 0, 'C');
        $pdf->SetXY(163, 87.5);
        $pdf->Cell(30, 5, $pengeluaran->nama_penerima ?: '-', 0, 0, 'C');

        $filename = 'Tanda-Terima-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $nomor) . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    private function terbilang(int $angka): string
    {
        $angka = abs($angka);
        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($angka < 12) {
            return trim($baca[$angka]);
        }
        if ($angka < 20) {
            return $this->terbilang($angka - 10) . ' Belas';
        }
        if ($angka < 100) {
            return trim($this->terbilang(intdiv($angka, 10)) . ' Puluh ' . $this->terbilang($angka % 10));
        }
        if ($angka < 200) {
            return trim('Seratus ' . $this->terbilang($angka - 100));
        }
        if ($angka < 1000) {
            return trim($this->terbilang(intdiv($angka, 100)) . ' Ratus ' . $this->terbilang($angka % 100));
        }
        if ($angka < 2000) {
            return trim('Seribu ' . $this->terbilang($angka - 1000));
        }
        if ($angka < 1000000) {
            return trim($this->terbilang(intdiv($angka, 1000)) . ' Ribu ' . $this->terbilang($angka % 1000));
        }
        if ($angka < 1000000000) {
            return trim($this->terbilang(intdiv($angka, 1000000)) . ' Juta ' . $this->terbilang($angka % 1000000));
        }
        if ($angka < 1000000000000) {
            return trim($this->terbilang(intdiv($angka, 1000000000)) . ' Miliar ' . $this->terbilang($angka % 1000000000));
        }

        return trim($this->terbilang(intdiv($angka, 1000000000000)) . ' Triliun ' . $this->terbilang($angka % 1000000000000));
    }

    public function destroy($id)
    {
        $data = Pengeluaran::findOrFail($id);
        if (! empty($data->lampiran) && file_exists(public_path('assets/keuangan/pengeluaran/' . $data->lampiran))) {
            unlink(public_path('assets/keuangan/pengeluaran/' . $data->lampiran));
        }

        if ($data->id_hutang != 0) {
            $hutanglama = Hutang::find($data->id_hutang);
            if ($hutanglama) {
                $hutanglama->update([
                    'terbayar'      => $hutanglama->terbayar - $data->nominal,
                    'sisa_bayar'    => $hutanglama->sisa_bayar + $data->nominal,
                    'status'        => 1,
                    'tgl_pelunasan' => null,
                ]);
            }
        }

        $this->logDelete('Pengeluaran', $data->id);
        $data->delete();

        return response()->json(['status' => 'success']);
    }
}
