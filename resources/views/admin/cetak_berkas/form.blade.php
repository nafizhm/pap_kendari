@extends('admin.layout_admin')
@push('css')
<link rel="stylesheet" href="{{ asset('css/template-studio.css') }}">
@endpush
@section('content')
<div class="content-wrapper template-studio">
<section class="content-header"><div class="container-fluid">
    <a href="{{ route('pengaturan-template.index') }}" class="studio-back"><i class="fas fa-arrow-left mr-2"></i> Pengaturan Template</a>
    <div class="studio-hero mt-3"><div><span class="studio-eyebrow">CETAK BERKAS / TEMPLATE WORD</span><h1>{{ $template->exists ? 'Sempurnakan template Anda' : 'Buat template, cetak lebih mudah' }}</h1><p>Siapkan dokumen sekali. Isi data customer secara otomatis setiap kali cetak.</p></div><div class="studio-hero-icon"><i class="far fa-file-word"></i></div></div>
    <div class="studio-steps"><span><b>1</b> Pilih variabel</span><span><b>2</b> Susun & unggah Word</span><span><b>3</b> Periksa & simpan</span></div>
</div></section>
<section class="content"><div class="container-fluid">
@if($errors->any())<div class="alert alert-danger"><strong>Periksa kembali isian Anda.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><small>Jika sebelumnya memilih file baru, silakan pilih ulang file tersebut.</small></div>@endif
<form id="template-form" method="POST" enctype="multipart/form-data" action="{{ $template->exists ? route('pengaturan-template.update', $template) : route('pengaturan-template.store') }}">
@csrf @if($template->exists) @method('PUT') @endif
<div class="row">
<div class="col-lg-7">
    <div class="card studio-card"><div class="card-body">
        <h2><i class="fas fa-sliders-h mr-2"></i> Identitas template</h2><p class="text-muted">Beri nama yang mudah dikenali saat memilih berkas untuk dicetak.</p>
        <div class="form-group"><label for="nama">Nama cetak <span class="text-danger">*</span></label><input id="nama" name="nama" class="form-control" maxlength="100" value="{{ old('nama', $template->nama) }}" placeholder="Contoh: Surat Pernyataan Customer" required></div>
        <div class="row"><div class="form-group col-md-8"><label for="kode">Kode template <span class="text-danger">*</span></label><input id="kode" name="kode" class="form-control" maxlength="50" pattern="[a-z0-9_]+" value="{{ old('kode', $template->kode) }}" placeholder="surat_pernyataan" required><small class="text-muted">Huruf kecil, angka, dan garis bawah. Harus unik.</small></div>
        <div class="form-group col-md-4"><label for="is_active">Status</label><select id="is_active" name="is_active" class="form-control"><option value="1" @selected(old('is_active', $template->is_active) == 1)>Aktif</option><option value="0" @selected(old('is_active', $template->is_active) == 0)>Nonaktif</option></select></div></div>
        <div class="form-group mb-0"><label for="deskripsi">Catatan <span class="text-muted font-weight-normal">(opsional)</span></label><textarea id="deskripsi" name="deskripsi" class="form-control" rows="2" maxlength="5000" placeholder="Kegunaan atau petunjuk penggunaan template ini">{{ old('deskripsi', $template->deskripsi) }}</textarea></div>
    </div></div>
    <div class="card studio-card"><div class="card-body">
        <h2><i class="fas fa-cloud-upload-alt mr-2"></i> Dokumen Word</h2>
        <div class="studio-upload" id="drop-zone"><i class="far fa-file-word"></i><strong>Pilih atau letakkan file Word di sini</strong><span>.docx • Maksimal 5 MB</span><label for="file_template" class="sr-only">Pilih dokumen Word</label><input type="file" id="file_template" name="file_template" accept=".docx" @required(!$template->file_path)><small id="file-name">{{ $template->file_path ? 'File tersimpan. Pilih file baru hanya jika ingin menggantinya.' : 'Variabel akan diperiksa otomatis setelah file dipilih.' }}</small></div>
        @if($template->file_path)<a class="btn btn-link px-0 mt-2" href="{{ route('pengaturan-template.download', $template) }}"><i class="fas fa-download mr-1"></i> Unduh Word saat ini</a>@endif
        <p class="studio-hint mt-3 mb-0"><i class="far fa-lightbulb mr-1"></i> Tempel kode seperti <code>${nama_customer}</code> ke Word. Teks utama, tabel, header, dan footer ikut diperiksa.</p>
    </div></div>
