<?php

// ================= LOGIN PROTECTION =================
require_once '../class/Admin.php';

$user = new Admin ();
if (!$user->isLoggedIn()) {
    header("Location: homepage.php");
    exit();
}

require_once '../class/Connection.php';

$db = new Connection();
$conn = $db->connect();

// Drop legacy material_id column if it still exists
try {
    $conn->exec("ALTER TABLE inventory_reports DROP COLUMN material_id");
} catch (PDOException $e) { /* already removed */ }

// ===== SELECTED MONTH =====
$selectedMonth = $_GET['month'] ?? date('Y-m');
$prevMonth     = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));

// Get all materials that had at least one transaction in the selected month.
$stmt = $conn->prepare("
    SELECT
        m.material_code,
        m.description,
        m.category,
        COALESCE(
            (SELECT b.Balance FROM bincard b
             WHERE b.material_id = m.id
               AND DATE_FORMAT(b.Date,'%Y-%m') = ?
             ORDER BY b.Date DESC, b.id DESC
             LIMIT 1),
            m.starting_balance
        ) AS starting_balance,
        m.uom,
        (SELECT b.Balance FROM bincard b
         WHERE b.material_id = m.id
           AND DATE_FORMAT(b.Date,'%Y-%m') = ?
         ORDER BY b.Date DESC, b.id DESC
         LIMIT 1) AS remaining_stock,
        (SELECT
            COALESCE(SUM(CASE WHEN b2.Receipts REGEXP '^[0-9]+$' AND CAST(b2.Receipts AS UNSIGNED) > 0
                              THEN CAST(b2.Receipts AS UNSIGNED) ELSE 0 END), 0) +
            COALESCE(SUM(CASE WHEN b2.Receipts LIKE '%RR%'
                              THEN b2.Issues ELSE 0 END), 0)
         FROM bincard b2
         WHERE b2.material_id = m.id
           AND DATE_FORMAT(b2.Date,'%Y-%m') = ?) AS total_stock_in,
        (SELECT COALESCE(SUM(CASE WHEN b3.Receipts NOT LIKE '%RR%'
                                  THEN b3.Issues ELSE 0 END), 0)
         FROM bincard b3
         WHERE b3.material_id = m.id
           AND DATE_FORMAT(b3.Date,'%Y-%m') = ?) AS total_stock_out
    FROM materials m
    WHERE EXISTS (
        SELECT 1 FROM bincard b
        WHERE b.material_id = m.id
          AND DATE_FORMAT(b.Date,'%Y-%m') = ?
    )
    ORDER BY m.material_code ASC
");
$stmt->execute([$prevMonth, $selectedMonth, $selectedMonth, $selectedMonth, $selectedMonth]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== SYNC TO inventory_reports TABLE =====
// Remove old records for this month, then re-insert from live data
$conn->prepare("DELETE FROM inventory_reports WHERE report_month = ?")->execute([$selectedMonth]);
$sync = $conn->prepare("
    INSERT INTO inventory_reports (material_code, description, category, quantity, report_month)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($reports as $r) {
    $sync->execute([
        $r['material_code'],
        $r['description'],
        $r['category'],
        (int)$r['remaining_stock'],
        $selectedMonth,
    ]);
}

// ===== UPDATE materials.starting_balance FROM PREVIOUS MONTH =====
// Sets starting_balance = last bincard balance from $prevMonth for every material
$conn->prepare("
    UPDATE materials m
    SET m.starting_balance = COALESCE(
        (SELECT b.Balance FROM bincard b
         WHERE b.material_id = m.id
           AND DATE_FORMAT(b.Date, '%Y-%m') = ?
         ORDER BY b.Date DESC, b.id DESC
         LIMIT 1),
        m.starting_balance
    )
")->execute([$prevMonth]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Reports — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/inventory-reports.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar" style="color:#3399ff;margin-right:8px;"></i>Stock Reports</h1>
        <p>Monthly inventory report — <?= htmlspecialchars($selectedMonth) ?></p>
    </div>

    <div class="card-box">
        <!-- Filter Row -->
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;" class="no-print">
            <form method="GET" class="filter-row" style="margin-bottom:0;flex-wrap:nowrap;">
                <label><i class="fas fa-calendar-alt"></i> Month:</label>
                <input type="month" name="month" value="<?= $selectedMonth ?>" onchange="this.form.submit()">
            </form>
            <div style="position:relative;flex:1;min-width:200px;max-width:320px;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aab;font-size:12px;pointer-events:none;"></i>
                <input type="text" id="reportSearch" placeholder="Search code, description, category…"
                    style="width:100%;padding:8px 10px 8px 32px;border:1px solid #dde;border-radius:8px;font-size:13px;color:#2d3142;background:#f8f9fc;outline:none;box-sizing:border-box;">
            </div>
            <div style="margin-left:auto;display:flex;gap:10px;">
                <button type="button" class="btn btn-dark" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a href="dashboard.php" class="btn btn-dark"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <!-- Table -->
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Material Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Starting Balance</th>
                    <th>Total Stock In</th>
                    <th>Total Stock Out</th>
                    <th>Remaining Stock</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($reports): ?>
                <?php foreach ($reports as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['material_code']) ?></td>
                    <td style="text-align:left"><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><span class="badge" style="background:#f0f4ff;color:#5a6080;font-weight:700;padding:3px 10px;border-radius:20px;"><?= (int)$row['starting_balance'] ?> <span style="font-size:10px;font-weight:600;opacity:0.75;"><?= htmlspecialchars($row['uom'] ?? 'pcs') ?></span></span></td>
                    <td><span class="badge" style="background:#e8f5e9;color:#2eb85c;font-weight:700;padding:3px 10px;border-radius:20px;">+<?= (int)$row['total_stock_in'] ?> <span style="font-size:10px;font-weight:600;opacity:0.75;"><?= htmlspecialchars($row['uom'] ?? 'pcs') ?></span></span></td>
                    <td><span class="badge" style="background:#fff0f0;color:#e55353;font-weight:700;padding:3px 10px;border-radius:20px;"><?= (int)$row['total_stock_out'] ?> <span style="font-size:10px;font-weight:600;opacity:0.75;"><?= htmlspecialchars($row['uom'] ?? 'pcs') ?></span></span></td>
                    <td><span class="badge badge-qty"><?= (int)$row['remaining_stock'] ?> <span style="font-size:10px;font-weight:600;opacity:0.75;"><?= htmlspecialchars($row['uom'] ?? 'pcs') ?></span></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="color:#768192;padding:24px;">No records found for this month.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="reportPagination" style="display:flex;justify-content:flex-end;align-items:center;gap:6px;margin-top:10px;flex-wrap:wrap;"></div>
</div>

<link rel="stylesheet" href="../assets/css/pagination.css">
<link rel="stylesheet" href="../assets/css/nav-animation.css">
<script src="../assets/js/pagination.js"></script>
<script src="../assets/js/nav-animation.js"></script>
<script>
initPagination('reportTable', 'reportSearch', 'reportPagination');
</script>
</body>
</html>
