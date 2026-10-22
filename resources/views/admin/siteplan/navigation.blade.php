@php
    $siteplanRoutes = ['siteplan-penjualan.index', 'siteplan-listrik.index', 'siteplan-air.index', 'st-balik-nama.index', 'st-bphtb-ssp.index', 'siteplan-unit-ready.index'];
    $siteplanMenuIds = \App\Models\HakAkses::where('id_user', auth()->id())->where('lihat', 1)->pluck('id_menu');
    $siteplanOptions = \App\Models\Menu::whereIn('id', $siteplanMenuIds)->whereIn('route_name', $siteplanRoutes)->orderBy('urutan')->get()->filter(fn ($item) => \Illuminate\Support\Facades\Route::has($item->route_name))->unique('route_name');
@endphp
<div class="d-flex flex-wrap align-items-end p-3" style="gap: 12px;">
    <div>
        <label for="siteplan-location">Lokasi Kavling</label>
        <select id="siteplan-location" class="custom-select" @disabled($lokasiKavling->isEmpty())>
            @forelse ($lokasiKavling as $kav)
                <option value="{{ $kav->id }}">{{ $kav->nama_kavling }}</option>
            @empty
                <option value="">Belum ada lokasi</option>
            @endforelse
        </select>
    </div>
    <div>
        <label for="siteplan-type">Jenis Siteplan</label>
        <select id="siteplan-type" class="custom-select">
            @foreach ($siteplanOptions as $option)
                <option value="{{ route($option->route_name) }}" @selected(request()->routeIs($option->route_name))>{{ $option->title }}</option>
            @endforeach
        </select>
    </div>
    <div id="siteplan-print-actions" class="d-flex flex-wrap" style="gap: 8px;"></div>
</div>
@push('scripts')
<script>
    $(function () {
        const locationSelect = $('#siteplan-location');
        const panels = $('#custom-tabs-four-tabContent').children('.tab-pane');
        const printActions = $('#siteplan-print-actions');
        panels.each(function () {
            const actions = $(this).children('a.btn[target="_blank"]');
            actions.attr('data-siteplan-location', this.id.replace('custom-tabs-four-', ''));
            printActions.append(actions);
        });
        function selectLocation() {
            const locationId = locationSelect.val();
            panels.removeClass('show active');
            $('#custom-tabs-four-' + locationId).addClass('show active');
            printActions.children().each(function () {
                $(this).toggleClass('d-none', $(this).attr('data-siteplan-location') !== locationId);
            });
            window.dispatchEvent(new Event('resize'));
        }
        const requestedLocation = new URL(window.location.href).searchParams.get('lokasi');
        if (requestedLocation && locationSelect.find('option').filter(function () { return this.value === requestedLocation; }).length) {
            locationSelect.val(requestedLocation);
        }
        locationSelect.on('change', selectLocation);
        $('#siteplan-type').on('change', function () {
            const target = new URL(this.value, window.location.origin);
            if (locationSelect.val()) target.searchParams.set('lokasi', locationSelect.val());
            window.location.assign(target.href);
        });
        selectLocation();
    });
</script>
@endpush
