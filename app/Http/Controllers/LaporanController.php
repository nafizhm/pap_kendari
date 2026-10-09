<?php

namespace App\Http\Controllers;

use App\Models\HakAkses;
use App\Models\Menu;
use App\Services\MonthlyTransactionReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanController extends Controller
{
    private function filters(Request $request): array
    {
        $menuId = Menu::where('route_name', 'laporan.index')->value('id');
        abort_unless($menuId && HakAkses::where('id_user', auth()->id())->where('id_menu', $menuId)->where('lihat', 1)->exists(), 403);
        $validated = $request->validate([
            'tahun' => 'sometimes|required|integer|min:1900|max:2100',
            'jenis_transaksi' => ['sometimes', 'required', Rule::in(array_keys(MonthlyTransactionReport::TYPES))],
        ]);
        return [(int) ($validated['tahun'] ?? now('Asia/Jakarta')->year), $validated['jenis_transaksi'] ?? 'hold'];
    }

    public function index(Request $request, MonthlyTransactionReport $report)
    {
        [$tahun, $jenisTransaksi] = $this->filters($request);
        $jenisList = MonthlyTransactionReport::TYPES;
        $rows = $report->counts($tahun, $jenisTransaksi);
        $total = array_sum(array_column($rows, 'jumlah'));
        return view('admin.laporan.index', compact('tahun', 'jenisTransaksi', 'jenisList', 'rows', 'total'));
    }

    public function detail(Request $request, MonthlyTransactionReport $report)
    {
        [$tahun, $jenis] = $this->filters($request);
        $validated = $request->validate(['bulan' => 'required|integer|min:1|max:12', 'page' => 'sometimes|integer|min:1']);
        return response()->json($report->details($tahun, $jenis, (int) $validated['bulan'])->paginate(25));
    }

    public function excel(Request $request, MonthlyTransactionReport $report)
    {
        [$tahun, $jenis] = $this->filters($request);
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $summary = $book->getActiveSheet()->setTitle('Rekap Bulanan');
        $summary->fromArray([['Laporan ' . MonthlyTransactionReport::TYPES[$jenis], $tahun], ['Bulan', 'Jumlah Data']], null, 'A1');
        $rows = $report->counts($tahun, $jenis);
        foreach ($rows as $index => $row) $summary->fromArray([$row['bulan'], $row['jumlah']], null, 'A' . ($index + 3));
        $summary->fromArray(['Total', array_sum(array_column($rows, 'jumlah'))], null, 'A15');
        $sheet = $book->createSheet()->setTitle('Detail Transaksi');
        $sheet->fromArray(['No', 'Tanggal', 'Kode', 'Nama Customer', 'Telepon', 'Lokasi', 'Kavling'], null, 'A1');
        $line = 2;
        foreach ($report->details($tahun, $jenis)->cursor() as $row) {
            $sheet->setCellValue('A' . $line, $line - 1);
            $values = [$row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') : '-',
                $row->kode, $row->nama, $row->telepon, $row->lokasi, $row->kavling];
            foreach ($values as $index => $value) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 2);
                $sheet->setCellValueExplicit($column . $line, (string) ($value ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $line++;
        }
        foreach ([$summary, $sheet] as $tab) {
            $tab->freezePane('A2');
            foreach (range('A', $tab->getHighestColumn()) as $column) $tab->getColumnDimension($column)->setAutoSize(true);
            $tab->getStyle('A1:' . $tab->getHighestColumn() . '1')->getFont()->setBold(true);
        }
        return response()->streamDownload(function () use ($book) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, "laporan-{$jenis}-{$tahun}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
