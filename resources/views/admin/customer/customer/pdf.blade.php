<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Customer - {{ $customer->kode_customer }}</title>
    <style>
        @page { margin: 28px 36px 38px; }
        body { font-family: DejaVu Sans, sans-serif; color: #25374c; font-size: 10px; line-height: 1.5; }
        .header { border-bottom: 3px solid #284c73; padding-bottom: 14px; margin-bottom: 18px; }
        .eyebrow { font-size: 9px; letter-spacing: 2px; color: #738396; }
        h1 { font-size: 22px; margin: 4px 0; }
        .meta { color: #728093; font-size: 9px; }
        .summary { background: #edf3f8; padding: 12px 15px; margin-bottom: 16px; }
        .summary strong { font-size: 14px; }
        h2 { font-size: 11px; color: #284c73; padding: 7px 0; margin: 13px 0 4px; border-bottom: 1px solid #dce5ef; page-break-after: avoid; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        tr { page-break-inside: avoid; }
        td { padding: 4px 0; vertical-align: top; overflow-wrap: break-word; }
        td.label { width: 31%; color: #738093; }
        td.separator { width: 3%; }
        .total td { border-top: 1px solid #dce5ef; padding-top: 8px; font-weight: bold; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 8px; color: #8b98a6; border-top: 1px solid #e4eaf0; padding-top: 6px; }
    </style>
</head>
<body>
    @php
        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y') : '-';
        $money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $sections = [
            'Identitas Customer' => [
                'Kode Customer' => $customer->kode_customer,
                'Tanggal Verifikasi' => $date($customer->tanggal_verif),
                'Nama Lengkap' => $customer->nama_lengkap,
                'NIK' => $customer->nik,
                'Jenis Kelamin' => $customer->jenis_kelamin,
                'Tempat / Tanggal Lahir' => ($customer->tempat_lahir ?: '-') . ' / ' . $date($customer->tgl_lahir),
                'Nomor Telepon / WA' => $customer->no_telp,
                'Email' => $customer->email,
                'Alamat KTP' => $customer->alamat_ktp,
                'Alamat Domisili' => $customer->alamat_domisili,
                'Pekerjaan' => $customer->pekerjaan,
                'NPWP' => $customer->npwp,
                'No. BPJS Kesehatan' => $customer->no_bpjs_kes,
            ],
            'Keluarga & Kontak Saudara' => [
                'Status Pernikahan' => $customer->status_pernikahan,
                'Nama Pasangan' => $customer->nama_p,
                'NIK Pasangan' => $customer->nik_p,
                'Nama Saudara' => $customer->nama_saudara,
                'Telepon Saudara' => $customer->no_telp_saudara,
            ],
            'Unit & Pembelian' => [
                'Perumahan' => $customer->lokasi?->nama_kavling,
                'Blok / Kavling' => $customer->kavling?->kode_kavling,
                'Tipe Bangunan' => $customer->kavling?->tipe_bangunan,
                'Luas Tanah' => isset($customer->kavling->luas_tanah) ? $customer->kavling->luas_tanah . ' m²' : '-',
                'Luas Bangunan' => isset($customer->kavling->luas_bangunan) ? $customer->kavling->luas_bangunan . ' m²' : '-',
                'Jenis Perumahan' => $customer->jenis_perumahan,
                'Jenis Pembelian' => $customer->jenis_pembelian,
                'Progres Terakhir' => $customer->progres?->status_progres,
                'Marketing' => $customer->marketing?->nama_marketing ?? 'Non Marketing',
                'Total Harga' => $money($customer->total_harga),
            ],
        ];
    @endphp
    <div class="header">
        <div class="eyebrow">INFORMASI CUSTOMER</div>
        <h1>Data Customer</h1>
        <div class="meta">{{ $customer->lokasi?->nama_kavling ?? 'Data Perumahan' }} &nbsp; | &nbsp; {{ $customer->kode_customer ?? '-' }}</div>
    </div>
    <div class="summary"><strong>{{ $customer->nama_lengkap }}</strong><br>Unit {{ $customer->kavling?->kode_kavling ?? '-' }} &nbsp; / &nbsp; {{ $customer->jenis_pembelian ?? '-' }}</div>
    @foreach ($sections as $title => $rows)
        <h2>{{ $title }}</h2>
        <table>
            @foreach ($rows as $label => $value)
                <tr @class(['total' => $label === 'Total Harga'])>
                    <td class="label">{{ $label }}</td><td class="separator">:</td><td>{{ filled($value) ? $value : '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach
    <div class="footer">{{ $customer->kode_customer ?? '-' }} &nbsp; · &nbsp; Dicetak {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
</body>
</html>
