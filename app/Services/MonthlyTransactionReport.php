<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyTransactionReport
{
    public const TYPES = [
        'hold' => 'Hold', 'booking_fee' => 'Booking Fee', 'proses_bank' => 'Proses Bank',
        'sp3k' => 'SP3K', 'akad' => 'Akad',
    ];

    private function source(string $type): array
    {
        return match ($type) {
            'hold' => [DB::table('pengajuan_hold')->where('stt_reg', 1), 'tgl_booking'],
            'booking_fee' => [DB::table('customer'), 'tanggal_verif'],
            'proses_bank' => [DB::table('wawancara'), 'tgl_wawancara'],
            'sp3k' => [DB::table('wawancara_sp3k'), 'tgl_terbit_sp3k'],
            'akad' => [DB::table('detail_akad')->join('akad', 'akad.id', '=', 'detail_akad.id_akad')
                ->where('detail_akad.status', 2), 'akad.tgl_akad'],
        };
    }

    public function counts(int $year, string $type): array
    {
        [$query, $date] = $this->source($type);
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', $date) AS INTEGER)" : "MONTH($date)";
        $totals = $query->where($date, '>=', sprintf('%04d-01-01', $year))
            ->where($date, '<', sprintf('%04d-01-01', $year + 1))
            ->selectRaw("$monthExpression AS bulan, COUNT(*) AS jumlah")
            ->groupByRaw($monthExpression)->pluck('jumlah', 'bulan');
        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $rows[] = ['bulan' => Carbon::create($year, $month, 1)->locale('id')->translatedFormat('F'),
                'jumlah' => (int) ($totals[$month] ?? 0)];
        }
        return $rows;
    }

    public function details(int $year, string $type, ?int $month = null): \Illuminate\Database\Query\Builder
    {
        [$query, $date] = $this->source($type);
        $start = Carbon::create($year, $month ?? 1, 1)->startOfDay();
        $end = $month ? $start->copy()->addMonth() : $start->copy()->addYear();
        if ($type === 'hold') {
            $customer = 'pengajuan_hold';
            $id = 'pengajuan_hold.id';
            $code = 'pengajuan_hold.no_registrasi';
        } else {
            if ($type === 'proses_bank') {
                $query->leftJoin('customer', 'customer.id', '=', 'wawancara.id_customer');
                $id = 'wawancara.id';
            } elseif ($type === 'sp3k') {
                $query->leftJoin('wawancara', 'wawancara.id', '=', 'wawancara_sp3k.id_wawancara')
                    ->leftJoin('customer', 'customer.id', '=', 'wawancara.id_customer');
                $id = 'wawancara_sp3k.id';
            } elseif ($type === 'akad') {
                $query->leftJoin('customer', 'customer.id', '=', 'detail_akad.id_customer');
                $id = 'detail_akad.id';
            } else {
                $id = 'customer.id';
            }
            $customer = 'customer';
            $code = 'customer.kode_customer';
        }
        return $query->leftJoin('lokasi_kavling', 'lokasi_kavling.id', '=', "$customer.id_lokasi")
            ->leftJoin('kavling_peta', 'kavling_peta.id', '=', "$customer.id_kavling")
            ->where($date, '>=', $start->format('Y-m-d'))->where($date, '<', $end->format('Y-m-d'))
            ->select(["$id as id", "$date as tanggal", "$code as kode", "$customer.nama_lengkap as nama",
                "$customer.no_telp as telepon", 'lokasi_kavling.nama_kavling as lokasi', 'kavling_peta.kode_kavling as kavling'])
            ->orderBy($date)->orderBy($id);
    }
}
