@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"></section><section class="content"><div class="container-fluid">
<div class="card"><div class="card-header p-3"><h3 class="font-weight-bold text-lg mb-0">Data Kelengkapan Berkas</h3></div>
<div class="card-body">
<div class="row mb-3"><div class="col-md-4"><label for="filter_lokasi">Lokasi Perumahan</label><select id="filter_lokasi" class="form-control"><option value="">Semua Perumahan</option>@foreach ($lokasiList as $lokasi)<option value="{{ $lokasi->id }}">{{ $lokasi->nama_kavling }}</option>@endforeach</select></div></div>
<div class="table-responsive"><table class="table table-bordered small table-striped data-table w-100"><thead><tr><th>No</th><th>Nama Customer</th>@foreach ($jenisBerkas as $jenis)<th>{{ $jenis->nama }}</th>@endforeach<th>Action</th></tr></thead></table></div>
</div></div></div></section></div>
<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormLabel" data-backdrop="static"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-indigo"><h5 class="modal-title text-white font-weight-bold" id="modalFormLabel">Edit Kelengkapan Berkas</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button></div>
<form id="formData" enctype="multipart/form-data">@csrf<input type="hidden" id="primary_id">
<div class="modal-body" style="max-height:70vh;overflow-y:auto">
<div class="bg-light rounded p-3 mb-3"><small class="text-muted">Customer</small><div id="customer_name" class="font-weight-bold"></div><div class="small text-muted mt-1">Upload berkas untuk mengubah status menjadi Ada secara otomatis. JPG, PNG, WEBP atau PDF, maksimal 10 MB per berkas.</div></div>
<div class="row">
@foreach ($jenisBerkas as $jenis)
<div class="col-md-6 mb-3"><div class="border rounded p-3 h-100 legal-document" data-id="{{ $jenis->id }}">
<div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $jenis->nama }}</strong><span class="badge document-badge">Belum Ada</span></div>
<label for="status_berkas_{{ $jenis->id }}" class="small text-muted">Status Berkas</label><select class="form-control form-control-sm mb-2 document-status" name="status_berkas[{{ $jenis->id }}]" id="status_berkas_{{ $jenis->id }}"><option value="0">Belum Ada</option><option value="1">Ada</option></select>
<div class="document-existing mb-2"></div>
<label for="berkas_files_{{ $jenis->id }}" class="small">Upload / Ganti Berkas</label><input type="file" class="form-control-file document-upload" name="berkas_files[{{ $jenis->id }}]" id="berkas_files_{{ $jenis->id }}" accept=".jpg,.jpeg,.png,.webp,.pdf">
<div class="document-preview mt-2 small"></div>
</div></div>
@endforeach
</div>
<div class="form-group mb-0"><label for="catatan_kekurangan">Catatan Berkas</label><textarea name="catatan_kekurangan" id="catatan_kekurangan" class="form-control" rows="3" placeholder="Tambahkan catatan kelengkapan atau berkas yang masih diperlukan"></textarea></div>
<div id="form_error" class="text-danger small mt-2" role="alert"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-save mr-1"></i> Simpan Berkas</button></div>
</form></div></div></div>
@endsection
@push('scripts')
<script>
$(function () {
    const permissions = @json($permissions);
    const baseUrl = @json(asset('assets/legal/pengajuan_berkas/berkas'));
    const table = $('.data-table').DataTable({processing:true, serverSide:false, ordering:false, responsive:false, autoWidth:false,
        ajax:{url:@json(route('pengajuan-berkas.index')), data:function(data){data.id_lokasi=$('#filter_lokasi').val();}},
        columns:[{data:'DT_RowIndex', searchable:false}, {data:'nama_customer'},
            @foreach ($jenisBerkas as $jenis)
            {data:'berkas_{{ $jenis->id }}', searchable:false, className:'text-center document-column', width:'90px'},
            @endforeach
            {data:'action', searchable:false, className:'text-center', width:'145px'}]
    });
    $('#filter_lokasi').select2({theme:'bootstrap4', width:'100%'}).on('change',function(){table.ajax.reload();});
    function statusBadge(card) {
        const exists = card.find('.document-status').val() === '1';
        card.find('.document-badge').text(exists ? 'Ada' : 'Belum Ada').toggleClass('badge-success',exists).toggleClass('badge-secondary',!exists);
    }
    function resetFiles() {
        $('.document-upload').each(function(){ if(this.dataset.previewUrl) URL.revokeObjectURL(this.dataset.previewUrl); delete this.dataset.previewUrl; });
        $('#formData')[0].reset();
        $('.document-existing, .document-preview, #form_error').empty();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.document-status').prop('disabled',false);
    }
    $(document).on('click','.edit-button',function(){
        if ($(this).hasClass('disabled')) return;
        $.get($(this).data('url')).done(function(response){
            resetFiles();
            const data=response.data;
            $('#primary_id').val(data.id);
            $('#customer_name').text(data.customer?.nama_lengkap || '-');
            $('#catatan_kekurangan').val(data.catatan_kekurangan);
            $('.legal-document').each(function(){
                const card=$(this), id=String(card.data('id')), file=(data.file_jenis_berkas || {})[id];
                const status=file ? '1' : String((data.status_jenis_berkas || {})[id] ?? 0);
                card.data('saved-status',status);
                card.find('.document-status').val(status).prop('disabled',!!file);
                if(file) $('<a>',{href:baseUrl+'/'+encodeURIComponent(file),class:'btn btn-outline-primary btn-sm legal-preview', 'data-url':baseUrl+'/'+encodeURIComponent(file), 'data-title':card.find('strong').text()}).text('Lihat berkas tersimpan').appendTo(card.find('.document-existing'));
                statusBadge(card);
            });
            $('#modalForm').modal('show');
        }).fail(function(){toastr.error('Gagal memuat data berkas.');});
    });
    $('.document-status').on('change',function(){statusBadge($(this).closest('.legal-document'));});
    $('.document-upload').on('change',function(){
        const card=$(this).closest('.legal-document'), file=this.files[0];
        if(this.dataset.previewUrl) URL.revokeObjectURL(this.dataset.previewUrl);
        card.find('.document-preview').empty();
        if(file && (file.size>10*1024*1024 || !['image/jpeg','image/png','image/webp','application/pdf'].includes(file.type))){this.value='';toastr.error('Pilih JPG, PNG, WEBP atau PDF, maksimal 10 MB.');}
        if(this.files[0]){
            this.dataset.previewUrl=URL.createObjectURL(file);
            $('<a>',{href:this.dataset.previewUrl,target:'_blank',rel:'noopener',class:'d-block mb-2'}).text(file.name).appendTo(card.find('.document-preview'));
            if(file.type.startsWith('image/')) $('<img>',{src:this.dataset.previewUrl,alt:'Preview berkas',class:'img-thumbnail mb-2'}).css({maxHeight:'130px',maxWidth:'100%'}).appendTo(card.find('.document-preview'));
            $('<button>',{type:'button',class:'btn btn-outline-secondary btn-sm cancel-upload'}).text('Batalkan pilihan').appendTo(card.find('.document-preview'));
            card.find('.document-status').val('1').prop('disabled',true);
        }else{card.find('.document-status').val(card.data('saved-status')).prop('disabled',card.find('.document-existing a').length>0);}
        statusBadge(card);
    });
    $(document).on('click','.cancel-upload',function(){ $(this).closest('.legal-document').find('.document-upload').val('').trigger('change'); });
    $('#modalForm').on('hidden.bs.modal',resetFiles);
    $('#formData').on('submit',function(event){
        event.preventDefault();
        const formData=new FormData(this); formData.append('_method','PUT');
        $('.document-status:disabled').each(function(){formData.set(this.name,this.value);});
        $('#form_error').empty();$('.is-invalid').removeClass('is-invalid');$('.invalid-feedback').remove();
        $('#submitBtn').prop('disabled',true).text('Menyimpan...');
        $.ajax({url:@json(route('pengajuan-berkas.update', ['pengajuan_berka'=>':id'])).replace(':id',$('#primary_id').val()),method:'POST',data:formData,processData:false,contentType:false})
        .done(function(){ $('#modalForm').modal('hide');table.ajax.reload(null,false);toastr.success('Berkas berhasil disimpan.'); })
        .fail(function(xhr){
            const errors=xhr.responseJSON?.errors || {};
            $('#form_error').text(Object.values(errors).flat()[0] || 'Gagal menyimpan berkas. Silakan coba kembali.');
            Object.entries(errors).forEach(function([key,messages]){
                const name=key.replace(/^(status_berkas|berkas_files)\.(.+)$/,'$1[$2]');
                const input=$('#formData :input').filter(function(){return this.name===name;});
                input.addClass('is-invalid');$('<div class="invalid-feedback d-block">').text(messages[0]).insertAfter(input);
            });
        }).always(function(){ $('#submitBtn').prop('disabled',false).html('<i class="fas fa-save mr-1"></i> Simpan Berkas'); });
    });
});
</script>
@endpush

