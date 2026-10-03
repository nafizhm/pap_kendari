<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#183b46">
    <title>404 — Halaman tidak ditemukan</title>
    <style>
        :root { color-scheme: light; font-family: Inter, "Segoe UI", Arial, sans-serif; color: #193842; background: #f5f6f2; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; min-height: 100svh; display: flex; flex-direction: column; }
        a { color: inherit; }
        .page-header { width: min(1180px, 100%); padding: 30px 36px; margin: 0 auto; }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; font-size: 13px; font-weight: 700; letter-spacing: .2px; }
        .brand-icon { width: 38px; height: 38px; padding: 9px; border: 1px solid #d6dfda; border-radius: 12px; }
        main { width: min(1108px, calc(100% - 72px)); margin: auto; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 60px; padding: 35px 0 65px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; padding: 9px 13px; background: #e6eee8; border-radius: 30px; color: #466954; }
        .eyebrow::before { content: ""; width: 6px; height: 6px; background: #729779; border-radius: 50%; }
        h1 { font-size: clamp(34px, 4.5vw, 56px); line-height: 1.13; letter-spacing: -2px; font-weight: 650; margin: 24px 0 20px; }
        .description { max-width: 400px; color: #718086; font-size: 16px; line-height: 1.85; margin: 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 13px 20px; border: 1px solid #d4deda; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; transition: background .15s, transform .15s; }
        .button svg { width: 17px; height: 17px; }
        .button-primary { background: #193e47; color: #fff; border-color: #193e47; box-shadow: 0 5px 12px #193e4712; }
        .button-primary:hover { background: #285560; transform: translateY(-1px); }
        .button-secondary:hover { background: #e9eeea; }
        a:focus-visible { outline: 3px solid #ba884b; outline-offset: 5px; }
        .hint { color: #85918f; font-size: 12px; margin: 24px 0 0; line-height: 1.7; }
        .art { position: relative; min-height: 480px; border-radius: 28px; background: #e7eee7; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .art::before { content: ""; position: absolute; width: 360px; height: 360px; border: 1px solid #cfdbd0; border-radius: 50%; top: 48px; left: calc(50% - 180px); }
        .art::after { content: ""; position: absolute; width: 280px; height: 280px; border: 1px solid #d2ddd3; border-radius: 50%; top: 88px; left: calc(50% - 140px); }
        .error-code { position: relative; z-index: 1; font-size: clamp(110px, 15vw, 176px); line-height: 1; font-weight: 750; letter-spacing: -12px; color: #254c46; margin-top: 20px; }
        .illustration { position: relative; z-index: 1; width: 85%; max-width: 380px; margin-top: -24px; }
        .art-caption { z-index: 1; color: #738775; font-size: 10px; letter-spacing: 2.5px; text-transform: uppercase; margin: 12px 0 26px; }
        footer { padding: 20px 24px 28px; text-align: center; font-size: 12px; color: #939c98; }
        @media (max-width: 800px) {
            .page-header { padding: 24px; }
            main { width: calc(100% - 48px); gap: 28px; grid-template-columns: 1fr; max-width: 520px; padding: 16px 0 32px; }
            .art { order: -1; min-height: 280px; }
            .error-code { font-size: 110px; letter-spacing: -7px; margin-top: 18px; }
            .illustration { width: 240px; margin-top: -20px; }
            .art::before { top: -30px; }
            .art::after { top: 10px; }
            .art-caption { margin: 0 0 18px; font-size: 9px; }
            h1 { letter-spacing: -1px; margin-top: 18px; }
            .description { font-size: 14px; }
            .actions { margin-top: 24px; }
        }
        @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
    </style>
</head>
<body>
    <header class="page-header">
        <a class="brand" href="{{ url('/') }}" aria-label="Ke halaman utama">
            <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m3 11 9-8 9 8M5 9v12h14V9M9 21v-8h6v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Portal Perumahan
        </a>
    </header>
    <main>
        <section aria-labelledby="error-title">
            <div class="eyebrow">Error 404</div>
            <h1 id="error-title">Sepertinya Anda<br>salah alamat.</h1>
            <p class="description">Halaman yang Anda cari tidak ditemukan. Alamatnya mungkin berubah, atau halaman tersebut sudah tidak tersedia.</p>
            <nav class="actions" aria-label="Navigasi halaman tidak ditemukan">
                <a class="button button-primary" href="{{ url('/') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-8 9 8M5 9v12h14V9M9 21v-8h6v8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Ke Beranda
                </a>
                <a class="button button-secondary" href="{{ url('/') }}" id="back-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m10 5-7 7 7 7M3 12h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Kembali
                </a>
            </nav>
            <p class="hint">Periksa kembali alamat URL, atau lanjutkan dari beranda.</p>
        </section>
        <div class="art" aria-hidden="true">
            <div class="error-code">404</div>
            <svg class="illustration" viewBox="0 0 400 220" fill="none">
                <ellipse cx="202" cy="186" rx="159" ry="15" fill="#d0dbcf"/>
                <path d="M197 181c-37 4-50 13-45 27h84c-12-10-12-17-7-27" fill="#f5f4ea"/>
                <path d="M109 99 201 35l92 64" stroke="#264f46" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M122 97 201 43l79 54v83H122V97Z" fill="#fafbf4"/>
                <path d="M201 43 122 97v83h79V43Z" fill="#f0f2e8"/>
                <path d="M108 100 201 35l92 65" stroke="#264f46" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M239 59V35h23v41" fill="#264f46"/>
                <rect x="177" y="116" width="47" height="64" rx="4" fill="#a7bca6"/>
                <circle cx="214" cy="149" r="2.5" fill="#38594c"/>
                <rect x="137" y="114" width="25" height="31" rx="3" fill="#d2e1dd"/>
                <path d="M149.5 114v31M137 129.5h25" stroke="#fafbf4" stroke-width="3"/>
                <rect x="239" y="114" width="25" height="31" rx="3" fill="#d2e1dd"/>
                <path d="M251.5 114v31M239 129.5h25" stroke="#fafbf4" stroke-width="3"/>
                <path d="M68 183v-47M327 183v-49" stroke="#65856d" stroke-width="5" stroke-linecap="round"/>
                <ellipse cx="68" cy="123" rx="24" ry="37" fill="#9eb79a"/>
                <ellipse cx="327" cy="122" rx="21" ry="32" fill="#a9c0a2"/>
                <path d="M304 184h40M48 184h41" stroke="#8ca487" stroke-width="3" stroke-linecap="round"/>
                <rect x="283" y="145" width="5" height="40" rx="2" fill="#65856d"/>
                <path d="M267 142h44l10 11-10 11h-44v-22Z" fill="#c6a570"/>
                <path d="M279 153h25m-5-5 5 5-5 5" stroke="#fffaf0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m79 66 5-9 5 9m-5-9v18M305 55h10m-5-5v10" stroke="#afc2aa" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div class="art-caption">Mari kembali ke alamat yang tepat</div>
        </div>
    </main>
    <footer>Halaman tidak ditemukan &nbsp;·&nbsp; 404</footer>
    <script>
        // Sediakan tautan kembali hanya jika asalnya masih di aplikasi yang sama.
        try {
            const previous = document.referrer ? new URL(document.referrer) : null;
            if (previous && previous.origin === window.location.origin && previous.href !== window.location.href) {
                document.getElementById('back-link').href = previous.href;
            }
        } catch (_) {}
    </script>
</body>
</html>
