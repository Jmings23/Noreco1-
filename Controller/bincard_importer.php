<?php

// ================= LOGIN PROTECTION =================
require_once '../class/Admin.php';

$user = new Admin ();
if (!$user->isLoggedIn()) {
    header("Location: homepage.php");
    exit();
}

require_once '../class/Connection.php';
$db   = new Connection();
$conn = $db->connect();

// Fetch all materials for the dropdown
$matStmt = $conn->query("SELECT id, material_code, description, category FROM materials ORDER BY category, description ASC");
$allMaterials = $matStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle save of reviewed rows
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rows'])) {
    $dates    = $_POST['row_date']      ?? [];
    $refs     = $_POST['row_reference'] ?? [];
    $receipts = $_POST['row_receipts']  ?? [];
    $issues   = $_POST['row_issues']    ?? [];
    $names    = $_POST['row_name']      ?? [];

    $insert   = $conn->prepare("INSERT INTO bincard (material_id, Date, Reference, Receipts, Issues, Balance) VALUES (?,?,?,?,?,?)");
    $syncQ    = $conn->prepare("UPDATE materials SET quantity = ? WHERE id = ?");
    $balCache = [];
    $saved    = 0;
    $savedMaterialId = null;

    for ($i = 0; $i < count($dates); $i++) {
        if (empty($dates[$i])) continue;
        $nameVal = trim($names[$i] ?? '');
        if (empty($nameVal)) continue;

        // Match material by description (partial) or material_code
        $matFind = $conn->prepare("SELECT id FROM materials WHERE description LIKE ? OR material_code = ? LIMIT 1");
        $matFind->execute(['%' . $nameVal . '%', $nameVal]);
        $mat_id = $matFind->fetchColumn();
        if (!$mat_id) continue;

        // Cache balance per material
        if (!isset($balCache[$mat_id])) {
            $balStmt = $conn->prepare("SELECT Balance FROM bincard WHERE material_id = ? ORDER BY id DESC LIMIT 1");
            $balStmt->execute([$mat_id]);
            $bal = $balStmt->fetchColumn();
            if ($bal === false) {
                $matQ = $conn->prepare("SELECT quantity FROM materials WHERE id = ?");
                $matQ->execute([$mat_id]);
                $bal = (int) $matQ->fetchColumn();
            }
            $balCache[$mat_id] = $bal;
        }

        $recVal = trim($receipts[$i]);
        $s      = (int) $issues[$i];
        if (stripos($recVal, 'RR') !== false) {
            $recVal = strtoupper($recVal);
            $balCache[$mat_id] += $s;
        } else {
            $r = is_numeric($recVal) ? (int) $recVal : 0;
            $balCache[$mat_id] = $balCache[$mat_id] + $r - $s;
        }

        $insert->execute([$mat_id, $dates[$i], $refs[$i], $recVal, $s, $balCache[$mat_id]]);
        $syncQ->execute([$balCache[$mat_id], $mat_id]);
        if ($savedMaterialId === null) $savedMaterialId = $mat_id; // capture first only
        $saved++;
    }
    $successMsg = "$saved record(s) saved to bin card successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bin Card Importer — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/bincard-importer.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-file-import" style="color:#3399ff;margin-right:8px;"></i>Bin Card Importer</h1>
        <p>Upload a photo of a bin card — OCR will extract the data for you to review and save</p>
    </div>

    <?php if (!empty($successMsg)): ?>
    <div class="alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
        <a href="bincard.php?material_id=<?= $savedMaterialId ?>" style="margin-left:12px;">View Bin Card &rarr;</a>
    </div>
    <?php endif; ?>

    <!-- Step indicator -->
    <div class="steps" id="step-bar">
        <div class="step active" id="step1">1. Upload Image</div>
        <div class="step" id="step2">2. Run OCR</div>
        <div class="step" id="step3">3. Review &amp; Edit</div>
        <div class="step" id="step4">4. Save</div>
    </div>

    <div class="card-box">

        <!-- Drop Zone -->
        <div class="drop-zone" id="drop-zone">
            <input type="file" id="file-input" accept="image/*">
            <div class="dz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="dz-title">Drag &amp; Drop your bin card photo here</div>
            <div class="dz-sub">Supports JPG, PNG, BMP, TIFF</div>
            <label for="file-input" class="dz-btn"><i class="fas fa-folder-open"></i> Browse File</label>
        </div>

        <!-- Image Preview -->
        <div id="preview-wrap">
            <img id="preview-img" src="" alt="Bin Card Preview">
            <div class="preview-name" id="preview-name"></div>
        </div>

        <!-- OCR Trigger -->
        <div id="ocr-section">
            <button class="btn btn-primary ocr-btn" id="ocr-btn" onclick="runOCR()">
                <i class="fas fa-magic"></i> Extract Data with OCR
            </button>
            <div id="ocr-progress">
                <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progress-fill"></div></div>
                <div class="progress-label" id="progress-label">Initializing...</div>
            </div>
            <div id="raw-text-wrap">
                <details>
                    <summary>View raw OCR text</summary>
                    <pre id="raw-text"></pre>
                </details>
            </div>
        </div>
    </div>

    <!-- Review Section -->
    <div id="review-section" class="card-box">
        <div class="review-header">
            <h3><i class="fas fa-table" style="color:#3399ff;margin-right:6px;"></i>Review Extracted Data</h3>
            <div style="font-size:12px;color:#768192;"><i class="fas fa-info-circle"></i> Edit any field before saving. Empty rows are skipped.</div>
        </div>

        <!-- Datalist for material name autocomplete -->
        <datalist id="mat-list">
            <?php foreach ($allMaterials as $mat): ?>
            <option value="<?= htmlspecialchars($mat['description']) ?>">
            <?php endforeach; ?>
        </datalist>

        <form method="POST">
            <!-- Editable review table -->
            <div style="overflow-x:auto;">
                <table class="review-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Reference No.</th>
                            <th>Receipts</th>
                            <th>Issues</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="review-tbody">
                        <!-- Rows injected by JS -->
                    </tbody>
                </table>
            </div>

            <button type="button" class="add-row-btn" onclick="addRow()">
                <i class="fas fa-plus"></i> Add Row Manually
            </button>

            <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" name="save_rows" class="btn btn-primary" style="padding:10px 28px;font-size:14px;">
                    <i class="fas fa-save"></i> Save to Bin Card
                </button>
                <button type="button" class="btn btn-dark" onclick="resetAll()" style="padding:10px 20px;font-size:14px;">
                    <i class="fas fa-redo"></i> Start Over
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tesseract.js (free, runs in browser) -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="../assets/js/bincard-importer.js"></script>
<link rel="stylesheet" href="../assets/css/nav-animation.css">
<script src="../assets/js/nav-animation.js"></script>
</body>
</html>