</div>
<div class="col-lg-5"><div class="card studio-card studio-inspection"><div class="card-body">
    <span class="studio-eyebrow">PEMERIKSAAN OTOMATIS</span><h2 class="mt-2">Variabel dalam dokumen</h2>
    <div id="inspection-status" class="studio-notice" role="status" aria-live="polite">Unggah Word untuk melihat hasil pemeriksaan.</div>
    <div class="studio-metrics"><div><strong id="known-count">—</strong><span>Dikenali</span></div><div><strong id="unknown-count">—</strong><span>Perlu diperbaiki</span></div><div><strong id="occurrence-count">—</strong><span>Kemunculan</span></div></div>
    <div id="detected-list" class="studio-detected"></div>
    <div id="selection-diff" class="studio-hint mt-3"></div>
    <p class="text-muted small mt-3 mb-0">Isi Word menentukan data yang digunakan saat cetak. Pilihan variabel di bawah membantu Anda menyusun dokumen.</p>
</div></div></div>
</div>
<div class="card studio-card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-start"><div><span class="studio-eyebrow">PUSTAKA VARIABEL</span><h2 class="mt-2">Pilih, salin, tempel ke Word</h2><p class="text-muted">Centang variabel untuk menyusun daftar pilihan. Salin satu per satu atau sekaligus.</p></div><span class="studio-pill"><span id="selected-count">0</span> dipilih</span></div>
    <div class="row mb-3"><div class="col-md-6 mb-2"><label class="sr-only" for="cari-variabel">Cari variabel</label><input id="cari-variabel" type="search" class="form-control" placeholder="Cari nama, alamat, kavling..."></div><div class="col-md-3 mb-2"><label class="sr-only" for="group-filter">Kategori variabel</label><select id="group-filter" class="form-control"><option value="">Semua kategori</option>@foreach($contextKeys as $group => $keys)<option>{{ $group }}</option>@endforeach</select></div><div class="col-md-3 mb-2"><label class="sr-only" for="view-filter">Tampilkan variabel</label><select id="view-filter" class="form-control"><option value="all">Semua variabel</option><option value="selected">Pilihan saya</option><option value="used">Ada dalam Word</option></select></div></div>
    <div class="studio-toolbar"><button type="button" id="copy-selected" class="btn btn-primary btn-sm"><i class="far fa-copy mr-1"></i> Salin pilihan</button><button type="button" id="select-detected" class="btn btn-outline-primary btn-sm">Pilih sesuai Word</button><button type="button" id="clear-selected" class="btn btn-link btn-sm">Kosongkan pilihan</button><span id="copy-status" class="small" role="status" aria-live="polite"></span></div>
    <div class="studio-variable-grid mt-3">
    @foreach($contextKeys as $group => $keys) @foreach($keys as $key)
        <div class="studio-variable" data-key="{{ $key }}" data-group="{{ $group }}">
            <label><input type="checkbox" name="selected_variables[]" value="{{ $key }}" @checked(in_array($key, old('selected_variables', $template->selected_variables ?? [])))><span>{{ \Illuminate\Support\Str::headline($key) }}<small>{{ $group }}</small></span></label>
            <div class="d-flex align-items-center justify-content-between"><code>{{ '${'.$key.'}' }}</code><button type="button" class="studio-copy" data-code="{{ '${'.$key.'}' }}" aria-label="Salin {{ $key }}" title="Salin kode"><i class="far fa-copy"></i></button></div><span class="studio-used" hidden><i class="fas fa-check mr-1"></i> Ada dalam Word</span>
        </div>
    @endforeach @endforeach
    </div><p id="no-variables" class="text-center text-muted py-4" hidden>Tidak ada variabel yang cocok dengan filter.</p>
</div></div>
<div class="studio-save"><span id="save-hint">Periksa dokumen sebelum mengaktifkan template.</span><div><a href="{{ route('pengaturan-template.index') }}" class="btn btn-light mr-2">Kembali</a><button id="save-template" type="submit" class="btn btn-primary"><i class="far fa-save mr-1"></i> Simpan Template</button></div></div>
</form>
</div></section>
</div>
@endsection
@push('scripts')
<script>
window.templateStudio = {
    inspectUrl: @json(route('pengaturan-template.inspect')),
    inspection: @json($inspection), error: @json($inspectionError),
    existing: @json($template->exists)
};
</script>
<script src="{{ asset('js/template-studio.js') }}" defer></script>
@endpush
