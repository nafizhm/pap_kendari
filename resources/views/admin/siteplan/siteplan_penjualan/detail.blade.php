<style>
    #modalDetail .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 70px #15234133; }
    #modalDetail .modal-header { padding: 22px 26px; background: #172e50; color: #fff; border: 0; align-items: center; }
    #modalDetail .modal-header .close { color: #fff; opacity: .85; }
    #modalDetail .unit-eyebrow { font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: #b7c8e1; margin-bottom: 5px; }
    #modalDetail .modal-title { font-size: 24px; font-weight: 700; }
    #modalDetail .unit-location { font-size: 13px; color: #d0dcec; margin-top: 5px; }
    #modalDetail .modal-body { padding: 24px; background: #f4f7fb; }
    #modalDetail .unit-tabs { gap: 6px; border-bottom: 1px solid #dde5ef; margin-bottom: 22px; flex-wrap: wrap; }
    #modalDetail .unit-tabs .nav-link { color: #63738a; border: 0; padding: 12px 16px; font-size: 14px; font-weight: 600; border-radius: 8px 8px 0 0; }
    #modalDetail .unit-tabs .nav-link.active { background: #fff; color: #204f91; box-shadow: inset 0 -3px #3474cc; }
    #modalDetail .detail-card { background: #fff; border: 1px solid #e4eaf2; border-radius: 12px; padding: 22px; margin-bottom: 18px; }
    #modalDetail .detail-heading { font-size: 15px; font-weight: 700; color: #223b5d; margin-bottom: 18px; }
    #modalDetail .detail-heading i { color: #5981b2; margin-right: 8px; }
    #modalDetail .unit-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    #modalDetail .unit-stat { padding: 17px; background: #eaf0f8; border-radius: 10px; }
    #modalDetail .unit-stat span { display: block; font-size: 12px; color: #65768c; margin-bottom: 6px; }
    #modalDetail .unit-stat strong { font-size: 19px; color: #203c62; overflow-wrap: anywhere; }
    #modalDetail .detail-list { margin: 0; }
    #modalDetail .detail-line { display: flex; justify-content: space-between; gap: 24px; padding: 11px 0; border-bottom: 1px solid #eef2f6; }
    #modalDetail .detail-line:last-child { border: 0; padding-bottom: 0; }
    #modalDetail .detail-line dt { font-size: 13px; font-weight: 400; color: #718096; flex: 1; }
    #modalDetail .detail-line dd { font-size: 14px; color: #263d59; font-weight: 600; margin: 0; text-align: right; flex: 1; overflow-wrap: anywhere; white-space: pre-line; }
    #modalDetail .price-card { border-top: 4px solid #3275b5; }
    #modalDetail .price-note { font-size: 12px; color: #77869a; margin-top: -10px; margin-bottom: 20px; }
    #modalDetail .price-row { display: flex; align-items: baseline; justify-content: space-between; gap: 18px; padding: 13px 0; border-bottom: 1px dashed #e3e9f0; font-size: 14px; }
    #modalDetail .price-row span { color: #62738a; overflow-wrap: anywhere; }
    #modalDetail .price-row strong { color: #243d5e; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    #modalDetail .price-total { margin-top: 22px; padding: 20px; border-radius: 10px; background: #eaf4ef; color: #24654d; }
    #modalDetail .price-total span { display: block; font-size: 12px; font-weight: 600; margin-bottom: 7px; }
    #modalDetail .price-total strong { display: block; font-size: 26px; font-weight: 700; overflow-wrap: anywhere; }
    #modalDetail .unit-status { display: inline-block; padding: 5px 12px; background: #edf4ff; color: #28568a; border-radius: 20px; font-size: 12px; font-weight: 600; }
    #modalDetail .detail-empty { text-align: center; padding: 28px 16px; color: #7b899d; font-size: 14px; }
    #modalDetail .table { font-size: 13px; margin-bottom: 0; }
    #modalDetail .table th { border-top: 0; background: #f5f8fc; color: #697a90; font-weight: 600; }
    #modalDetail .table td { vertical-align: middle; color: #334a66; }
    #modalDetail .utility-photo { width: 100%; height: 210px; object-fit: contain; background: #f6f8fb; border: 1px solid #e8edf4; border-radius: 10px; }
    #modalDetail .modal-footer { background: #fff; border-color: #e8edf4; padding: 14px 24px; }
    @media (max-width: 575.98px) {
        #modalDetail .modal-header, #modalDetail .modal-body { padding: 16px; }
        #modalDetail .modal-title { font-size: 20px; }
        #modalDetail .unit-stats { grid-template-columns: 1fr; gap: 8px; }
        #modalDetail .unit-stat { display: flex; align-items: center; justify-content: space-between; }
        #modalDetail .unit-stat span { margin: 0; }
        #modalDetail .detail-card { padding: 16px; }
        #modalDetail .unit-tabs .nav-link { font-size: 12px; padding: 10px; }
        #modalDetail .price-total strong { font-size: 22px; }
    }
