(() => {
    'use strict';

    const root = document.getElementById('siteplan-export');
    const status = document.getElementById('export-status');
    const retry = document.getElementById('export-retry');
    const download = document.getElementById('export-download');
    const preview = document.getElementById('export-preview');
    const format = root.dataset.format || 'JPG';
    let jpgUrl;
    let pdfUrl;

    async function exportJpg() {
        retry.hidden = true;
        download.hidden = true;
        preview.hidden = true;
        status.textContent = `Menyiapkan denah ${format}...`;
        let svgUrl;

        try {
            const source = JSON.parse(document.getElementById('siteplan-svg').textContent);
            const doc = new DOMParser().parseFromString(source, 'image/svg+xml');
            const svg = doc.documentElement;
            if (doc.querySelector('parsererror') || svg.localName !== 'svg') {
                throw new Error('Template denah SVG tidak valid.');
            }

            // Use the original viewBox, independent of zoom/pan on the siteplan page.
            const box = svg.viewBox.baseVal;
            const sourceWidth = box.width || svg.width.baseVal.value;
            const sourceHeight = box.height || svg.height.baseVal.value;
            if (!(sourceWidth > 0 && sourceHeight > 0) || !Number.isFinite(sourceWidth + sourceHeight)) {
                throw new Error('Ukuran template denah tidak valid.');
            }
            // Limit memory usage on mobile browsers while keeping labels readable.
            const scale = 3200 / Math.max(sourceWidth, sourceHeight);
            const width = Math.max(1, Math.round(sourceWidth * scale));
            const height = Math.max(1, Math.round(sourceHeight * scale));
            if (!box.width || !box.height) {
                svg.setAttribute('viewBox', `0 0 ${sourceWidth} ${sourceHeight}`);
            }
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);
            svg.style.width = `${width}px`;
            svg.style.height = `${height}px`;
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

            svgUrl = URL.createObjectURL(new Blob([
                new XMLSerializer().serializeToString(svg),
            ], { type: 'image/svg+xml;charset=utf-8' }));
            const image = new Image();
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('Denah tidak dapat dirender menjadi gambar.'));
                image.src = svgUrl;
            });

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Browser tidak mendukung pembuatan gambar.');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, width, height);
            context.drawImage(image, 0, 0, width, height);
            const pdfImage = format === 'PDF' ? canvas.toDataURL('image/jpeg', 0.95) : null;
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.95));
            canvas.width = canvas.height = 1;
            if (!blob || blob.type !== 'image/jpeg') {
                throw new Error('Browser gagal membuat file JPG.');
            }

            if (jpgUrl) URL.revokeObjectURL(jpgUrl);
            jpgUrl = URL.createObjectURL(blob);
            download.href = jpgUrl;
            if (format === 'PDF') {
                if (!window.pdfMake) throw new Error('Pustaka PDF tidak dapat dimuat.');
                const metadata = JSON.parse(document.getElementById('siteplan-pdf-metadata').textContent);
                const definition = {
                    pageSize: 'A4',
                    pageMargins: [28, 28, 28, 28],
                    info: { title: `Site Plan - ${metadata.location}`, author: metadata.company },
                    content: [
                        { text: metadata.company.toUpperCase(), bold: true, fontSize: 14, alignment: 'center' },
                        { text: `SITE PLAN ${metadata.location.toUpperCase()}`, fontSize: 12, alignment: 'center', margin: [0, 6, 0, 0] },
                        { text: `Periode Cetak : ${metadata.date}`, fontSize: 10, alignment: 'center', margin: [0, 6, 0, 8] },
                        { canvas: [
                            { type: 'line', x1: 0, y1: 0, x2: 539, y2: 0, lineWidth: 2 },
                            { type: 'line', x1: 0, y1: 3, x2: 539, y2: 3, lineWidth: 0.8 },
                        ], margin: [0, 0, 0, 20] },
                        { image: pdfImage, fit: [454, 670], alignment: 'center' },
                    ],
                };
                const pdfBlob = await new Promise(resolve => window.pdfMake.createPdf(definition).getBlob(resolve));
                if (pdfUrl) URL.revokeObjectURL(pdfUrl);
                pdfUrl = URL.createObjectURL(pdfBlob);
                download.href = pdfUrl;
            }
            download.download = root.dataset.filename;
            download.hidden = false;
            preview.src = jpgUrl;
            preview.hidden = false;
            status.textContent = `${format} siap. Jika unduhan tidak dimulai otomatis, klik Download ${format}.`;
            download.click();
        } catch (error) {
            status.textContent = `Gagal membuat ${format}: ${error.message} Silakan coba lagi.`;
            retry.hidden = false;
        } finally {
            if (svgUrl) URL.revokeObjectURL(svgUrl);
        }
    }

    retry.addEventListener('click', exportJpg);
    exportJpg();
})();
