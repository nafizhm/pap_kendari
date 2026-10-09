@php
    $booking = $data ?? null;
    $types = app(\App\Services\BookingAttachments::class)->definitions($booking);
    $folder = $booking && (int) $booking->stt_reg === 2 ? 'assets/customer/' : 'assets/booking/';
@endphp
<div class="row">
    @foreach ($types as $type)
        @php
            $filename = $type->filename($booking);
            $enabled = $type->aktif && !$type->trashed();
            $inputId = 'upload_' . $type->kode;
        @endphp
        <div class="col-sm-6 mb-3">
            <label for="{{ $inputId }}">{{ $type->nama }} @if ($enabled && $type->wajib)<span class="text-danger">*</span>@endif</label>
            @if ($filename)
                <div class="mb-2">
                    <a href="{{ asset($folder . $filename) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Lihat berkas</a>
                    @if (empty($readonly) && !empty($allowDelete))
                        <button type="button" class="btn btn-sm btn-danger btnHapusFoto" data-field="{{ $type->kode }}" data-id="{{ $booking->id }}">Hapus berkas</button>
                    @endif
                </div>
            @elseif (!empty($readonly))
                <div class="text-muted">Belum diunggah</div>
            @endif
            @if (empty($readonly))
                <input type="file" name="{{ $type->inputName() }}" id="{{ $inputId }}" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control-file booking-file-input" data-frontend="{{ !empty($frontend) ? '1' : '0' }}"
                    @if (!empty($frontend)) onchange="handleFileChange(this, 'preview_{{ $type->kode }}')" @endif>
                <small class="text-muted">JPG, PNG, WEBP atau PDF, maksimal 10 MB. {{ $enabled && $type->wajib ? 'Wajib' : 'Opsional' }}.</small>
                @if (empty($frontend))
                    <div class="booking-new-preview mt-2"></div>
                @endif
                @if (!empty($frontend))
                    <div id="preview_{{ $type->kode }}" class="mt-2 d-none">
                        <button type="button" class="btn btn-sm btn-primary" onclick="showPreview(this)">Lihat</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('{{ $inputId }}', 'preview_{{ $type->kode }}')">Hapus</button>
                    </div>
                @endif
            @endif
        </div>
    @endforeach
</div>
@once
@push('scripts')
<script>
document.addEventListener('change', function (event) {
    const input = event.target;
    if (!input.classList.contains('booking-file-input') || input.dataset.frontend === '1') return;
    const preview = input.parentElement.querySelector('.booking-new-preview');
    if (input.dataset.previewUrl) URL.revokeObjectURL(input.dataset.previewUrl);
    preview.replaceChildren();
    const file = input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
        input.value = '';
        toastr.error('Pilih JPG, PNG, WEBP atau PDF, maksimal 10 MB.');
        return;
    }
    const link = document.createElement('a');
    input.dataset.previewUrl = URL.createObjectURL(file);
    link.href = input.dataset.previewUrl;
    link.target = '_blank';
    link.rel = 'noopener';
    link.className = 'btn btn-sm btn-outline-primary';
    link.textContent = 'Lihat file baru: ' + file.name;
    preview.appendChild(link);
});
</script>
@endpush
@endonce
