@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
        <div><h1 class="h4 font-weight-bold">Monitoring Berkas Bank</h1><p class="text-muted">Pemantauan ACC: 20 hari kalender sejak tanggal berkas diterima bank.</p></div>
        <a href="{{ route('wawancara.index') }}" class="btn btn-outline-primary">Kembali ke Wawancara</a>
    </div></section>
    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @php
            $today = \Carbon\Carbon::today('Asia/Jakarta');
            $approved = $records->filter(fn ($record) => $record->wawancaraSp3k->isNotEmpty());
            $overdue = $records->filter(fn ($record) => $record->wawancaraSp3k->isEmpty() && $today->gt(\Carbon\Carbon::parse($record->tanggal_masuk_bank)->addDays(20)));
        @endphp
        <div class="row">
            @foreach (['Berkas Masuk' => $records->count(), 'Sudah ACC' => $approved->count(), 'Menunggu ACC' => $records->count() - $approved->count(), 'Perlu Tindak Lanjut' => $overdue->count()] as $label => $count)
                <div class="col-md-3 col-6"><div class="small-box {{ $label === 'Perlu Tindak Lanjut' ? 'bg-danger' : 'bg-primary' }}"><div class="inner"><h3>{{ $count }}</h3><p>{{ $label }}</p></div></div></div>
            @endforeach
        </div>
        <div class="card card-outline card-primary"><div class="card-header"><h3 class="card-title">Masukkan Berkas ke Bank</h3></div><div class="card-body">
            <form id="bank-submission" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="form-group col-md-4"><label for="bank-customer">Nasabah / Bank</label><select id="bank-customer" class="form-control" required><option value="">Pilih nasabah</option>@foreach ($pending as $record)<option value="{{ route('wawancara.masuk-bank', $record->id) }}" @selected((string) request('nasabah') === (string) $record->id)>{{ $record->customer->nama_lengkap }} ? {{ $record->bankKPR->nama ?? '-' }}</option>@endforeach</select></div>
                    <div class="form-group col-md-4"><label for="bank-date">Tanggal Diterima Bank</label><input id="bank-date" type="date" name="tanggal_masuk_bank" class="form-control" value="{{ $today->toDateString() }}" max="{{ $today->toDateString() }}" required></div>
                    <div class="form-group col-md-4"><label for="bank-officer">Petugas Penerima Bank</label><input id="bank-officer" name="petugas_bank" class="form-control" maxlength="255" required></div>
                </div>
                <div class="form-group"><label for="bank-receipt">Surat Tanda Terima Berkas Bertanda Tangan</label><input id="bank-receipt" type="file" name="tanda_terima_bank" class="form-control-file" accept="application/pdf,image/jpeg,image/png" required><small class="text-muted">Unggah surat dari bank yang sudah ditandatangani pemohon dan petugas bank. PDF/JPG/PNG, maksimal 10 MB.</small></div>
                <div class="form-check mb-3"><input id="signature-confirm" class="form-check-input" type="checkbox" name="konfirmasi_tanda_tangan" value="1" required><label class="form-check-label" for="signature-confirm">Saya memastikan surat telah ditandatangani pemohon dan petugas bank.</label></div>
                <button class="btn btn-primary" type="submit">Simpan Penyerahan Berkas</button>
            </form>
        </div></div>
        <div class="card"><div class="card-header"><h3 class="card-title">Pelacakan Berkas & Tindak Lanjut</h3></div><div class="card-body table-responsive">
            <table class="table table-bordered table-striped"><thead><tr><th>Nasabah / Lokasi</th><th>Bank / Petugas</th><th>Tanggal Masuk / Batas ACC</th><th>Status</th><th>Tanda Terima</th><th>Tindak Lanjut</th></tr></thead><tbody>
                @forelse ($records as $record)
                    @php
                        $deadline = \Carbon\Carbon::parse($record->tanggal_masuk_bank)->addDays(20);
                        $isApproved = $record->wawancaraSp3k->isNotEmpty();
                        $isOverdue = ! $isApproved && $today->gt($deadline);
                    @endphp
                    <tr><td><strong>{{ $record->customer->nama_lengkap ?? '-' }}</strong><br>{{ $record->customer?->lokasi?->nama_kavling }} / {{ $record->customer?->kavling?->kode_kavling }}</td>
                    <td>{{ $record->bankKPR->nama ?? '-' }}<br><small>{{ $record->petugas_bank }}</small></td>
                    <td>{{ \Carbon\Carbon::parse($record->tanggal_masuk_bank)->format('d/m/Y') }}<br><strong>{{ $deadline->format('d/m/Y') }}</strong></td>
                    <td><span class="badge badge-{{ $isApproved ? 'success' : ($isOverdue ? 'danger' : 'warning') }}">{{ $isApproved ? 'Sudah ACC' : 'Belum di-ACC' }}</span>@if ($isOverdue)<div class="text-danger small mt-2">Lewat 20 hari. Hubungi bank untuk menanyakan penyebab belum ada kemajuan.</div>@endif</td>
                    <td><a class="btn btn-outline-primary btn-sm" href="{{ route('wawancara.tanda-terima-bank', $record->id) }}" target="_blank" rel="noopener">Lihat Surat</a></td>
                    <td style="min-width:250px"><div class="small mb-2" style="white-space:pre-wrap">{{ $record->tindak_lanjut_bank ?: 'Belum ada tindak lanjut.' }}</div>
                    @unless ($isApproved)<form method="POST" action="{{ route('wawancara.tindak-lanjut-bank', $record->id) }}">@csrf<textarea class="form-control mb-2" name="tindak_lanjut_bank" rows="2" maxlength="5000" placeholder="Catat tanggal konfirmasi, jawaban bank, kendala, dan rencana tindak lanjut" aria-label="Hasil tindak lanjut {{ $record->customer->nama_lengkap ?? '' }}" required></textarea><button class="btn btn-sm btn-primary">Catat Tindak Lanjut</button></form>@endunless</td></tr>
                @empty<tr><td colspan="6" class="text-center text-muted">Belum ada berkas yang masuk bank.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div></section>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('bank-submission').addEventListener('submit', function (event) {
        const target = document.getElementById('bank-customer').value;
        if (!target) { event.preventDefault(); return; }
        this.action = target;
    });
</script>
@endpush
