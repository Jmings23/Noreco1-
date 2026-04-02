<?php
// ================= LOGIN PROTECTION =================
require_once '../class/Admin.php';

$user = new Admin();
if (!$user->isLoggedIn()) {
    header("Location: homepage.php");
    exit();
}

// ================= DATABASE CONNECTION =================
$host = "localhost";
$dbname = "noreco1_mater_inventory";
$username = "root";
$password = "";

$conn = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ================= DASHBOARD COUNTS =================
$total_materials = $conn->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$line_materials = $conn->query("SELECT COUNT(*) FROM materials WHERE category LIKE '%Line%'")->fetchColumn();
$special_equipment = $conn->query("SELECT COUNT(*) FROM materials WHERE category LIKE '%Special%'")->fetchColumn();
$house_wiring = $conn->query("SELECT COUNT(*) FROM materials WHERE category LIKE '%House%'")->fetchColumn();

// ================= LOW STOCK =================
$low_stock = $conn->query("
    SELECT 
        m.material_code,
        m.description,
        m.category,
        m.image,
        COALESCE(b.Balance, m.quantity) AS quantity
    FROM materials m
    LEFT JOIN bincard b 
        ON b.material_id = m.id
        AND b.id = (SELECT MAX(id) FROM bincard WHERE material_id = m.id)
    WHERE COALESCE(b.Balance, m.quantity) < 10
")->fetchAll(PDO::FETCH_ASSOC);

// ================= MOST FREQUENTLY WITHDRAWN =================
$most_used = $conn->query("
    SELECT
        m.material_code,
        m.description,
        m.category,
        m.image,
        SUM(b.Issues) AS total_issues
    FROM bincard b
    INNER JOIN materials m ON m.id = b.material_id
    GROUP BY b.material_id
    ORDER BY total_issues DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data   = [];
foreach ($most_used as $row) {
    $labels[] = $row['description'];
    $data[]   = (int)$row['total_issues'];
}

// ================= MONTHLY WITHDRAWAL TREND (last 12 months) =================
$monthly_trend = $conn->query("
    SELECT
        DATE_FORMAT(b.Date, '%b %Y') AS month_label,
        DATE_FORMAT(b.Date, '%Y-%m') AS month_sort,
        SUM(b.Issues)                AS total_issued
    FROM bincard b
    WHERE b.Date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_sort, month_label
    ORDER BY month_sort ASC
")->fetchAll(PDO::FETCH_ASSOC);

$monthly_labels = [];
$monthly_data   = [];
foreach ($monthly_trend as $row) {
    $monthly_labels[] = $row['month_label'];
    $monthly_data[]   = (int)$row['total_issued'];
}

// ================= MATERIAL LIST FOR TREND DROPDOWN =================
$material_list = $conn->query("
    SELECT DISTINCT m.id, m.description
    FROM bincard b
    INNER JOIN materials m ON m.id = b.material_id
    ORDER BY m.description
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NORECO 1 Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ===== GLOBAL ===== */
body {
    margin: 0;
    font-family: Arial;
    background: #f4f7fc;
}

/* ===== NAVBAR ===== */
.navbar {
    background: #003366;
    padding: 15px 25px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Navbar links */
.navbar a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-weight: bold;
    padding: 6px 12px;
    border-radius: 6px;
    position: relative;
    transition: all 0.3s ease;
}

/* Hover highlight */
.navbar a:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Click effect */
.navbar a:active {
    background-color: #ffffff;
    color: #003366;
}

/* Active / Selected page */
.navbar a.active {
    background-color: #ffffff;
    color: #003366;
}

/* Optional underline animation */
.navbar a::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -4px;
    width: 0;
    height: 2px;
    background: #ffffff;
    transition: width 0.3s ease;
    transform: translateX(-50%);
}

.navbar a:hover::after,
.navbar a.active::after {
    width: 70%;
}

/* ===== CONTENT ===== */
.container {
    padding: 30px;
}

.cards {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    width: 250px;
    box-shadow: 0 2px 6px rgba(0,0,0,.1);
}

.card h3 {
    margin: 0;
    color: #003366;
}

.card p {
    font-size: 32px;
    font-weight: bold;
}

.section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
}

/* ===== TABLE ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: center;
}

th {
    background: #003366;
    color: white;
}

/* ===== IMAGES ===== */
img {
    width: 45px;
    height: 45px;
    object-fit: contain;
}
</style>
</head>

<body>

<div class="navbar">
<strong>NORECO 1 Warehouse Line Materials Monitoring System with Integrated Data Analytics</strong>
<div>
<a href="dashboard.php">Dashboard</a>
<a href="materials.php">Materials</a>
<a href="inventory_reports.php">Stock Reports</a>
<a href="logout.php">Logout</a>
</div>
</div>

<div class="container">

<h2>📊 System Overview</h2>

<div class="cards">
<div class="card"><h3>Total Materials</h3><p><?= $total_materials ?></p></div>
<div class="card"><h3>Line Materials</h3><p><?= $line_materials ?></p></div>
<div class="card"><h3>Special Equipment</h3><p><?= $special_equipment ?></p></div>
<div class="card"><h3>House Wiring</h3><p><?= $house_wiring ?></p></div>
</div>

<div class="section" style="background:#fff3cd">
<h3>⚠️ Low Stock Alert (Below 10)</h3>
<?php if ($low_stock): ?>
<table>
<tr><th>Image</th><th>Code</th><th>Description</th><th>Category</th><th>Balance</th></tr>
<?php foreach ($low_stock as $i): ?>
<tr>
<td><img src="../assets/images/<?= $i['image'] ?>"></td>
<td><?= $i['material_code'] ?></td>
<td><?= $i['description'] ?></td>
<td><?= $i['category'] ?></td>
<td style="color:red;font-weight:bold"><?= $i['quantity'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>✅ All materials are sufficiently stocked.</p>
<?php endif; ?>
</div>

<!-- CHART 1: MOST FREQUENTLY WITHDRAWN -->
<div class="section" style="background:#e8f4ff">
<h3>📈 Most Frequently Withdrawn Materials</h3>
<table>
<tr><th>Image</th><th>Code</th><th>Description</th><th>Category</th><th>Total Withdrawals</th></tr>
<?php foreach ($most_used as $i): ?>
<tr>
<td><img src="../assets/images/<?= htmlspecialchars($i['image']) ?>"></td>
<td><?= htmlspecialchars($i['material_code']) ?></td>
<td><?= htmlspecialchars($i['description']) ?></td>
<td><?= htmlspecialchars($i['category']) ?></td>
<td><b><?= $i['total_issues'] ?></b></td>
</tr>
<?php endforeach; ?>
</table>
<div style="position:relative;height:240px;margin-top:20px">
    <canvas id="usageChart"></canvas>
</div>
</div>

<!-- CHART 2: MONTHLY WITHDRAWAL TREND -->
<div class="section" style="background:#f0fff4">
<h3>📉 Stock Depletion Over Time <small style="font-size:13px;color:#888">(last 12 months – total units withdrawn)</small></h3>
<div style="position:relative;height:240px">
    <canvas id="monthlyChart"></canvas>
</div>
</div>

<!-- CHART 3: USAGE TREND BY MATERIAL -->
<div class="section" style="background:#fff8e8">
<h3>📊 Usage Trend by Material</h3>
<div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;margin-bottom:15px">
    <div>
        <label style="font-weight:600">Material:</label>
        <select id="materialSelect" onchange="loadMaterialTrend()" style="padding:7px 10px;border-radius:5px;border:1px solid #ccc">
            <?php foreach ($material_list as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['description']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-weight:600">From:</label>
        <input type="date" id="trendFrom" style="padding:7px;border-radius:5px;border:1px solid #ccc">
    </div>
    <div>
        <label style="font-weight:600">To:</label>
        <input type="date" id="trendTo" style="padding:7px;border-radius:5px;border:1px solid #ccc">
    </div>
    <button onclick="loadMaterialTrend()" style="padding:7px 16px;background:#003366;color:#fff;border:none;border-radius:5px;cursor:pointer">Apply</button>
</div>
<div style="position:relative;height:240px">
    <canvas id="trendChart"></canvas>
</div>
</div>

</div>

<script>
function makeGradient(ctx, r, g, b) {
    const grad = ctx.createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, `rgba(${r},${g},${b},0.35)`);
    grad.addColorStop(1, `rgba(${r},${g},${b},0.01)`);
    return grad;
}

const chartDefaults = (color, title) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#003366',
            titleColor: '#fff',
            bodyColor: '#fff',
            callbacks: { label: c => ' ' + c.parsed.y + (title ? ' ' + title : '') }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { color: '#888' },
            grid: { color: 'rgba(0,0,0,0.05)', borderDash: [4,4] },
            border: { display: false }
        },
        x: {
            ticks: { color: '#888', maxRotation: 30 },
            grid: { color: 'rgba(0,0,0,0.05)', borderDash: [4,4] },
            border: { display: false }
        }
    }
});

