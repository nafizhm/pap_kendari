@extends('admin.layout_admin')
@push('css')<link rel="stylesheet" href="{{ asset('css/template-studio.css') }}">@endpush
@section('content')
<div class="content-wrapper template-studio">
    <section class="content-header"><div class="container-fluid"><div class="studio-hero"><div><span class="studio-eyebrow">CETAK BERKAS</span><h1>Dokumen rapi, siap setiap saat.</h1><p>Kelola template Word dan biarkan data customer mengisi dokumen Anda.</p></div><div class="studio-hero-icon"><i class="far fa-file-word"></i></div></div></div></section>
    <section class="content"><div class="container-fluid">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="card studio-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">Pengaturan Template</h3>
                @if($permissions['tambah'])<a href="{{ route('pengaturan-template.create') }}" class="btn btn-primary btn-sm ml-auto"><i class="fas fa-plus mr-1"></i> Tambah Template</a>@endif
            </div>
            <div class="card-body">
                <form method="GET" class="d-flex mb-4" role="search"><label for="template-search" class="sr-only">Cari template</label><input id="template-search" type="search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Cari nama cetak atau kode template..."><button class="btn btn-outline-primary">Cari</button>@if(request('q'))<a class="btn btn-link" href="{{ route('pengaturan-template.index') }}">Reset</a>@endif</form>
                <div class="table-responsive"><table class="table table-bordered table-striped">
                    <thead><tr><th>No</th><th>Nama Cetak</th><th>Kode</th><th>Deskripsi</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $templates->firstItem() + $loop->index }}</td>
                            <td><span class="studio-list-title">{{ $template->nama }}</span><small class="d-block text-muted mt-1">{{ $template->detected_variables === null ? 'Buka Edit untuk memeriksa variabel' : count($template->detected_variables).' variabel dalam Word' }}</small></td><td><code>{{ $template->kode }}</code></td>
                            <td>{{ $template->deskripsi ?: '-' }}</td>
                            <td><span class="badge badge-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-nowrap">
                                @if($template->file_path)<a class="btn btn-info btn-sm" href="{{ route('pengaturan-template.download', $template) }}">Unduh Word</a>@endif
                                @if($permissions['edit'])<a class="btn btn-primary btn-sm" href="{{ route('pengaturan-template.edit', $template) }}">Edit</a>@endif
                                @if($permissions['hapus'])
                                <form class="d-inline" method="POST" action="{{ route('pengaturan-template.destroy', $template) }}" onsubmit="return confirm('Hapus template ini beserta file Word-nya?')">
                                    @csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="studio-empty"><i class="far fa-file-word"></i><strong>{{ request('q') ? 'Template tidak ditemukan' : 'Mulai dengan template pertama Anda' }}</strong><p class="mt-2 mb-0">{{ request('q') ? 'Coba kata pencarian lain.' : 'Tambahkan Word, periksa variabel, lalu simpan untuk digunakan kembali.' }}</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                {{ $templates->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div></section>
</div>
@endsection
