@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3 bg-indigo text-white">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Verifikasi Data Booking</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="formData" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tanggal</label>
                                            <div class="col-sm-8">
                                                <input type="date" name="tgl_booking" id="tgl_booking" value="{{ $data->tgl_booking }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nama Lengkap</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ $data->nama_lengkap }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">NIK</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="nik" id="nik" value="{{ $data->nik }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Jenis
                                                Kelamin</label>
                                            <div class="col-sm-8">
                                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-control"><option value=""></option><option value="Laki-laki" {{ $data->jenis_kelamin == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option><option value="Perempuan" {{ $data->jenis_kelamin == 'Perempuan' ? 'selected' : '' }}>Perempuan</option></select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tempat Lahir</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="tempat_lahir" id="tempat_lahir" value="{{ $data->tempat_lahir }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Tanggal
                                                Lahir</label>
                                            <div class="col-sm-8">
                                                <input type="date" name="tgl_lahir" id="tgl_lahir" value="{{ $data->tgl_lahir }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Alamat KTP</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="alamat_ktp" id="alamat_ktp" value="{{ $data->alamat_ktp }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">NPWP</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="npwp" id="npwp" value="{{ $data->npwp }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="no_bpjs_kes" class="col-sm-4 col-form-label">No. BPJS Kes</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="no_bpjs_kes" id="no_bpjs_kes" value="{{ $data->no_bpjs_kes }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Email</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="email" id="email" value="{{ $data->email }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nomor
                                                Telepon</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="no_telp" id="no_telp" value="{{ $data->no_telp }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Nama
                                                Saudara</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="nama_saudara" id="nama_saudara" value="{{ $data->nama_saudara }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">No. Telp
                                                Saudara</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="no_telp_saudara" id="no_telp_saudara" value="{{ $data->no_telp_saudara }}" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Nama Marketing</label>
                                            <div class="col-sm-8">
                                                <select name="id_marketing" id="id_marketing" class="form-control"><option value=""></option><option value="0" {{ (string) $data->id_marketing === "0" ? "selected" : "" }}>Non Marketing</option>
@foreach ($marketingList as $option)
<option value="{{ $option->id }}" {{ $data->id_marketing == $option->id ? 'selected' : '' }}>{{ $option->nama_marketing }}</option>
@endforeach
</select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Lokasi Perumahan</label>
                                            <div class="col-sm-8">
                                                <select name="id_lokasi" id="id_lokasi" class="form-control"><option value=""></option>
@foreach ($lokasiList as $option)
<option value="{{ $option->id }}" {{ $data->id_lokasi == $option->id ? 'selected' : '' }}>{{ $option->nama_kavling }}</option>
@endforeach
</select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label">Blok Kavling</label>
                                            <div class="col-sm-8">
                                                <select name="id_kavling" id="id_kavling" class="form-control"><option value=""></option>
@foreach ($kavlingList as $option)
<option value="{{ $option->id }}" {{ $data->id_kavling == $option->id ? 'selected' : '' }}>{{ $option->kode_kavling }}</option>
@endforeach
</select>
                                            </div>
                                        </div>

                                        <div id="rincian-biaya">
                                        @foreach ($data->rincian_biaya as $item)
                                            @if (($item['nilai'] ?? 0) > 0)
                                                <div class="form-group row">
                                                    <label class="col-sm-4 col-form-label">{{ $item['nama'] ?? '-' }}</label>
                                                    <div class="col-sm-8">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">Rp.</span>
                                                            </div>
                                                            <input type="text"
                                                                value="{{ number_format($item['nilai'], 0, ',', '.') }}"
                                                                class="form-control" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-4 col-form-label"><strong>Total Harga</strong></label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp.</span>
                                                    </div>
                                                    <input type="text"
                                                        id="total_harga" value="{{ number_format($data->total_harga, 0, ',', '.') }}"
                                                        class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="alamat_domisili" class="col-sm-4 col-form-label">Alamat Domisili</label>
                                            <div class="col-sm-8"><input type="text" name="alamat_domisili" id="alamat_domisili" value="{{ $data->alamat_domisili }}" class="form-control"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="pekerjaan" class="col-sm-4 col-form-label">Pekerjaan</label>
                                            <div class="col-sm-8"><input type="text" name="pekerjaan" id="pekerjaan" value="{{ $data->pekerjaan }}" class="form-control"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="status_pernikahan" class="col-sm-4 col-form-label">Status Pernikahan</label>
                                            <div class="col-sm-8"><input type="text" name="status_pernikahan" id="status_pernikahan" value="{{ $data->status_pernikahan }}" class="form-control"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="nama_p" class="col-sm-4 col-form-label">Nama Pasangan</label>
                                            <div class="col-sm-8"><input type="text" name="nama_p" id="nama_p" value="{{ $data->nama_p }}" class="form-control"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="nik_p" class="col-sm-4 col-form-label">NIK Pasangan</label>
                                            <div class="col-sm-8"><input type="text" name="nik_p" id="nik_p" value="{{ $data->nik_p }}" class="form-control"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Jenis
                                                Perumahan</label>
                                            <div class="col-sm-8">
                                                <select name="jenis_perumahan" id="jenis_perumahan" class="form-control"><option value=""></option><option value="Subsidi" {{ $data->jenis_perumahan == 'Subsidi' ? 'selected' : '' }}>Subsidi</option><option value="Komersil" {{ $data->jenis_perumahan == 'Komersil' ? 'selected' : '' }}>Komersil</option></select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tgl_registrasi" class="col-sm-4 col-form-label">Booking
                                                Fee</label>
                                            <div class="col-sm-8">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp.</span>
                                                    </div>
                                                    <input type="text" name="booking_fee" id="booking_fee"
                                                        value="{{ number_format((float) $data->booking_fee, 0, ',', '.') }}"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KTP</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_ktp)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_ktp) }}" data-pdf="{{ strtolower(pathinfo($data->foto_ktp, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_ktp, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_ktp) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_ktp" id="foto_ktp" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto NPWP</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_npwp)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_npwp) }}" data-pdf="{{ strtolower(pathinfo($data->foto_npwp, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_npwp, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_npwp) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_npwp" id="foto_npwp" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KK</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_kk)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_kk) }}" data-pdf="{{ strtolower(pathinfo($data->foto_kk, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_kk, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_kk) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_kk" id="foto_kk" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto BPJS</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_bpjs)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_bpjs) }}" data-pdf="{{ strtolower(pathinfo($data->foto_bpjs, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_bpjs, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_bpjs) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_bpjs" id="foto_bpjs" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto KTP Pasangan</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_ktp_p)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_ktp_p) }}" data-pdf="{{ strtolower(pathinfo($data->foto_ktp_p, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_ktp_p, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_ktp_p) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_ktp_p" id="foto_ktp_p" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label">Bukti Transfer</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->file_bukti)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->file_bukti) }}" data-pdf="{{ strtolower(pathinfo($data->file_bukti, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->file_bukti, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->file_bukti) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="file_bukti" id="file_bukti" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <label class="form-label">Foto Pemohon</label>
                                                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                                    style="max-width: 350px; height: 180px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                                    @if ($data->foto_pemohon)
                                                        <button type="button" class="btn p-0 attachment-preview" data-url="{{ asset('assets/booking/' . $data->foto_pemohon) }}" data-pdf="{{ strtolower(pathinfo($data->foto_pemohon, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}" aria-label="Perbesar lampiran" style="width:100%;height:100%;">
                                                            @if (strtolower(pathinfo($data->foto_pemohon, PATHINFO_EXTENSION)) === 'pdf')
                                                                <span class="text-primary">Lihat PDF</span>
                                                            @else
                                                                <img src="{{ asset('assets/booking/' . $data->foto_pemohon) }}" alt="Lampiran" style="max-width:100%;max-height:100%;">
                                                            @endif
                                                        </button>
                                                    @else
                                                        <span class="text-muted">Tidak ada file</span>
                                                    @endif
                                                </div>
                                                <input type="file" name="foto_pemohon" id="foto_pemohon" class="form-control-file mt-2 attachment-input" accept=".jpg,.jpeg,.png,.webp">
                                                <small class="text-muted">Pilih file untuk mengganti lampiran (maks. 10 MB).</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>


                                    @csrf
                                    <input type="hidden" id="primary_id" name="primary_id"
                                        value="{{ $data->id }}">

                                    <div class="form-group row">
                                        <label for="stt_reg" class="col-sm-2 col-form-label">Status Verifikasi</label>
                                        <div class="col-sm-3">
                                            <select name="stt_reg" id="stt_reg" class="form-control select-status">
                                                <option value=""></option>
                                                <option value="1" {{ $data->stt_reg == 1 ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="2" {{ $data->stt_reg == 2 ? 'selected' : '' }}>
                                                    Disetujui
                                                </option>
                                                <option value="3" {{ $data->stt_reg == 3 ? 'selected' : '' }}>Ditolak
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Metode Bayar</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-metode-bayar" name="id_metode_bayar"
                                                id="id_metode_bayar">
                                                <option value=""></option>
                                                @foreach ($metodeBayarList as $item)
                                                    <option value="{{ $item->id }}" @selected(old('id_metode_bayar', $data->id_metode_bayar) == $item->id)>{{ $item->jenis_bayar }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="id_bank" class="col-sm-2 col-form-label">Rekening Pembayaran</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-bank" name="id_bank" id="id_bank">
                                                <option value=""></option>
                                                @foreach ($bankList as $item)
                                                    <option value="{{ $item->id }}" @selected(old('id_bank', $data->id_bank) == $item->id)>{{ $item->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="jenis_pembelian" class="col-sm-2 col-form-label">Jenis
                                            Pembelian</label>
                                        <div class="col-sm-3">
                                            <select name="jenis_pembelian" id="jenis_pembelian"
                                                class="form-control select-pembelian">
                                                <option value=""></option>
                                                <option value="Pembelian Cash"
                                                    {{ $data->jenis_pembelian == 'Pembelian Cash' ? 'selected' : '' }}>
                                                    Pembelian Cash</option>
                                                <option value="Cash Bertahap"
                                                    {{ $data->jenis_pembelian == 'Cash Bertahap' ? 'selected' : '' }}>Cash
                                                    Bertahap</option>
                                                <option value="KPR"
                                                    {{ $data->jenis_pembelian == 'KPR' ? 'selected' : '' }}>KPR</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- CASH ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Atas Nama Surat</label>
                                            <div class="col-sm-3">
                                                <input name="an_surat_cash" id="an_surat_cash" class="form-control"
                                                    type="text"
                                                    value="{{ $data->an_surat_cash ?? $data->nama_lengkap }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CASH BERTAHAP ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash_bertahap" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Termin (x)</label>
                                            <div class="col-sm-2">
                                                <div class="input-group">
                                                    <input name="termin_x_cash_b" id="termin_x_cash_b"
                                                        class="form-control format-number"
                                                        value="{{ isset($data->termin_x_cash_b) && $data->termin_x_cash_b != 0 ? number_format($data->termin_x_cash_b, 0, ',', '.') : 60 }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">Bulan</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer text-center">
                                        <a href="{{ route('pengajuan-hold.index') }}" class="btn btn-danger">Kembali</a>

                                        <button type="submit" class="btn btn-primary ms-1" id="submitBtn"
                                            @if (isset($data) && $data->stt_reg == 2) readonly @endif>
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                aria-hidden="true"></span>
                                            <span class="button-text">Simpan</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="attachmentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="attachmentModalTitle">Lampiran Booking</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body text-center" style="overflow:auto;max-height:80vh;">
                <div id="attachmentZoomControls" class="mb-2"><button type="button" class="btn btn-sm btn-secondary" id="zoomOut">−</button> <button type="button" class="btn btn-sm btn-secondary" id="zoomReset">100%</button> <button type="button" class="btn btn-sm btn-secondary" id="zoomIn">+</button></div>
                <img id="attachmentImage" alt="Lampiran booking" style="max-width:100%;height:auto;">
                <iframe id="attachmentPdf" title="Lampiran PDF" style="width:100%;height:65vh;border:0;" class="d-none"></iframe>
            </div>
        </div></div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {
            const kavlings = @json($kavlingList);
            const originalKavling = @json((string) $data->id_kavling);
            function fillKavlings(selected) {
                const select = $('#id_kavling').empty().append(new Option('', ''));
                kavlings.filter(item => String(item.id_lokasi) === $('#id_lokasi').val()).forEach(item => {
                    select.append(new Option(item.kode_kavling, item.id, false, String(item.id) === selected));
                });
            }
            fillKavlings(originalKavling);
            $('#id_lokasi').on('change', function() {
                fillKavlings('');
                $('#id_kavling').trigger('change');
            });
            $('#id_kavling').on('change', function() {
                const item = kavlings.find(item => String(item.id) === $(this).val());
                const rincian = item ? (item.rincian_biaya || []) : [];
                const container = $('#rincian-biaya').empty();
                let total = 0;
                rincian.forEach(cost => {
                    total += Number(cost.nilai || 0);
                    if (Number(cost.nilai) <= 0) return;
                    const row = $('<div class="form-group row">');
                    $('<label class="col-sm-4 col-form-label">').text(cost.nama || '-').appendTo(row);
                    const input = $('<div class="col-sm-8"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rp.</span></div></div></div>');
                    $('<input type="text" class="form-control" readonly>').val(Number(cost.nilai).toLocaleString('id-ID')).appendTo(input.find('.input-group'));
                    row.append(input).appendTo(container);
                });
                $('#total_harga').val(total.toLocaleString('id-ID'));
            });
            $('#booking_fee').on('input', function() {
                const value = this.value.replace(/\D/g, '');
                this.value = value ? Number(value).toLocaleString('id-ID') : '';
            });

            let zoom = 1;
            function setZoom(value) {
                zoom = Math.max(0.25, Math.min(4, value));
                $('#attachmentImage').css({width: (zoom * 100) + '%', maxWidth: 'none'});
                $('#zoomReset').text(Math.round(zoom * 100) + '%');
            }
            $(document).on('click', '.attachment-preview', function() {
                const isPdf = $(this).attr('data-pdf') === '1';
                const url = $(this).attr('data-url');
                $('#attachmentImage').toggleClass('d-none', isPdf).attr('src', isPdf ? '' : url);
                $('#attachmentPdf').toggleClass('d-none', !isPdf).attr('src', isPdf ? url : '');
                $('#attachmentZoomControls').toggle(!isPdf);
                setZoom(1);
                $('#attachmentModal').modal('show');
            });
            $('#zoomIn').on('click', () => setZoom(zoom + 0.25));
            $('#zoomOut').on('click', () => setZoom(zoom - 0.25));
            $('#zoomReset').on('click', () => setZoom(1));
            $('#attachmentModal').on('hidden.bs.modal', function() {
                $('#attachmentImage, #attachmentPdf').removeAttr('src');
            });
            $('.attachment-input').each(function() {
                $(this).data('original-preview', $(this).siblings('.img-thumbnail').html());
            }).on('change', function() {
                const input = $(this);
                const thumbnail = input.siblings('.img-thumbnail');
                const previousUrl = input.data('preview-url');
                if (previousUrl) URL.revokeObjectURL(previousUrl);
                const file = this.files[0];
                if (!file) {
                    thumbnail.html(input.data('original-preview'));
                    return;
                }
                const isPdf = file.type === 'application/pdf';
                if (file.size > 10 * 1024 * 1024 || !(['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || (this.id === 'file_bukti' && isPdf))) {
                    toastr.error('Pilih gambar JPG/PNG/WebP atau bukti PDF, maksimal 10 MB.');
                    this.value = '';
                    thumbnail.html(input.data('original-preview'));
                    return;
                }
                const url = URL.createObjectURL(file);
                input.data('preview-url', url);
                const button = $('<button type="button" class="btn p-0 attachment-preview" aria-label="Perbesar lampiran" style="width:100%;height:100%;">').attr({'data-url': url, 'data-pdf': isPdf ? '1' : '0'});
                if (isPdf) button.text('Lihat PDF');
                else button.append($('<img alt="Lampiran baru" style="max-width:100%;max-height:100%;">').attr('src', url));
                thumbnail.empty().append(button);
            });
        });
        $(document).ready(function() {
            $('.select-status').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });

            $('.select-jenis').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });

            $('.select-bank').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Bank',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-metode-bayar').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Metode Bayar',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-pembelian').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });
        });

        $(document).ready(function() {
            function hideAllTransactionForms() {
                $('#trx_cash').hide();
                $('#trx_cash_bertahap').hide();
                $('.hr-transaksi').hide();
            }

            $('#jenis_pembelian').on('change', function() {
                let selected = $(this).val();
                hideAllTransactionForms();

                switch (selected) {
                    case 'Pembelian Cash':
                        $('#trx_cash').prev('.hr-transaksi').show();
                        $('#trx_cash').show();
                        break;
                    case 'Cash Bertahap':
                        $('#trx_cash_bertahap').prev('.hr-transaksi').show();
                        $('#trx_cash_bertahap').show();
                        break;
                }
            });

            $('#jenis_pembelian').trigger('change');
        });


        var audio = new Audio('{{ asset('audio/notification.ogg') }}');

        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = '{{ $data->id }}';
            let url = '{{ route('pengajuan-hold.verifikasi.simpan', ['id' => ':id']) }}'.replace(':id', id);
            let method = 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    sessionStorage.setItem('success', response.message || 'Data booking berhasil disimpan.');
                    window.location.href = "{{ route('pengajuan-hold.index') }}";
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        audio.play();
                        let errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
                        let firstError = Object.values(errors).flat()[0] || 'Data yang dikirim tidak valid.';

                        toastr.error(firstError, "GAGAL!", {
                            progressBar: true,
                            timeOut: 6000,
                            positionClass: "toast-bottom-right",
                        });

                        $.each(errors, function(key, val) {
                            let input = $('#' + key);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });
                    } else {
                        audio.play();
                        let response = xhr.responseJSON || {};
                        let message = response.message || response.error;

                        if (!message && xhr.status === 419) {
                            message = 'Sesi Anda telah berakhir. Muat ulang halaman lalu coba simpan kembali.';
                        }

                        toastr.error(message || 'Terjadi kesalahan pada server. Silakan coba lagi.', "GAGAL!", {
                            progressBar: true,
                            timeOut: 8000,
                            positionClass: "toast-bottom-right",
                        });
                    }
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                }
            });
        });
    </script>
@endpush
