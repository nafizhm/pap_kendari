@php
    $types = app(\App\Services\BookingAttachments::class)->definitions($data);
    $folder = (int) $data->stt_reg === 2 ? 'assets/customer/' : 'assets/booking/';
@endphp
@foreach ($types->chunk(2) as $row)
    <div class="row mb-3">
        @foreach ($row as $type)
            @php
                $filename = $type->filename($data);
                $inputId = 'upload_' . $type->kode;
                $isPdf = $filename && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
            @endphp
            <div class="col-sm-6">
                <label for="{{ $inputId }}" class="form-label">{{ $type->nama }} @if ($type->aktif && !$type->trashed() && $type->wajib)<span class="text-danger">*</span>@endif</label>
                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                    @if ($filename)
                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset($folder . $filename) }}" data-pdf="{{ $isPdf ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                            @if ($isPdf)
                                <span class="text-primary">Lihat PDF</span>
                            @else
                                <img src="{{ asset($folder . $filename) }}" alt="{{ $type->nama }}" style="max-width:100%;max-height:100%;">
                            @endif
                        </button>
                    @else
                        <span class="text-muted">Tidak ada file</span>
                    @endif
                </div>
                <input type="file" name="{{ $type->inputName() }}" id="{{ $inputId }}" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
            </div>
        @endforeach
    </div>
@endforeach
