@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
<section class="content-header"></section>
<section class="content"><div class="container-fluid"><div class="card">
<div class="card-header"><h3 class="font-weight-bold text-lg mb-0">Laporan Transaksi Bulanan</h3></div>
<div class="card-body">
<form method="GET" action="{{ route('laporan.index') }}" class="form-inline mb-3">
<label for="tahun" class="mr-2">Tahun</label>
<input type="number" name="tahun" id="tahun" class="form-control form-control-sm mr-3 mb-2" style="width:100px" min="1900" max="2100" value="{{ $tahun }}" required>
<label for="jenis_transaksi" class="mr-2">Jenis Transaksi</label>
<select name="jenis_transaksi" id="jenis_transaksi" class="form-control form-control-sm mr-2 mb-2">
@foreach ($jenisList as $value => $label)
<option value="{{ $value }}" {{ $jenisTransaksi === $value ? 'selected' : '' }}>{{ $label }}</option>
@endforeach
</select>
<button type="submit" class="btn btn-primary btn-sm mr-2 mb-2">Tampilkan</button>
<a href="{{ route('laporan.excel', ['tahun' => $tahun, 'jenis_transaksi' => $jenisTransaksi]) }}" class="btn btn-success btn-sm mb-2"><i class="fas fa-file-excel mr-1"></i> Excel</a>
</form>
<h5 class="mb-3">{{ $jenisList[$jenisTransaksi] }} - {{ $tahun }}</h5>
<div class="row"><div class="col-md-4"><div class="table-responsive">
<table class="table table-sm table-bordered table-striped">
<thead><tr><th>Bulan</th><th class="text-right">Jumlah</th><th class="text-center">Detail</th></tr></thead>
<tbody>
@foreach ($rows as $index => $row)
<tr><td>{{ $row['bulan'] }}</td><td class="text-right">{{ number_format($row['jumlah'], 0, ',', '.') }}</td><td class="text-center"><button type="button" class="btn btn-outline-primary btn-sm report-detail" data-month="{{ $index + 1 }}" data-label="{{ $row['bulan'] }}" aria-label="Detail {{ $row['bulan'] }}"><i class="fas fa-eye"></i></button></td></tr>
@endforeach
</tbody><tfoot><tr class="font-weight-bold"><td>Total</td><td class="text-right">{{ number_format($total, 0, ',', '.') }}</td><td></td></tr></tfoot>
</table></div></div>
<div class="col-md-8"><div style="position:relative;height:420px"><canvas id="monthlyChart" role="img" aria-label="Grafik jumlah transaksi bulanan"></canvas></div></div>
</div></div></div></div></section></div>
<div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailTitle" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content">
<div class="modal-header bg-indigo"><h5 class="modal-title" id="reportDetailTitle">Detail Transaksi</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button></div>
<div class="modal-body"><div id="reportDetailMessage" class="text-muted mb-2" role="status"></div>
<div class="table-responsive"><table class="table table-sm table-bordered table-striped"><thead><tr><th>No</th><th>Tanggal</th><th>Kode</th><th>Nama Customer</th><th>Telepon</th><th>Lokasi</th><th>Kavling</th></tr></thead><tbody id="reportDetailRows"></tbody></table></div>
<div class="d-flex align-items-center justify-content-between"><span id="reportDetailTotal"></span><div><button type="button" id="detailPrevious" class="btn btn-outline-secondary btn-sm">Sebelumnya</button> <button type="button" id="detailNext" class="btn btn-outline-secondary btn-sm">Berikutnya</button></div></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button></div>
</div></div></div>
@endsection
@push('scripts')
<script>
$(function () {
    const rows = @json($rows);
    const filters = {tahun: @json($tahun), jenis_transaksi: @json($jenisTransaksi)};
    const typeLabel = @json($jenisList[$jenisTransaksi]);
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar', data: {labels: rows.map(row => row.bulan), datasets: [{label: typeLabel, data: rows.map(row => row.jumlah), backgroundColor: '#3c8dbc', borderColor: '#367fa9', borderWidth: 1}]},
        options: {responsive: true, maintainAspectRatio: false, legend: {display: false}, scales: {yAxes: [{ticks: {beginAtZero: true, precision: 0}}]}}
    });
    let month = 1, page = 1, lastPage = 1, pending;
    function loadDetail(nextPage) {
        if (pending) pending.abort();
        page = nextPage;
        $('#reportDetailRows').empty();
        $('#reportDetailMessage').text('Memuat data...');
        $('#reportDetailTotal').empty();
        $('#detailPrevious, #detailNext').prop('disabled', true);
        pending = $.getJSON(@json(route('laporan.detail')), {...filters, bulan: month, page: page})
            .done(function (response) {
                lastPage = response.last_page;
                response.data.forEach(function (item, index) {
                    const tr = $('<tr>');
                    const date = item.tanggal ? item.tanggal.slice(0, 10).split('-').reverse().join('-') : '-';
                    [response.from + index, date, item.kode, item.nama, item.telepon, item.lokasi, item.kavling].forEach(value => $('<td>').text(value ?? '-').appendTo(tr));
                    $('#reportDetailRows').append(tr);
                });
                $('#reportDetailMessage').text(response.total ? '' : 'Tidak ada data pada bulan ini.');
                $('#reportDetailTotal').text('Total: ' + response.total + ' data | Halaman ' + response.current_page + ' / ' + lastPage);
                $('#detailPrevious').prop('disabled', page <= 1);
                $('#detailNext').prop('disabled', page >= lastPage);
            }).fail(function (xhr, status) {
                if (status !== 'abort') $('#reportDetailMessage').text('Gagal memuat detail. Silakan buka kembali detail bulan ini.');
            });
    }
    $('.report-detail').on('click', function () {
        month = Number($(this).data('month'));
        $('#reportDetailTitle').text(typeLabel + ' - ' + $(this).data('label') + ' ' + filters.tahun);
        $('#reportDetailModal').modal('show');
        loadDetail(1);
    });
    $('#detailPrevious').on('click', () => loadDetail(page - 1));
    $('#detailNext').on('click', () => loadDetail(page + 1));
    $('#reportDetailModal').on('hidden.bs.modal', function () { if (pending) pending.abort(); });
});
</script>
@endpush
