(() => {
    'use strict';
    const config = window.templateStudio;
    const byId = id => document.getElementById(id);
    const rows = [...document.querySelectorAll('.studio-variable')];
    const checks = rows.map(row => row.querySelector('input'));
    let report = config.inspection, busy = false, invalid = !!config.error, sequence = 0;
    const selected = () => checks.filter(input => input.checked).map(input => input.value);
    const token = key => '${' + key + '}';
    const notice = (message, type = '') => {
        byId('inspection-status').textContent = message;
        byId('inspection-status').className = 'studio-notice ' + type;
    };
    function filter() {
        const search = byId('cari-variabel').value.toLowerCase().trim();
        const category = byId('group-filter').value, mode = byId('view-filter').value;
        let count = 0;
        rows.forEach(row => {
            const used = !!report?.known.includes(row.dataset.key);
            const chosen = row.querySelector('input').checked;
            row.hidden = !(row.textContent + row.dataset.key).toLowerCase().includes(search)
                || (category && category !== row.dataset.group)
                || (mode === 'selected' && !chosen) || (mode === 'used' && !used);
            row.classList.toggle('is-selected', chosen);
            row.querySelector('.studio-used').hidden = !used;
            if (!row.hidden) count++;
        });
        byId('no-variables').hidden = count > 0;
    }
    function refresh() {
        const choices = selected();
        byId('selected-count').textContent = choices.length;
        byId('copy-selected').disabled = !choices.length;
        byId('select-detected').disabled = !report || busy;
        const missing = choices.filter(key => !report?.known.includes(key));
        byId('selection-diff').textContent = report && missing.length
            ? missing.length + ' variabel pilihan belum ada di Word: ' + missing.map(token).join(', ') + '. Tempelkan kode tersebut lalu unggah ulang jika diperlukan.' : '';
        const blocked = busy || invalid || !report || (byId('is_active').value === '1' && report.unknown.length > 0);
        byId('save-template').disabled = blocked;
        byId('save-hint').textContent = busy ? 'Sedang memeriksa Word…' : invalid ? 'Pilih ulang dokumen Word yang valid.' : !report ? 'Unggah Word untuk melanjutkan.' : report.unknown.length ? 'Perbaiki variabel atau pilih status Nonaktif untuk menyimpan.' : 'Dokumen siap disimpan.';
        filter();
    }
    function render() {
        byId('detected-list').replaceChildren();
        byId('known-count').textContent = report ? report.known.length : '—';
        byId('unknown-count').textContent = report ? report.unknown.length : '—';
        byId('occurrence-count').textContent = report ? Object.values(report.counts).reduce((sum, count) => sum + count, 0) : '—';
        if (report) {
            notice(report.unknown.length ? 'Ada kode yang belum dikenal. Perbaiki di Word lalu unggah ulang.' : report.variables.length ? 'Pemeriksaan selesai. Semua variabel dikenali.' : 'Tidak ditemukan variabel. Dokumen dapat disimpan sebagai template berisi teks tetap.', report.unknown.length || !report.variables.length ? 'warning' : 'success');
            report.variables.forEach(key => {
                const row = document.createElement('div'), code = document.createElement('code'), count = document.createElement('span');
                code.textContent = token(key); count.textContent = report.counts[key] + '×';
                row.append(code, count);
                if (report.unknown.includes(key)) {
                    const hint = document.createElement('small');
                    hint.textContent = report.suggestions[key] ? 'Tidak dikenal. Mungkin maksud Anda ' + token(report.suggestions[key]) + '?' : 'Tidak dikenal. Gunakan kode dari pustaka variabel.';
                    row.append(hint);
                }
                byId('detected-list').append(row);
            });
        }
        refresh();
    }
    async function inspectFile() {
        const current = ++sequence, file = byId('file_template').files[0];
        report = null; invalid = false; busy = false; render();
        if (!file) {
            report = config.inspection; invalid = !!config.error; render();
            notice(config.error || (report ? 'Menggunakan dokumen yang sudah tersimpan.' : 'Pilih dokumen Word untuk diperiksa.'), invalid ? 'error' : '');
            return;
        }
        byId('file-name').textContent = file.name + ' • ' + (file.size / 1024 / 1024).toFixed(2) + ' MB';
        if (!/\.docx$/i.test(file.name) || file.size > 5 * 1024 * 1024) {
            invalid = true; notice('Gunakan file .docx dengan ukuran maksimal 5 MB.', 'error'); refresh(); return;
        }
        busy = true; notice('Membaca variabel dari dokumen Word…'); refresh();
        const form = new FormData(); form.append('file_template', file);
        try {
            const response = await fetch(config.inspectUrl, {method: 'POST', body: form, headers: {'X-CSRF-TOKEN': document.querySelector('#template-form input[name="_token"]').value, 'Accept': 'application/json'}});
            const data = await response.json();
            if (current !== sequence) return;
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : 'Pemeriksaan gagal. Muat ulang halaman atau pilih ulang file.');
            report = data; invalid = false;
        } catch (error) {
            if (current !== sequence) return;
            invalid = true;
            notice(error instanceof SyntaxError ? 'Sesi mungkin berakhir. Muat ulang halaman lalu coba lagi.' : error.message, 'error');
        } finally {
            if (current === sequence) { busy = false; render(); }
        }
    }
    async function copy(text) {
        let success = false;
        try { if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(text); success = true; } } catch (_) {}
        if (!success) {
            const previous = document.activeElement, area = document.createElement('textarea');
            area.value = text; area.style.position = 'fixed'; area.style.opacity = '0'; document.body.append(area); area.select();
            try { success = document.execCommand('copy'); } catch (_) {} finally { area.remove(); previous?.focus(); }
        }
        byId('copy-status').textContent = success ? 'Kode berhasil disalin.' : 'Salin manual dengan memilih kode lalu Ctrl+C.';
        byId('copy-status').className = 'small ' + (success ? 'text-success' : 'text-danger');
    }
    checks.forEach(input => input.addEventListener('change', refresh));
    ['cari-variabel', 'group-filter', 'view-filter'].forEach(id => byId(id).addEventListener('input', filter));
    document.querySelectorAll('.studio-copy').forEach(button => button.addEventListener('click', () => copy(button.dataset.code)));
    byId('copy-selected').addEventListener('click', () => copy(selected().map(token).join('\n')));
    byId('select-detected').addEventListener('click', () => { checks.forEach(input => input.checked = report?.known.includes(input.value)); refresh(); });
    byId('clear-selected').addEventListener('click', () => { checks.forEach(input => input.checked = false); refresh(); });
    byId('is_active').addEventListener('change', refresh);
    byId('file_template').addEventListener('change', inspectFile);
    const zone = byId('drop-zone');
    zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('dragging'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragging'));
    zone.addEventListener('drop', event => {
        event.preventDefault(); zone.classList.remove('dragging');
        if (event.dataTransfer.files.length !== 1) { notice('Letakkan satu dokumen Word saja.', 'warning'); return; }
        byId('file_template').files = event.dataTransfer.files; inspectFile();
    });
    let codeEdited = !!byId('kode').value;
    byId('kode').addEventListener('input', () => codeEdited = true);
    byId('nama').addEventListener('input', function () { if (!codeEdited && !config.existing) byId('kode').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 50); });
    byId('template-form').addEventListener('submit', event => {
        if (byId('save-template').disabled) { event.preventDefault(); return; }
        byId('save-template').disabled = true; byId('save-template').textContent = 'Menyimpan…';
    });
    render();
    if (config.error) notice(config.error, 'error');
})();