</style>

<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="unit-eyebrow">Siteplan Penjualan</div>
                    <h5 class="modal-title" id="modalDetailLabel">Detail Unit</h5>
                    <div class="unit-location" id="unitLocation">—</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="unitLoading" class="detail-empty" role="status"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat detail unit...</div>
                <div id="unitError" class="alert alert-danger d-none" role="alert"></div>
                <div id="unitDetailContent" class="d-none">
                    <ul class="nav nav-tabs unit-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="unitOverviewTab" href="#unitOverview" data-toggle="tab" role="tab" aria-controls="unitOverview" aria-selected="true">Unit & Harga</a></li>
                        <li class="nav-item"><a class="nav-link" id="unitCustomerTab" href="#unitCustomer" data-toggle="tab" role="tab" aria-controls="unitCustomer" aria-selected="false">Data Customer</a></li>
                        <li class="nav-item"><a class="nav-link" id="unitPaymentsTab" href="#unitPayments" data-toggle="tab" role="tab" aria-controls="unitPayments" aria-selected="false">Tagihan & Pembayaran</a></li>
                        <li class="nav-item"><a class="nav-link" id="unitUtilitiesTab" href="#unitUtilities" data-toggle="tab" role="tab" aria-controls="unitUtilities" aria-selected="false">Listrik & Air</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="unitOverview" role="tabpanel" aria-labelledby="unitOverviewTab">
                            <div class="row">
                                <div class="col-lg-7">
                                    <div class="unit-stats">
                                        <div class="unit-stat"><span>Luas tanah</span><strong id="unitLand">—</strong></div>
                                        <div class="unit-stat"><span>Luas bangunan</span><strong id="unitBuilding">—</strong></div>
                                        <div class="unit-stat"><span>Tipe bangunan</span><strong id="unitType">—</strong></div>
                                    </div>
                                    <section class="detail-card">
                                        <div class="d-flex justify-content-between align-items-center mb-3"><h6 class="detail-heading mb-0"><i class="fas fa-home" aria-hidden="true"></i>Informasi unit</h6><span id="unitStatus" class="unit-status"></span></div>
                                        <dl class="detail-list" id="unitSpecifications"></dl>
                                    </section>
                                    <section class="detail-card"><h6 class="detail-heading"><i class="fas fa-file-alt" aria-hidden="true"></i>Legalitas & Keterangan</h6><dl class="detail-list" id="unitLegal"></dl></section>
                                </div>
                                <div class="col-lg-5">
                                    <section class="detail-card price-card">
                                        <h6 class="detail-heading"><i class="fas fa-tags" aria-hidden="true"></i>Rincian Harga Rumah</h6>
                                        <p class="price-note">Seluruh komponen harga sesuai data Kavling.</p>
                                        <div id="unitPrices"></div>
                                        <div class="price-total"><span>Total Harga</span><strong id="unitTotal">Rp 0</strong></div>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="unitCustomer" role="tabpanel" aria-labelledby="unitCustomerTab"><section class="detail-card"><h6 class="detail-heading"><i class="fas fa-user" aria-hidden="true"></i>Informasi Customer</h6><div id="customerEmpty" class="detail-empty d-none">Belum ada customer untuk unit ini.</div><dl class="detail-list" id="customerDetails"></dl></section></div>
                        <div class="tab-pane fade" id="unitPayments" role="tabpanel" aria-labelledby="unitPaymentsTab">
                            <section class="detail-card"><h6 class="detail-heading">Tagihan</h6><div class="table-responsive"><table class="table"><thead><tr><th>No</th><th>Deskripsi Tagihan</th><th class="text-right">Nominal</th></tr></thead><tbody id="unitBills"></tbody><tfoot><tr><th colspan="2">Total Tagihan</th><th id="unitBillTotal" class="text-right"></th></tr></tfoot></table></div></section>
                            <section class="detail-card"><h6 class="detail-heading">Pembayaran Masuk</h6><div class="table-responsive"><table class="table"><thead><tr><th>No</th><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th class="text-right">Nominal</th></tr></thead><tbody id="unitReceipts"></tbody><tfoot><tr><th colspan="4">Total Pembayaran</th><th id="unitReceiptTotal" class="text-right"></th></tr></tfoot></table></div></section>
                        </div>
                        <div class="tab-pane fade" id="unitUtilities" role="tabpanel" aria-labelledby="unitUtilitiesTab"><div class="row">
                            <div class="col-md-6"><section class="detail-card"><h6 class="detail-heading"><i class="fas fa-bolt" aria-hidden="true"></i>Listrik</h6><dl class="detail-list mb-3" id="electricityDetails"></dl><div id="electricityPhotos"></div></section></div>
                            <div class="col-md-6"><section class="detail-card"><h6 class="detail-heading"><i class="fas fa-tint" aria-hidden="true"></i>Air</h6><dl class="detail-list mb-3" id="waterDetails"></dl><div id="waterPhotos"></div></section></div>
                        </div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button><button type="button" class="btn btn-primary" id="btn-cetak" disabled><i class="fas fa-print mr-2" aria-hidden="true"></i>Cetak Data Unit</button></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedSiteplanUnit = null;
    let siteplanDetailRequest = null;
    const siteplanMoney = value => 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
    const siteplanValue = value => value === null || value === undefined || value === '' ? '—' : String(value);
    const siteplanMeasure = (value, unit) => value === null || value === undefined || value === '' ? '—' : Number(value).toLocaleString('id-ID') + ' ' + unit;
    function siteplanDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        return isNaN(date.getTime()) ? '—' : date.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
    }
    function siteplanDetails(selector, entries) {
        const list = $(selector).empty();
        entries.forEach(([label, value]) => {
            const line = $('<div class="detail-line">');
            $('<dt>').text(label).appendTo(line);
            $('<dd>').text(siteplanValue(value)).appendTo(line);
            list.append(line);
        });
    }
    function siteplanTable(selector, rows, columns) {
        const body = $(selector).empty();
        if (!rows.length) {
            body.append($('<tr>').append($('<td class="detail-empty">').attr('colspan', columns).text('Belum ada data.')));
        }
        rows.forEach(values => {
            const row = $('<tr>');
            values.forEach((value, index) => $('<td>').text(siteplanValue(value)).toggleClass('text-right text-nowrap', index === columns - 1).appendTo(row));
            body.append(row);
        });
    }
    function siteplanPhotos(selector, photos) {
        const container = $(selector).empty();
        photos.forEach(([file, folder, label]) => {
            if (!file) return;
            const figure = $('<figure class="mb-3">');
            $('<img class="utility-photo" loading="lazy">').attr({src: '/assets/legal/listrik_air/' + folder + '/' + encodeURIComponent(file), alt: label}).appendTo(figure);
            $('<figcaption class="small text-muted mt-2">').text(label).appendTo(figure);
            container.append(figure);
        });
        if (!container.children().length) container.append($('<div class="detail-empty">').text('Belum ada foto.'));
    }
    function renderSiteplanUnit(response) {
        const unit = response.data;
        const customer = unit.customer;
        const utilities = unit.listrik_air || response.listrik_air || {};
        selectedSiteplanUnit = unit;
        $('#modalDetailLabel').text('Unit ' + siteplanValue(unit.kode_kavling));
        $('#unitLocation').text(siteplanValue(unit.lokasi?.nama_kavling));
        $('#unitStatus').text(customer?.progres?.status_progres || (customer ? 'Terjual' : (unit.is_booked ? 'Booking' : 'Tersedia')));
        $('#unitLand').text(siteplanMeasure(unit.luas_tanah, 'm²'));
        $('#unitBuilding').text(siteplanMeasure(unit.luas_bangunan, 'm²'));
        $('#unitType').text(siteplanValue(unit.tipe_bangunan));
        siteplanDetails('#unitSpecifications', [
            ['Perumahan', unit.lokasi?.nama_kavling], ['Blok / Kavling', unit.kode_kavling],
            ['Panjang', siteplanMeasure(unit.panjang, 'm')],
            ['Lebar', siteplanMeasure(unit.lebar, 'm')],
            ['Daya listrik', siteplanMeasure(unit.daya_listrik, 'VA')], ['Harga per m²', siteplanMoney(unit.hrg_meter)]
        ]);
        siteplanDetails('#unitLegal', [['ID Rumah Sikumbang', unit.id_rumah_sikumbang], ['No. Sertipikat', unit.no_sertifikat], ['Keterangan', unit.keterangan]]);
        const prices = Array.isArray(unit.rincian_biaya) ? unit.rincian_biaya : [];
        const priceList = $('#unitPrices').empty();
        prices.forEach(item => {
            const row = $('<div class="price-row">');
            $('<span>').text(siteplanValue(item.nama)).appendTo(row);
            $('<strong>').text(siteplanMoney(item.nilai)).appendTo(row);
            priceList.append(row);
        });
        if (!prices.length) priceList.append($('<div class="detail-empty">').text('Rincian harga belum diisi.'));
        $('#unitTotal').text(siteplanMoney(unit.total_harga));
        $('#customerEmpty').toggleClass('d-none', !!customer);
        siteplanDetails('#customerDetails', customer ? [
            ['Nama lengkap', customer.nama_lengkap], ['NIK', customer.nik], ['Nomor telepon', customer.no_telp],
            ['Tempat lahir', customer.tempat_lahir], ['Tanggal lahir', siteplanDate(customer.tgl_lahir)], ['Jenis kelamin', customer.jenis_kelamin],
            ['Alamat KTP', customer.alamat_ktp], ['Alamat domisili', customer.alamat_domisili], ['NPWP', customer.npwp],
            ['Pekerjaan', customer.pekerjaan], ['Jenis pembelian', customer.jenis_pembelian], ['Marketing', customer.marketing?.nama_marketing]
        ] : []);
        siteplanTable('#unitBills', (response.tagihan || []).map((item, i) => [i + 1, item.deskripsi, siteplanMoney(item.nominal)]), 3);
        siteplanTable('#unitReceipts', (response.pemasukan || []).map((item, i) => [i + 1, siteplanDate(item.tanggal), item.kategori?.kategori, item.keterangan, siteplanMoney(item.nominal)]), 5);
        $('#unitBillTotal').text(siteplanMoney(response.total_tagihan));
        $('#unitReceiptTotal').text(siteplanMoney(response.total_pemasukan));
        siteplanDetails('#electricityDetails', [['No. rekening listrik', utilities.norek_listrik], ['Daya listrik', siteplanMeasure(unit.daya_listrik, 'VA')]]);
        siteplanDetails('#waterDetails', [['No. rekening air', utilities.norek_air]]);
        siteplanPhotos('#electricityPhotos', [[utilities.foto_listrik, 'listrik_1', 'Foto listrik 1'], [utilities.foto_listrik_2, 'listrik_2', 'Foto listrik 2']]);
        siteplanPhotos('#waterPhotos', [[utilities.foto_air, 'air_1', 'Foto air 1'], [utilities.foto_air_2, 'air_2', 'Foto air 2']]);
        $('#unitLoading').addClass('d-none');
        $('#unitDetailContent').removeClass('d-none');
        $('#btn-cetak').prop('disabled', false);
    }
    $(document).on('click', '.detail-button', function() {
        if (siteplanDetailRequest) siteplanDetailRequest.abort();
        selectedSiteplanUnit = null;
        $('#btn-cetak').prop('disabled', true);
        $('#modalDetailLabel').text('Detail Unit');
        $('#unitLocation').text('');
        $('#unitDetailContent, #unitError').addClass('d-none');
        $('#unitLoading').removeClass('d-none');
        $('#unitOverviewTab').tab('show');
        $('#modalDetail').modal('show');
        siteplanDetailRequest = $.get($(this).data('url')).done(response => {
            if (response.success && response.data) renderSiteplanUnit(response);
            else showSiteplanError();
        }).fail((xhr, status) => { if (status !== 'abort') showSiteplanError(); });
    });
    function showSiteplanError() {
        $('#unitLoading').addClass('d-none');
        $('#unitError').removeClass('d-none').text('Detail unit belum dapat dimuat. Tutup panel lalu klik kavling untuk mencoba kembali.');
    }
    $('#modalDetail').on('hidden.bs.modal', function() {
        if (siteplanDetailRequest) siteplanDetailRequest.abort();
        selectedSiteplanUnit = null;
    });
    $('#btn-cetak').on('click', function() {
        if (!selectedSiteplanUnit) return;
        const unit = selectedSiteplanUnit;
        const customer = unit.customer || {};
        const data = {
            _token: $('meta[name="csrf-token"]').attr('content'), id_kavling: unit.id,
            kode_kavling: unit.kode_kavling, blok: unit.kode_kavling, status: $('#unitStatus').text(),
            luas_tanah: unit.luas_tanah, luas_bangunan: unit.luas_bangunan,
            nama_customer: customer.nama_lengkap, no_ktp: customer.nik, tempat_lahir: customer.tempat_lahir,
            tgl_lahir: customer.tgl_lahir, alamat: customer.alamat_ktp, no_hp: customer.no_telp, pekerjaan: customer.pekerjaan
        };
        const form = $('<form>').attr({method: 'POST', action: @json(route('penjualan.cetak')), target: '_blank'});
        Object.entries(data).forEach(([name, value]) => $('<input type="hidden">').attr('name', name).val(value ?? '').appendTo(form));
        form.appendTo(document.body)[0].submit();
        form.remove();
    });
    function toggleLegend() {
        const legend = document.getElementById('legend');
        const hidden = legend.style.right === '-300px';
        legend.style.right = hidden ? '30px' : '-300px';
        document.getElementById('show-btn').style.display = hidden ? 'none' : 'block';
    }
</script>
@endpush
