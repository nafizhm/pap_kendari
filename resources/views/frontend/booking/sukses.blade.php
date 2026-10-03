@extends('layouts.app')

@php
    $konfigurasi = \App\Models\PengaturanProfil::first();
    $company = $konfigurasi->nama_perusahaan ?? 'Hunian Anda';
    $logo = \App\Models\PengaturanMedia::where('jenis_data', 'Logo Login')->first();
    $logoPath = $logo && $logo->nama_file && is_file(public_path('config_media/' . $logo->nama_file))
        ? asset('config_media/' . $logo->nama_file) : asset('default/logo.png');
    $preview = request()->boolean('preview');
    $hasBooking = session()->has('nama');
    $nama = $preview ? 'Andi Pratama' : session('nama', '—');
    $lokasi = $preview ? 'Perumahan Contoh' : session('lokasi', '—');
    $blok = $preview ? 'A-12' : session('blok', '—');
@endphp

@section('title', 'Booking Berhasil | ' . $company)

@push('styles')
<style>
    .booking-success { --green: #183f35; --muted: #717b73; min-height: 100vh; min-height: 100svh; background: #f5f4ee; color: #263d33; font-family: 'Segoe UI', sans-serif; padding: 30px 28px 22px; position: relative; overflow: hidden; }
    .booking-success * { box-sizing: border-box; }
    .booking-success::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 330px; background: var(--green); }
    .bs-shell { max-width: 1000px; margin: auto; position: relative; }
    .bs-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 30px; color: #fff; }
    .bs-brand { display: flex; align-items: center; gap: 14px; }
    .bs-logo { width: 76px; height: 66px; padding: 8px; object-fit: contain; background: #fff; border-radius: 12px; }
    .bs-brand strong { display: block; font-size: 17px; font-weight: 600; }
    .bs-brand small { display: block; margin-top: 5px; color: #c0cfc6; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; }
    .bs-header-note { color: #d2ddd6; font-size: 12px; letter-spacing: .5px; }
    .bs-preview { padding: 11px 18px; margin-bottom: 18px; background: #fff4d9; border: 1px solid #e6d4a6; border-radius: 10px; font-size: 13px; color: #725b2b; }
    .bs-card { background: #fffefa; border: 1px solid #e5e8df; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 65px #16372d12; }
    .bs-intro { text-align: center; padding: 38px 24px 32px; }
    .bs-check { display: grid; place-items: center; width: 66px; height: 66px; margin: 0 auto 22px; border: 1px solid #cbdcd0; border-radius: 50%; background: #edf4ed; color: #2a6650; box-shadow: 0 0 0 8px #f5f8f0; }
    .bs-check svg { width: 30px; height: 30px; }
    .bs-eyebrow { font-size: 10px; letter-spacing: 2.8px; font-weight: 700; color: #8c784a; text-transform: uppercase; margin-bottom: 12px; }
    .bs-intro h1 { font-family: Georgia, 'Times New Roman', serif; font-weight: 400; font-size: clamp(30px, 4vw, 43px); letter-spacing: -1px; margin: 0 0 14px; color: var(--green); }
    .bs-intro p { color: var(--muted); font-size: 14px; line-height: 1.8; margin: 0 auto; max-width: 470px; }
    .bs-content { display: grid; grid-template-columns: 1.08fr 1fr; border-top: 1px solid #e7e9df; }
    .bs-summary { padding: 30px 36px; background: #f7f8f2; border-right: 1px solid #e7e9df; }
    .bs-section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 24px; }
    .booking-success h2 { font-size: 15px; font-weight: 600; margin: 0; }
    .bs-status { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e4d9bb; padding: 5px 9px; border-radius: 30px; background: #faf3e2; color: #80652c; font-size: 10px; white-space: nowrap; }
    .bs-status::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: #aa8840; }
    .bs-details { margin: 0; }
    .bs-details > div { padding: 0 0 18px; margin-bottom: 18px; border-bottom: 1px solid #e3e7dc; }
    .bs-details > div:last-child { margin: 0; padding: 0; border: 0; }
    .bs-details dt { color: var(--muted); font-size: 11px; font-weight: 400; margin-bottom: 6px; }
    .bs-details dd { font-size: 15px; font-weight: 600; margin: 0; overflow-wrap: anywhere; }
    .bs-unit { display: inline-block; background: #e8eee2; border: 1px solid #dbe3d4; padding: 7px 16px; border-radius: 7px; letter-spacing: 1px; }
    .bs-next { padding: 30px 36px; }
    .bs-steps { list-style: none; padding: 0; margin: 24px 0 0; }
    .bs-steps li { display: flex; align-items: flex-start; gap: 13px; position: relative; padding-bottom: 25px; }
    .bs-steps li:not(:last-child)::after { content: ''; position: absolute; left: 13px; top: 30px; bottom: 4px; width: 1px; background: #dce3d7; }
    .bs-steps li:last-child { padding-bottom: 0; }
    .bs-step-number { flex: 0 0 28px; height: 28px; display: grid; place-items: center; border: 1px solid #dbe2d6; border-radius: 50%; font-size: 11px; color: #72806c; }
    .bs-step-number.done { background: var(--green); border-color: var(--green); color: #fff; }
    .bs-steps strong { display: block; font-size: 12px; font-weight: 600; margin: 3px 0 5px; }
    .bs-steps p { font-size: 12px; line-height: 1.7; color: var(--muted); margin: 0; }
    .bs-footer { padding: 24px 36px; display: flex; align-items: center; justify-content: space-between; gap: 24px; border-top: 1px solid #e7e9df; }
    .bs-footer p { font-size: 11px; color: var(--muted); margin: 0; line-height: 1.8; max-width: 390px; }
    .bs-button { display: inline-flex; align-items: center; justify-content: center; gap: 18px; padding: 13px 21px; border-radius: 8px; background: var(--green); color: #fff; font-size: 12px; font-weight: 600; text-decoration: none; transition: background .2s; white-space: nowrap; }
    .bs-button:hover { background: #2b5846; color: #fff; text-decoration: none; }
    .bs-button:focus-visible { outline: 3px solid #b39b61; outline-offset: 4px; }
    .bs-bottom { text-align: center; font-size: 11px; color: #7a837a; margin: 22px 0 0; }
    @media (max-width: 680px) {
        .booking-success { padding: 20px 16px; }
        .bs-header { margin-bottom: 24px; }
        .bs-header-note { display: none; }
        .bs-brand strong { font-size: 14px; }
        .bs-logo { width: 64px; height: 58px; }
        .bs-card { border-radius: 18px; }
        .bs-intro { padding: 32px 20px 26px; }
        .bs-content { grid-template-columns: 1fr; }
        .bs-summary { border-right: 0; border-bottom: 1px solid #e7e9df; }
        .bs-summary, .bs-next { padding: 25px 24px; }
        .bs-footer { padding: 22px 24px; flex-direction: column; align-items: stretch; gap: 18px; }
        .bs-footer p { text-align: center; max-width: none; }
    }
</style>
@endpush

@section('content')
<main class="booking-success">
    <div class="bs-shell">
        <header class="bs-header">
            <div class="bs-brand">
                <img class="bs-logo" src="{{ $logoPath }}" alt="Logo {{ $company }}">
                <div><strong>{{ $company }}</strong><small>A place to call home</small></div>
            </div>
            <span class="bs-header-note">LAYANAN BOOKING HUNIAN</span>
        </header>
        @if ($preview)
            <div class="bs-preview" role="note"><strong>Pratinjau desain</strong> · Data di bawah adalah contoh. Tidak ada booking yang dibuat.</div>
        @endif
        <article class="bs-card" aria-labelledby="booking-title">
            <div class="bs-intro">
                <div class="bs-check" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none"><path d="m8 16 5.5 5.5L25 10" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="bs-eyebrow">Satu langkah menuju hunian impian</div>
                <h1 id="booking-title">{{ $preview || $hasBooking ? 'Booking berhasil dikirim.' : 'Informasi booking' }}</h1>
                <p>{{ $preview || $hasBooking ? 'Terima kasih atas kepercayaan Anda. Data booking Anda telah kami terima dan menunggu verifikasi tim kami.' : 'Belum ada detail booking pada sesi ini. Silakan isi formulir booking untuk memulai pemesanan hunian Anda.' }}</p>
            </div>
            @if ($preview || $hasBooking)
            <div class="bs-content">
                <section class="bs-summary" aria-labelledby="summary-title">
                    <div class="bs-section-head"><h2 id="summary-title">Ringkasan booking</h2><span class="bs-status">Menunggu verifikasi</span></div>
                    <dl class="bs-details">
                        <div><dt>Nama pemesan</dt><dd>{{ $nama }}</dd></div>
                        <div><dt>Lokasi perumahan</dt><dd>{{ $lokasi }}</dd></div>
                        <div><dt>Blok / unit rumah</dt><dd><span class="bs-unit">{{ $blok }}</span></dd></div>
                    </dl>
                </section>
                <section class="bs-next" aria-labelledby="next-title">
                    <h2 id="next-title">Apa langkah selanjutnya?</h2>
                    <ol class="bs-steps">
                        <li><span class="bs-step-number done" aria-hidden="true">✓</span><div><strong>Booking diterima</strong><p>Data pemesanan Anda berhasil tersimpan.</p></div></li>
                        <li><span class="bs-step-number" aria-hidden="true">02</span><div><strong>Verifikasi oleh tim kami</strong><p>Tim kami akan meninjau data dan kelengkapan booking Anda.</p></div></li>
                        <li><span class="bs-step-number" aria-hidden="true">03</span><div><strong>Konfirmasi lebih lanjut</strong><p>Pastikan nomor telepon Anda aktif agar mudah dihubungi oleh tim kami.</p></div></li>
                    </ol>
                </section>
            </div>
            @endif
            <footer class="bs-footer">
                <p>Booking akan diproses setelah verifikasi. Silakan hubungi marketing Anda jika memerlukan bantuan.</p>
                <a class="bs-button" href="{{ route('booking') }}"><span aria-hidden="true">←</span> Kembali ke halaman booking</a>
            </footer>
        </article>
        <p class="bs-bottom">{{ $company }} · Menemani langkah Anda menuju rumah baru.</p>
    </div>
</main>
@endsection

@push('scripts')
<script>
    try { sessionStorage.removeItem('success'); } catch (error) { /* Storage may be disabled by the browser. */ }
</script>
@endpush
