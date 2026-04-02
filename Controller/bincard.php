<?php
require_once '../class/Connection.php';

$db   = new Connection();
$conn = $db->connect();

if (!isset($_GET['material_id'])) die("Material ID not provided.");
$material_id = (int) $_GET['material_id'];

$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$material) die("Material not found.");

// Get current balance
$balStmt = $conn->prepare("SELECT Balance FROM bincard WHERE material_id = ? ORDER BY Date DESC, id DESC LIMIT 1");
$balStmt->execute([$material_id]);
$currentBalance = $balStmt->fetchColumn();
if ($currentBalance === false) $currentBalance = (int) $material['quantity'];

// Handle new entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recVal    = trim($_POST['receipts']);
    $issueVal  = (int)$_POST['issues'];
    // If RR (Receiving Report) — uppercase and ADD to balance
    if (stripos($recVal, 'RR') !== false) {
        $recVal     = strtoupper($recVal);
        $newBalance = $currentBalance + $issueVal;
    } else {
        $rNum       = is_numeric($recVal) ? (int)$recVal : 0;
        $newBalance = $currentBalance + $rNum - $issueVal;
    }
    $insert = $conn->prepare("INSERT INTO bincard (material_id, Date, Reference, Receipts, Issues, Balance) VALUES (?,?,?,?,?,?)");
    $insert->execute([$material_id, $_POST['date'], $_POST['reference'], $recVal, $issueVal, $newBalance]);
    // Sync quantity in materials table to match new bin card balance
    $sync = $conn->prepare("UPDATE materials SET quantity = ? WHERE id = ?");
    $sync->execute([$newBalance, $material_id]);
    header("Location: bincard.php?material_id=" . $material_id);
    exit;
}

