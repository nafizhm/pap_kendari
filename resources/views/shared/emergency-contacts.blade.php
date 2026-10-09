@for ($i = 1; $i <= 5; $i++)
    <div class="form-group row">
        <div class="col-sm-12"><strong>Kontak Darurat {{ $i }}</strong></div>
        @foreach (['kontak_darurat' => 'Kontak Darurat (No. Telepon)', 'nama_pemilik_kontak_darurat' => 'Nama Pemilik Kontak Darurat', 'keterangan_kontak_darurat' => 'Keterangan'] as $key => $label)
            @php
                $field = $key . '_' . $i;
                $value = isset($data) ? $data->{$field} : null;
                if ($i === 1 && $value === null && isset($data)) {
                    $value = $key === 'kontak_darurat' ? $data->no_telp_saudara : ($key === 'nama_pemilik_kontak_darurat' ? $data->nama_saudara : null);
                }
            @endphp
            <div class="col-sm-4">
                <label for="{{ $field }}">{{ $label }}</label>
                <input type="{{ $key === 'kontak_darurat' ? 'tel' : 'text' }}" name="{{ $field }}" id="{{ $field }}"
                    value="{{ old($field, $value) }}" maxlength="255" class="form-control" {{ !empty($readonly) ? 'readonly' : '' }}>
            </div>
        @endforeach
    </div>
@endfor
