@push('css')
<style>
    .dashboard-period { display:flex; align-items:center; flex-wrap:wrap; gap:12px; padding:18px; border:1px solid #e5e2f0; border-left:4px solid #7040ff; border-radius:5px; background:linear-gradient(110deg,#fff,#f5f2ff); box-shadow:0 2px 4px #26334d10; margin-bottom:16px; }
    .dashboard-period-title { display:flex; align-items:center; gap:9px; font-weight:700; }
    .dashboard-period-title i { background:#7040ef; color:white; padding:10px; border-radius:10px; }
    .dashboard-period-options { display:flex; flex-wrap:wrap; gap:3px; border-radius:24px; padding:4px; background:#eef1ff; }
    .dashboard-period-options a, .dashboard-period-options button { border:0; border-radius:20px; padding:6px 12px; background:#fafbfc; color:#65758b; font:inherit; cursor:pointer; }
    .dashboard-period-options .active { background:#007bff; color:white; font-weight:600; }
    .dashboard-period-active { margin-left:auto; padding:8px 16px; background:linear-gradient(110deg,#0aa8e8,#2461ee); color:white; border-radius:24px; font-size:13px; }
    .dashboard-period-form { display:flex; flex-wrap:wrap; align-items:center; gap:10px; border:1px solid #a9edbd; border-radius:15px; background:linear-gradient(110deg,#edfff5,#f8fafc); padding:16px; margin-bottom:16px; }
    .dashboard-period-form[hidden] { display:none; }
    .dashboard-period-form label { margin:0; color:#64748b; font-size:14px; }
    .dashboard-period-form input { border:1px solid #ced4da; border-radius:10px; padding:5px 10px; color:#34435a; background:white; }
    .dashboard-period-form button { border-radius:20px; padding:5px 22px; }
    @media(max-width:575px) { .dashboard-period-active { margin-left:0; } .dashboard-period-form input { max-width:100%; } }
</style>
@endpush

<div class="dashboard-period" aria-label="Filter periode dashboard">
    <div class="dashboard-period-title"><i class="fas fa-filter" aria-hidden="true"></i> Filter Periode</div>
    <div class="dashboard-period-options">
        <a href="{{ route('dashboard.index', ['period' => 'this_month']) }}" class="{{ $period->mode === 'this_month' ? 'active' : '' }}">Bulan Ini</a>
        <a href="{{ route('dashboard.index', ['period' => 'last_month']) }}" class="{{ $period->mode === 'last_month' ? 'active' : '' }}">Bulan Kemarin</a>
        <button type="button" data-period-panel="month" aria-controls="period-month" aria-expanded="{{ $period->mode === 'month' ? 'true' : 'false' }}" class="{{ $period->mode === 'month' ? 'active' : '' }}"><i class="far fa-calendar-alt" aria-hidden="true"></i> Pilih Bulan <i class="fas fa-caret-down" aria-hidden="true"></i></button>
        <button type="button" data-period-panel="custom" aria-controls="period-custom" aria-expanded="{{ $period->mode === 'custom' ? 'true' : 'false' }}" class="{{ $period->mode === 'custom' ? 'active' : '' }}"><i class="far fa-calendar-alt" aria-hidden="true"></i> Custom Tanggal <i class="fas fa-caret-down" aria-hidden="true"></i></button>
        <a href="{{ route('dashboard.index', ['period' => 'all']) }}" class="{{ $period->mode === 'all' ? 'active' : '' }}">Semua Waktu</a>
    </div>
    <span class="dashboard-period-active"><i class="fas fa-history" aria-hidden="true"></i> Aktif: <strong>{{ $period->label }}</strong></span>
</div>
@if($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
<form method="GET" action="{{ route('dashboard.index') }}" id="period-month" class="dashboard-period-form" @if($period->mode !== 'month') hidden @endif>
    <input type="hidden" name="period" value="month">
    <label for="dashboard-month">Pilih Bulan:</label>
    <input type="month" id="dashboard-month" name="month" value="{{ old('month', ($period->start ?? now())->format('Y-m')) }}" required>
    <button class="btn btn-success" type="submit"><i class="fas fa-search" aria-hidden="true"></i> Terapkan</button>
</form>
<form method="GET" action="{{ route('dashboard.index') }}" id="period-custom" class="dashboard-period-form" @if($period->mode !== 'custom' && !old('start_date')) hidden @endif>
    <input type="hidden" name="period" value="custom">
    <label for="dashboard-start">Dari Tanggal:</label>
    <input type="date" id="dashboard-start" name="start_date" value="{{ old('start_date', ($period->start ?? now()->startOfMonth())->format('Y-m-d')) }}" required>
    <label for="dashboard-end">Sampai Tanggal:</label>
    <input type="date" id="dashboard-end" name="end_date" value="{{ old('end_date', ($period->end ?? now()->endOfMonth())->format('Y-m-d')) }}" required>
    <button class="btn btn-success" type="submit"><i class="fas fa-search" aria-hidden="true"></i> Terapkan</button>
</form>
<p class="text-muted small">Customer dan statistik penjualan berdasarkan tanggal verifikasi; HOLD berdasarkan tanggal booking. Total unit dan unit ready adalah stok saat ini.</p>

@push('scripts')
<script>
    document.querySelectorAll('[data-period-panel]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.dashboard-period-options .active').forEach(option => option.classList.remove('active'));
            document.querySelectorAll('[data-period-panel]').forEach(option => option.setAttribute('aria-expanded', String(option === button)));
            button.classList.add('active');
            ['month', 'custom'].forEach(mode => { document.getElementById('period-' + mode).hidden = mode !== button.dataset.periodPanel; });
        });
    });
    const periodStart = document.getElementById('dashboard-start');
    const periodEnd = document.getElementById('dashboard-end');
    const updatePeriodMin = () => { periodEnd.min = periodStart.value; };
    periodStart.addEventListener('change', updatePeriodMin);
    updatePeriodMin();
</script>
@endpush