// Fetch all records
$stmt = $conn->prepare("SELECT * FROM bincard WHERE material_id = ? ORDER BY Date ASC, id ASC");
$stmt->execute([$material_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Category accent color
$catColors = ['Line Materials'=>'#3399ff','Special Equipment'=>'#d97706','Housewiring Materials'=>'#e55353'];
$accentColor = $catColors[$material['category']] ?? '#3399ff';
$backPage = 'materials.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bin Card — <?= htmlspecialchars($material['description']) ?></title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/bincard.css">
<style>
    /* Category accent color (dynamic PHP variable) */
    .balance-box { background: linear-gradient(135deg, <?= $accentColor ?>, <?= $accentColor ?>cc); }
    .cat-badge   { background: <?= $accentColor ?>; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-book" style="color:<?= $accentColor ?>;margin-right:8px;"></i>Bin Card</h1>
        <p>Transaction ledger for this material</p>
    </div>

    <div class="bincard-grid">

        <!-- LEFT: Info + Form -->
        <div>
            <!-- Balance -->
            <div class="balance-box">
                <div class="bal-icon"><i class="fas fa-cubes"></i></div>
                <div>
                    <div class="bal-label">Current Balance</div>
                    <div class="bal-value"><?= $currentBalance ?></div>
                </div>
            </div>

            <!-- Material Info -->
            <div class="card-box" style="margin-bottom:18px;">
                <div class="mat-info">
                    <h3><?= htmlspecialchars($material['description']) ?> <span class="cat-badge"><?= htmlspecialchars($material['category']) ?></span></h3>
                    <span>Code: <?= htmlspecialchars($material['material_code']) ?></span>
                </div>
                <a href="view_material.php?id=<?= $material_id ?>" class="btn btn-dark" style="font-size:12px;padding:6px 14px;">
                    <i class="fas fa-eye"></i> View Material
                </a>
            </div>

            <!-- Add Entry Form -->
            <div class="card-box">
                <div style="font-size:14px;font-weight:700;color:#2d3142;margin-bottom:16px;">
                    <i class="fas fa-plus-circle" style="color:<?= $accentColor ?>;margin-right:6px;"></i>Add Entry
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Reference No.</label>
                        <input type="text" name="reference" id="refInput" required value="<?= date('Y') ?>-" placeholder="e.g. 2026-080124">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-arrow-down" style="color:#2eb85c;"></i> Receipts</label>
                        <input type="text" name="receipts" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-arrow-up" style="color:#e55353;"></i> Issues</label>
                        <input type="number" name="issues" value="0" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-save"></i> Add Record
                    </button>
                </form>
            </div>

            <div style="margin-top:14px;">
                <a href="<?= $backPage ?>" class="btn btn-dark" style="font-size:13px;">
                    <i class="fas fa-arrow-left"></i> Back to Materials
                </a>
            </div>
        </div>

        <!-- RIGHT: Transaction Table + Pagination wrapper -->
        <div>
        <div class="card-box">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
                <div style="font-size:14px;font-weight:700;color:#2d3142;white-space:nowrap;">
                    <i class="fas fa-list" style="color:<?= $accentColor ?>;margin-right:6px;"></i>Transaction History
                </div>
                <div style="position:relative;flex:1;min-width:180px;max-width:280px;">
                    <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aab;font-size:12px;pointer-events:none;"></i>
                    <input type="text" id="txSearch" placeholder="Search date, ref, receipts…"
                        style="width:100%;padding:7px 10px 7px 30px;border:1px solid #dde;border-radius:8px;font-size:12px;color:#2d3142;background:#f8f9fc;outline:none;">
                </div>
            </div>
            <div class="table-wrap">
                <table id="txTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Receipts</th>
                            <th>Issued</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($records): ?>
                        <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Date']) ?></td>
                            <td><?= htmlspecialchars($row['Reference']) ?></td>
                            <td><?php
                                $rec  = $row['Receipts'];
                                $isRR = stripos((string)$rec, 'RR') !== false;
                                if ($isRR) echo '<span style="color:#e55353;font-weight:700;text-transform:uppercase;">'.htmlspecialchars(strtoupper($rec)).'</span>';
                                elseif (is_numeric($rec) && $rec > 0) echo '<span style="color:#2eb85c;font-weight:600;">+'.htmlspecialchars($rec).'</span>';
                                elseif (!is_numeric($rec) && $rec !== '' && $rec !== null) echo '<span style="color:#2eb85c;font-weight:600;">'.htmlspecialchars($rec).'</span>';
                                else echo '<span style="color:#ccc;">0</span>';
                            ?></td>
                            <td><?php
                                $rec  = $row['Receipts'];
                                $isRR = stripos((string)$rec, 'RR') !== false;
                                $uomLabel = htmlspecialchars($material['uom'] ?? 'pcs');
                                if ($row['Issues'] > 0)
                                    echo $isRR
                                        ? '<span style="color:#2d3142;font-weight:600;">+'.$row['Issues'].'<span style="font-size:10px;font-weight:600;margin-left:2px;opacity:0.7;">'.$uomLabel.'</span></span>'
                                        : '<span style="color:#e55353;font-weight:600;">'.$row['Issues'].'<span style="font-size:10px;font-weight:600;margin-left:2px;opacity:0.7;">'.$uomLabel.'</span></span>';
                                else echo '<span style="color:#ccc;">0</span>';
                            ?></td>
                            <td><strong class="<?= $row['Balance'] < 10 ? 'balance-low' : 'balance-positive' ?>"><?= $row['Balance'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="color:#768192;padding:24px;">No bin card records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination — below the card box, inside right column wrapper -->
        <div id="txPagination" style="display:flex;justify-content:flex-end;align-items:center;gap:6px;margin-top:10px;flex-wrap:wrap;"></div>
        </div><!-- end right wrapper -->

    </div>
</div>

<link rel="stylesheet" href="../assets/css/pagination.css">
<style>
/* Category accent color for pagination buttons (dynamic PHP variable) */
.pg-btn:hover  { background:#<?= ltrim($accentColor,'#') ?>22; border-color:<?= $accentColor ?>; color:<?= $accentColor ?>; }
.pg-btn.active { background:<?= $accentColor ?>; color:#fff; border-color:<?= $accentColor ?>; }
</style>

<script src="../assets/js/pagination.js"></script>
<script>
(function () {
    const ref    = document.getElementById('refInput');
    const prefix = '<?= date('Y') ?>-';

    ref.addEventListener('focus', function () {
        const len = this.value.length;
        this.setSelectionRange(len, len);
    });

    ref.addEventListener('keydown', function (e) {
        const pos = this.selectionStart;
        if ((e.key === 'Backspace' && pos <= prefix.length) ||
            (e.key === 'Delete'    && pos < prefix.length)) {
            e.preventDefault();
        }
    });

    ref.addEventListener('input', function () {
        if (!this.value.startsWith(prefix)) {
            this.value = prefix + this.value.replace(/^\d{4}-?/, '');
        }
    });
})();

initPagination('txTable', 'txSearch', 'txPagination');
</script>
</body>
</html>
