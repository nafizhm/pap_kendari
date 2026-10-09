@php
    $types = app(\App\Services\BookingAttachments::class)->definitions();
@endphp
@foreach ($types->chunk(2) as $row)
    <div class="form-group row">
        @foreach ($row as $type)
            @php($inputId = 'upload_' . $type->kode)
            <label for="{{ $inputId }}" class="control-label col-sm-2">{{ $type->nama }} @if ($type->wajib)<span class="text-danger">*</span>@endif</label>
            <div class="col-sm-4">
                <input name="{{ $type->inputName() }}" id="{{ $inputId }}" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                    onchange="handleFileChange(this, 'preview_{{ $type->kode }}')">
                <div id="preview_{{ $type->kode }}" class="mt-2 d-none">
                    <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">View</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('{{ $inputId }}', 'preview_{{ $type->kode }}')">Hapus</button>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