const lineDataset = (data, grad, color) => ({
    data,
    borderColor: color,
    backgroundColor: grad,
    pointBackgroundColor: color,
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 7,
    borderWidth: 2.5,
    tension: 0.4,
    cubicInterpolationMode: 'monotone',
    fill: true
});

// CHART 1 – Most Frequently Withdrawn
(function() {
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [lineDataset(<?= json_encode($data) ?>, makeGradient(ctx,0,102,204), '#0066cc')]
        },
        options: chartDefaults('#0066cc', 'withdrawals')
    });
})();

// CHART 2 – Monthly Depletion Trend
(function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [lineDataset(<?= json_encode($monthly_data) ?>, makeGradient(ctx,0,153,102), '#009966')]
        },
        options: chartDefaults('#009966', 'units withdrawn')
    });
})();

// CHART 3 – Per-Material Usage Trend
let trendChart;
function loadMaterialTrend() {
    const m = document.getElementById('materialSelect').value;
    const f = document.getElementById('trendFrom').value;
    const t = document.getElementById('trendTo').value;

    let url = `get_material_trend.php?material_id=${m}`;
    if (f && t) url += `&from=${f}&to=${t}`;

    fetch(url).then(r => r.json()).then(d => {
        const labels = d.map(x => x.month_label);
        const values = d.map(x => parseInt(x.total_issued));

        if (trendChart) trendChart.destroy();

        const ctx = document.getElementById('trendChart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No data'],
                datasets: [lineDataset(values.length ? values : [0], makeGradient(ctx,204,102,0), '#cc6600')]
            },
            options: chartDefaults('#cc6600', 'units withdrawn')
        });
    });
}
loadMaterialTrend();
</script>

</body>
</html>
