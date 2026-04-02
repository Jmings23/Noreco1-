/* ── Bin Card Importer — OCR logic ── */
let uploadedFile = null;

// ─── Drag & Drop ──────────────────────────────────────────
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) handleFile(file);
});
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) handleFile(fileInput.files[0]);
});

function handleFile(file) {
    uploadedFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('preview-name').textContent = file.name;
        document.getElementById('preview-wrap').style.display = 'block';
        document.getElementById('ocr-section').style.display = 'block';
        setStep(2);
    };
    reader.readAsDataURL(file);
}

// ─── OCR ──────────────────────────────────────────────────
async function runOCR() {
    if (!uploadedFile) return;
    const btn = document.getElementById('ocr-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running OCR...';
    document.getElementById('ocr-progress').style.display = 'block';
    setStep(2);

    try {
        const result = await Tesseract.recognize(uploadedFile, 'eng', {
            logger: m => {
                if (m.status === 'recognizing text') {
                    const pct = Math.round(m.progress * 100);
                    document.getElementById('progress-fill').style.width = pct + '%';
                    document.getElementById('progress-label').textContent = 'Reading text... ' + pct + '%';
                } else {
                    document.getElementById('progress-label').textContent = m.status;
                }
            }
        });

        const raw = result.data.text;
        document.getElementById('raw-text').textContent = raw;
        document.getElementById('raw-text-wrap').style.display = 'block';
        document.getElementById('progress-label').textContent = 'Done!';
        document.getElementById('progress-fill').style.width = '100%';

        const rows = parseOCRText(raw);
        populateReviewTable(rows);
        document.getElementById('review-section').style.display = 'block';
        document.getElementById('review-section').scrollIntoView({ behavior: 'smooth' });
        setStep(3);

    } catch (err) {
        document.getElementById('progress-label').textContent = 'OCR failed. Please try again.';
        console.error(err);
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-redo"></i> Re-run OCR';
}

// ─── Parse OCR text into rows ─────────────────────────────
function parseOCRText(text) {
    const lines  = text.split('\n').map(l => l.trim()).filter(l => l.length > 3);
    const rows   = [];
    const dateRx = /(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2})/;
    let currentName = '';

    for (const line of lines) {
        const nameLineMatch = line.match(/name[:\s]+([A-Za-z0-9][^\t\n]{1,60}?)(?:\s{2,}|$)/i);
        if (nameLineMatch) {
            currentName = nameLineMatch[1].trim();
            continue;
        }

        if (/^(date|reference|receipt|issue|balance|description|code)\b/i.test(line)) continue;

        const dateMatch = line.match(dateRx);
        if (!dateMatch) continue;

        const date   = normalizeDate(dateMatch[1]);
        const rest   = line.replace(dateMatch[0], '').trim();
        const tokens = rest.split(/\s+/).filter(t => t.length > 0);

        let ref = '', refIdx = -1;
        for (let i = 0; i < tokens.length; i++) {
            if (/^[A-Z0-9][A-Z0-9\-#\/]{2,}$/i.test(tokens[i])) {
                ref = tokens[i]; refIdx = i; break;
            }
        }

        const afterRef = refIdx >= 0 ? tokens.slice(refIdx + 1) : tokens;

        let receipts = '', issues = 0, lastNumPos = -1;
        for (let i = afterRef.length - 1; i >= 0; i--) {
            if (/^\d+$/.test(afterRef[i])) { lastNumPos = i; break; }
        }
        if (lastNumPos >= 0) {
            issues   = parseInt(afterRef[lastNumPos]);
            receipts = afterRef.slice(0, lastNumPos).join(' ').trim();
        } else {
            receipts = afterRef.join(' ').trim();
        }

        rows.push({ name: currentName, date, reference: ref, receipts, issues });
    }

    if (rows.length === 0) rows.push({ name: '', date: '', reference: '', receipts: '', issues: 0 });
    return rows;
}

function normalizeDate(str) {
    try {
        const parts = str.split(/[\/\-\.]/);
        if (parts[0].length === 4) return `${parts[0]}-${parts[1].padStart(2,'0')}-${parts[2].padStart(2,'0')}`;
        if (parts[2].length === 4) return `${parts[2]}-${parts[0].padStart(2,'0')}-${parts[1].padStart(2,'0')}`;
        if (parts[2].length === 2) return `20${parts[2]}-${parts[1].padStart(2,'0')}-${parts[0].padStart(2,'0')}`;
    } catch(e) {}
    return str;
}

// ─── Review table ─────────────────────────────────────────
function populateReviewTable(rows) {
    const tbody = document.getElementById('review-tbody');
    tbody.innerHTML = '';
    rows.forEach(r => addRow(r));
}

let rowIndex = 0;
function addRow(data) {
    data = data || {};
    const tbody = document.getElementById('review-tbody');
    rowIndex++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="row_name[]" value="${escHtml(data.name || '')}" placeholder="Material name" list="mat-list" style="min-width:140px;"></td>
        <td><input type="date" name="row_date[]" value="${data.date || ''}" placeholder="YYYY-MM-DD"></td>
        <td><input type="text" name="row_reference[]" value="${escHtml(data.reference || '')}" placeholder="e.g. 2025-001"></td>
        <td><input type="text" name="row_receipts[]" value="${escHtml(String(data.receipts != null ? data.receipts : ''))}" placeholder="qty or location"></td>
        <td><input type="number" name="row_issues[]" value="${data.issues != null ? data.issues : 0}" min="0"></td>
        <td><button type="button" class="del-btn" onclick="this.closest('tr').remove()" title="Remove row"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
    setStep(3);
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── Step bar ─────────────────────────────────────────────
function setStep(n) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('step' + i);
        el.className = 'step' + (i < n ? ' done' : i === n ? ' active' : '');
    }
}

function resetAll() {
    uploadedFile = null;
    document.getElementById('file-input').value = '';
    document.getElementById('preview-wrap').style.display = 'none';
    document.getElementById('ocr-section').style.display = 'none';
    document.getElementById('review-section').style.display = 'none';
    document.getElementById('raw-text-wrap').style.display = 'none';
    document.getElementById('ocr-progress').style.display = 'none';
    document.getElementById('progress-fill').style.width = '0%';
    document.getElementById('review-tbody').innerHTML = '';
    document.getElementById('ocr-btn').disabled = false;
    document.getElementById('ocr-btn').innerHTML = '<i class="fas fa-magic"></i> Extract Data with OCR';
    rowIndex = 0;
    setStep(1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Mark step 4 on form submit
document.querySelector('form') && document.querySelector('form').addEventListener('submit', () => setStep(4));