<style>
.data-table { table-layout:fixed; min-width:{{ 360 + $jenisBerkas->count() * 90 }}px; }
.data-table .document-column { width:90px !important; min-width:90px; max-width:90px; text-align:center; vertical-align:middle; overflow-wrap:anywhere; }
#legalPreviewModal { z-index:1070; }
</style>
<div class="modal fade" id="legalPreviewModal" tabindex="-1" aria-labelledby="legalPreviewTitle"><div class="modal-dialog modal-xl"><div class="modal-content">
<div class="modal-header"><h5 id="legalPreviewTitle" class="modal-title">Preview Berkas</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button></div>
<div class="modal-body text-center" style="max-height:80vh;overflow:auto"><img id="legalPreviewImage" alt="Preview berkas" style="width:auto;height:auto;max-width:100%;max-height:70vh"><iframe id="legalPreviewPdf" title="Preview PDF" style="display:none;width:100%;height:70vh;border:0"></iframe></div>
</div></div></div>
<div class="modal fade" id="legalPrintModal" tabindex="-1" aria-labelledby="legalPrintTitle"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-indigo"><h5 id="legalPrintTitle" class="modal-title">Urutan Cetak Berkas</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button></div>
<form id="legalPrintForm" method="POST" target="_blank">@csrf
<div class="modal-body"><div id="printCustomer" class="font-weight-bold mb-2"></div><p class="small text-muted">Pilih berkas yang akan dicetak. Gunakan panah untuk mengatur urutan halaman PDF.</p><div id="printDocuments"></div><div id="printMessage" class="small text-muted mt-2" role="status"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" id="printSubmit" class="btn btn-primary">Cetak PDF</button></div>
</form></div></div></div>
@push('scripts')
<script>
$(function(){
    $(document).on('click','.legal-preview',function(event){
        event.preventDefault();
        const url=$(this).attr('data-url'), isPdf=/\.pdf(?:\?|$)/i.test(url);
        $('#legalPreviewTitle').text($(this).attr('data-title') || 'Preview Berkas');
        $('#legalPreviewImage').toggle(!isPdf).attr('src',isPdf ? '' : url);
        $('#legalPreviewPdf').toggle(isPdf).attr('src',isPdf ? url+'#zoom=100' : '');
        $('#legalPreviewModal').modal('show');
    });
    $('#legalPreviewModal').on('hidden.bs.modal',function(){
        $('#legalPreviewImage, #legalPreviewPdf').removeAttr('src');
        if($('#modalForm').hasClass('show')) $('body').addClass('modal-open');
    });
    function refreshPrint(){ $('#printSubmit').prop('disabled',!$('#printDocuments input:checked').length); }
    $(document).on('click','.legal-print',function(){
        const button=$(this);
        $('#printDocuments').empty();$('#printMessage').text('Memuat berkas...');$('#printSubmit').prop('disabled',true);
        $('#legalPrintForm').attr('action',button.attr('data-print-url'));
        $('#legalPrintModal').modal('show');
        $.getJSON(button.attr('data-url')).done(function(response){
            $('#printCustomer').text(response.data.customer?.nama_lengkap || '-');
            const files=response.data.file_jenis_berkas || {};
            response.jenis.filter(type=>files[type.id]).forEach(function(type){
                const row=$('<div class="d-flex align-items-center border rounded p-2 mb-2 print-document">');
                const label=$('<label class="mb-0 flex-grow-1">');
                $('<input>',{type:'checkbox',name:'urutan[]',value:type.id,checked:true,class:'mr-2'}).appendTo(label);
                label.append(document.createTextNode(type.nama));row.append(label);
                $('<button>',{type:'button',class:'btn btn-outline-secondary btn-sm mr-1 print-up','aria-label':'Naikkan urutan'}).text('↑').appendTo(row);
                $('<button>',{type:'button',class:'btn btn-outline-secondary btn-sm print-down','aria-label':'Turunkan urutan'}).text('↓').appendTo(row);
                $('#printDocuments').append(row);
            });
            $('#printMessage').text($('#printDocuments .print-document').length ? '' : 'Belum ada file yang diunggah untuk dicetak.');refreshPrint();
        }).fail(function(){ $('#printMessage').text('Gagal memuat berkas. Silakan coba kembali.'); });
    });
    $(document).on('click','.print-up',function(){const row=$(this).closest('.print-document');row.prev('.print-document').before(row);});
    $(document).on('click','.print-down',function(){const row=$(this).closest('.print-document');row.next('.print-document').after(row);});
    $(document).on('change','#printDocuments input',refreshPrint);
    $('#legalPrintForm').on('submit',function(event){if(!$('#printDocuments input:checked').length) event.preventDefault();});
});
</script>
@endpush
