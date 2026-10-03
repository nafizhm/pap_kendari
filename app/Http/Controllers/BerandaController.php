<?php

namespace App\Http\Controllers;

use App\Models\Akad;
use App\Models\AkadDetail;
use App\Models\BAST;
use App\Models\BankKPR;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\Customer;
use App\Models\MarketingOffline;
use App\Models\PengajuanHold;
use App\Models\PPJB;
use App\Models\SPPR;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $username = Auth::user()->username;

        $pipelineCounts = [
            'booking' => PengajuanHold::where('stt_reg', '!=', 2)->count(),
            'wawancara' => Wawancara::where('status', 1)
                ->whereHas('customer', fn ($query) => $query->where('stt_arsip', 0))
                ->count(),
            'acc_bank' => WawancaraSp3k::where('status', 1)
                ->whereHas('wawancara.customer', fn ($query) => $query->where('stt_arsip', 0))
                ->count(),
            'akad' => Akad::count(),
        ];

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $salesYear = (int) $request->input('sales_year', Carbon::now('Asia/Jakarta')->year);
        if ($salesYear < 2000 || $salesYear > 2100) {
            $salesYear = Carbon::now('Asia/Jakarta')->year;
        }
        $monthlyBookings = PengajuanHold::whereYear('tgl_booking', $salesYear)
            ->whereIn('stt_reg', [1, 2])
            ->selectRaw('MONTH(tgl_booking) as month, stt_reg, COUNT(*) as total')
            ->groupByRaw('MONTH(tgl_booking), stt_reg')->get();
        $monthlySales = collect(['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'])
            ->map(function ($label, $index) use ($monthlyBookings) {
                $rows = $monthlyBookings->where('month', $index + 1);
                return ['label' => $label, 'pending' => (int) $rows->where('stt_reg', 1)->sum('total'),
                    'approved' => (int) $rows->where('stt_reg', 2)->sum('total')];
            });
        $salesScale = max(1, $monthlySales->max('pending'), $monthlySales->max('approved'));

        $summaryMetrics = [
            'jumlah_project' => LokasiKavling::whereIn('stt_tampil', [1, 3])->count(),
            'total_unit' => KavlingPeta::count(),
            'booking_fee_hari_ini' => PengajuanHold::whereDate('tgl_booking', $today)->sum('booking_fee'),
            'piutang' => 0,
            'piutang_customer' => 0,
            'tagihan_tempo_customer' => 0,
            'tagihan_tempo_total' => 0,
        ];

        $projectStats = LokasiKavling::orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (LokasiKavling $lokasi) {
                $lokasiId = $lokasi->id;

                return [
                    'id' => $lokasiId,
                    'nama' => $lokasi->nama_kavling,
                    'kode' => $lokasi->nama_singkat,
                    'total_unit' => KavlingPeta::where('id_lokasi', $lokasiId)->count(),
                    'booking' => PengajuanHold::where('id_lokasi', $lokasiId)->where('stt_reg', '!=', 2)->count(),
                    'sppr' => SPPR::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'wawancara' => Wawancara::where('status', 1)
                        ->whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))
                        ->count(),
                    'acc_bank' => WawancaraSp3k::where('status', 1)
                        ->whereHas('wawancara.customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))
                        ->count(),
                    'ppjb' => PPJB::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'akad' => AkadDetail::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                    'bast' => BAST::whereHas('customer', fn ($query) => $query->where('id_lokasi', $lokasiId)->where('stt_arsip', 0))->count(),
                ];
            });

        $projectTotals = [
            'booking' => $projectStats->sum('booking'),
            'sppr' => $projectStats->sum('sppr'),
            'wawancara' => $projectStats->sum('wawancara'),
            'acc_bank' => $projectStats->sum('acc_bank'),
            'ppjb' => $projectStats->sum('ppjb'),
            'akad' => $projectStats->sum('akad'),
            'bast' => $projectStats->sum('bast'),
        ];

        $marketingStats = MarketingOffline::orderBy('nama_marketing')
            ->get()
            ->map(function (MarketingOffline $marketing) {
                return [
                    'id' => $marketing->id,
                    'nama' => $marketing->nama_marketing,
                    'kode' => $marketing->kode_marketing,
                    'inisial' => mb_substr($marketing->nama_marketing, 0, 1),
                    'jumlah' => Customer::where('id_marketing', $marketing->id)
                        ->where('stt_arsip', 0)
                        ->count(),
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        $totalBankUsage = WawancaraSp3k::where('status', 1)->count();

        $bankStats = BankKPR::orderBy('nama')
            ->get()
            ->map(function (BankKPR $bank) use ($totalBankUsage) {
                $jumlah = WawancaraSp3k::where('status', 1)
                    ->where('id_bank_kpr', $bank->id)
                    ->count();

                return [
                    'id' => $bank->id,
                    'nama' => $bank->nama,
                    'jumlah' => $jumlah,
                    'persentase' => $totalBankUsage > 0 ? round(($jumlah / $totalBankUsage) * 100) : 0,
                ];
            })
            ->filter(fn ($bank) => $bank['jumlah'] > 0)
            ->sortByDesc('jumlah')
            ->values();

        return view('admin.beranda.index', compact(
            'username',
            'pipelineCounts',
            'summaryMetrics',
            'projectStats',
            'projectTotals',
            'marketingStats',
            'bankStats',
            'salesYear',
            'monthlySales',
            'salesScale'
        ));
    }
}
