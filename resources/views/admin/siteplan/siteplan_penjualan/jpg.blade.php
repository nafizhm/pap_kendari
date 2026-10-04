@php($format = $format ?? 'JPG')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Download Denah {{ $format }}</title>
    <style>
        body { margin: 24px; font: 16px/1.5 Arial, sans-serif; color: #233044; background: #f4f6f9; }
        main { max-width: 1000px; margin: auto; }
        button, a { display: inline-block; padding: 10px 16px; border: 0; border-radius: 5px; background: #1769aa; color: white; font: inherit; cursor: pointer; text-decoration: none; }
        button:disabled { opacity: .6; cursor: wait; }
        [hidden] { display: none !important; }
        img { display: block; max-width: 100%; height: auto; margin-top: 20px; background: white; }
    </style>
</head>
<body>
<main id="siteplan-export" data-filename="{{ $filename }}" data-format="{{ $format }}">
    <h1>Download Denah {{ $format }}</h1>
    <p id="export-status" role="status" aria-live="polite">Menyiapkan denah {{ $format }}...</p>
    <button id="export-retry" type="button" hidden>Coba lagi</button>
    <a id="export-download" hidden>Download {{ $format }}</a>
    <img id="export-preview" alt="Pratinjau denah siteplan penjualan" hidden>
    <noscript>Aktifkan JavaScript di browser untuk mengunduh denah {{ $format }}.</noscript>
    <script id="siteplan-svg" type="application/json">{!! json_encode($svgContent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
    @if($format === 'PDF')
        <script id="siteplan-pdf-metadata" type="application/json">{!! json_encode($pdfMetadata, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
    @endif
</main>
@if($format === 'PDF')
<script src="{{ asset('assets/plugins/pdfmake/pdfmake.min.js') }}" defer></script>
<script src="{{ asset('assets/plugins/pdfmake/vfs_fonts.js') }}" defer></script>
@endif
<script src="{{ asset('js/siteplan-jpg.js') }}" defer></script>
</body>
</html>
