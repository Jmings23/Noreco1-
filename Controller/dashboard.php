<?php
// ================= LOGIN PROTECTION =================
require_once '../class/admin.php';

$user = new admin();
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
        m.id,
        m.material_code,
        m.description,
        m.category,
        m.image,
        SUM(b.Issues) AS total_issues
    FROM bincard b
    INNER JOIN materials m ON m.id = b.material_id
    GROUP BY b.material_id, m.id, m.material_code, m.description, m.category, m.image
    ORDER BY total_issues DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ================= PER-MATERIAL MONTHLY CHARTS =================
$current_year        = date('Y');
$material_chart_data = [];
$short_month_labels  = [];
for ($m = 1; $m <= 12; $m++) {
    $short_month_labels[] = date('M', mktime(0, 0, 0, $m, 1, $current_year));
}

if (!empty($most_used)) {
    $top_ids      = array_column($most_used, 'id');
    $placeholders = implode(',', array_fill(0, count($top_ids), '?'));

    $mstmt = $conn->prepare("
        SELECT
            b.material_id,
            DATE_FORMAT(b.Date, '%Y-%m') AS month_sort,
            SUM(b.Issues) AS total_issued
        FROM bincard b
        WHERE b.material_id IN ($placeholders)
          AND YEAR(b.Date) = ?
        GROUP BY b.material_id, month_sort
        ORDER BY b.material_id, month_sort
    ");
    $mstmt->execute(array_merge($top_ids, [$current_year]));
    $mraw = $mstmt->fetchAll(PDO::FETCH_ASSOC);

    $indexed = [];
    foreach ($mraw as $row) {
        $indexed[$row['material_id']][$row['month_sort']] = (int)$row['total_issued'];
    }

    foreach ($most_used as $mat) {
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $key       = $current_year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
            $monthly[] = $indexed[$mat['id']][$key] ?? 0;
        }
        $material_chart_data[] = [
            'id'          => $mat['id'],
            'description' => $mat['description'],
            'monthly'     => $monthly,
        ];
    }
}

// ================= MONTHLY WITHDRAWAL TREND (Jan–Dec) =================
$monthly_trend = $conn->query("
    SELECT
        DATE_FORMAT(b.Date, '%b %Y') AS month_label,
        DATE_FORMAT(b.Date, '%Y-%m') AS month_sort,
        SUM(b.Issues)                AS total_issued
    FROM bincard b
    WHERE YEAR(b.Date) = $current_year
    GROUP BY month_sort, month_label
    ORDER BY month_sort ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Always show all 12 months with zeros for months with no data
$actual_data = [];
foreach ($monthly_trend as $row) {
    $actual_data[$row['month_sort']] = (int)$row['total_issued'];
}
$monthly_labels = [];
$monthly_data   = [];
for ($m = 1; $m <= 12; $m++) {
    $key   = $current_year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
    $label = date('M Y', mktime(0, 0, 0, $m, 1, $current_year));
    $monthly_labels[] = $label;
    $monthly_data[]   = $actual_data[$key] ?? 0;
}

// ================= MATERIAL LIST FOR TREND DROPDOWN =================
$material_list = $conn->query("
    SELECT DISTINCT m.id, m.description
    FROM bincard b
    INNER JOIN materials m ON m.id = b.material_id
    ORDER BY m.description ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ================= CATEGORY DISTRIBUTION FOR DONUT =================
$category_dist = $conn->query("
    SELECT category, COUNT(*) AS count
    FROM materials
    GROUP BY category
    ORDER BY FIELD(category, 'Line Materials', 'Special Equipment', 'Housewiring Materials')
")->fetchAll(PDO::FETCH_ASSOC);

$cat_labels = [];
$cat_data   = [];
foreach ($category_dist as $row) {
    $cat_labels[] = $row['category'] ?: 'Uncategorized';
    $cat_data[]   = (int)$row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NORECO 1 Dashboard</title>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/dashboard-charts.js"></script>
<style>
    .mat-row:hover      { background: rgba(51,153,255,0.06); }
    .mat-row-active     { background: rgba(51,153,255,0.12) !important; }
    .mat-row-active td  { color: #3399ff; font-weight: 600; }
</style>
</head>
<body>

<!-- ==================== SIDEBAR ==================== -->
<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="fas fa-warehouse"></i></div>
        <div class="sidebar-brand-text">
            <div class="brand-name">NORECO 1 WMS</div>
            <div class="brand-sub">Warehouse Monitoring System</div>
        </div>
    </a>

    <div class="sidebar-section-label">Main Menu</div>

    <a href="dashboard.php" class="nav-item active">
        <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a href="materials.php" class="nav-item">
        <i class="fas fa-boxes"></i> Materials
    </a>
    <a href="inventory_reports.php" class="nav-item">
        <i class="fas fa-chart-bar"></i> Stock Reports
    </a>
    <a href="bincard_importer.php" class="nav-item">
        <i class="fas fa-file-import"></i> Bin Card Importer
    </a>

    <div class="sidebar-spacer"></div>

    <a href="logout.php" class="nav-item logout">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</aside>

<!-- ==================== MAIN CONTENT ==================== -->
<div class="main">

    <!-- Topbar -->
    <div class="topbar">
        <div class="breadcrumb">Home / Dashboard</div>
        <div class="page-title">Dashboard Overview</div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards">
        <div class="stat-card purple">
            <div class="stat-card-label">Total Materials</div>
            <div class="stat-card-value"><?= $total_materials ?></div>
            <i class="fas fa-layer-group stat-card-icon"></i>
        </div>
        <div class="stat-card blue">
            <div class="stat-card-label">Line Materials</div>
            <div class="stat-card-value"><?= $line_materials ?></div>
            <i class="fas fa-bolt stat-card-icon"></i>
        </div>
        <div class="stat-card green">
            <div class="stat-card-label">Special Equipment</div>
            <div class="stat-card-value"><?= $special_equipment ?></div>
            <i class="fas fa-tools stat-card-icon"></i>
        </div>
        <div class="stat-card red">
            <div class="stat-card-label">House Wiring</div>
            <div class="stat-card-value"><?= $house_wiring ?></div>
            <i class="fas fa-plug stat-card-icon"></i>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="low-stock-card">
        <div class="card-title">
            <i class="fas fa-exclamation-triangle" style="color:#f5a623"></i>
            Low Stock Alert
            <span style="font-size:12px;font-weight:400;color:#b07000;margin-left:4px">(Below 10 units)</span>
        </div>
        <?php if ($low_stock): ?>
        <table class="table-dark-header">
            <thead>
                <tr><th>Image</th><th>Code</th><th>Description</th><th>Category</th><th>Balance</th></tr>
            </thead>
            <tbody>
            <?php foreach ($low_stock as $i): ?>
            <tr>
                <td><img class="mat-img" src="../assets/images/<?= htmlspecialchars($i['image']) ?>"></td>
                <td><?= htmlspecialchars($i['material_code']) ?></td>
                <td style="text-align:left"><?= htmlspecialchars($i['description']) ?></td>
                <td><?= htmlspecialchars($i['category']) ?></td>
                <td><span class="badge-low"><?= $i['quantity'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="margin-top:12px;color:#5a7a00;font-weight:500">
            <i class="fas fa-check-circle"></i> All materials are sufficiently stocked.
        </p>
        <?php endif; ?>
    </div>

    <!-- Charts Row 1: Most Withdrawn (line) + Category Distribution (donut) -->
    <div class="chart-grid-2">

        <!-- Chart 1: Most Frequently Withdrawn -->
        <div class="card-box">
            <div class="card-title">
                <span class="title-dot" style="background:linear-gradient(135deg,#3399ff,#66bbff)"></span>
                Most Frequently Withdrawn Materials
            </div>
            <div class="card-subtitle">Top 5 most taken-out materials — click a row to view its monthly chart</div>
            <table style="margin-bottom:12px">
                <thead>
                    <tr><th>Image</th><th>Code</th><th>Description</th><th>Withdrawals</th></tr>
                </thead>
                <tbody>
                <?php foreach ($most_used as $idx => $i): ?>
                <tr class="mat-row<?= $idx === 0 ? ' mat-row-active' : '' ?>" data-idx="<?= $idx ?>" style="cursor:pointer;transition:background 0.2s;">
                    <td><img class="mat-img" src="../assets/images/<?= htmlspecialchars($i['image']) ?>"></td>
                    <td><?= htmlspecialchars($i['material_code']) ?></td>
                    <td style="text-align:left"><?= htmlspecialchars($i['description']) ?></td>
                    <td><span class="badge-qty"><?= $i['total_issues'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="font-size:12px;color:#768192;margin-bottom:6px;">
                <i class="fas fa-chart-line" style="color:#3399ff;margin-right:4px;"></i>
                Monthly withdrawals — <span id="activeMatLabel" style="color:#3399ff;font-weight:600;"></span>
            </div>
            <div style="position:relative;height:220px">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Category Distribution (donut) -->
        <div class="card-box" style="background:rgba(45,49,66,0.10);border:1px solid rgba(255,255,255,0.4);">

            <div class="card-title" style="color:#ffffff;">
                <span class="title-dot" style="background:linear-gradient(135deg,#3399ff,#66bbff)"></span>
                Inventory by Category
            </div>
            <div class="card-subtitle" style="color:rgba(255,255,255,0.6);">Distribution of materials per category</div>
            <div style="position:relative;height:260px;margin-top:55px;">
                <canvas id="donutChart"></canvas>
                <svg id="donutBolt" viewBox="0 0 44 64" width="44" height="64" style="
                    position:absolute;top:50%;left:50%;
                    transform:translate(-50%,-50%) scale(0);
                    opacity:0;pointer-events:none;overflow:visible;
                ">
                    <path d="M 32,2 L 10,34 L 22,34 L 6,62 L 38,26 L 26,26 Z"
                          fill="none" stroke="#f5c842" stroke-width="3"
                          stroke-linejoin="round" stroke-linecap="round"
                          stroke-dasharray="200" stroke-dashoffset="200"/>
                </svg>
            </div>
            <div id="donutLegend" style="display:flex;justify-content:center;gap:18px;flex-wrap:wrap;margin-top:28px;padding-bottom:8px;">
                <?php
                $legendColorMap = [
                    'Line Materials'        => '#3399ff',
                    'Special Equipment'     => '#e8a000',
                    'Housewiring Materials' => '#2eb85c',
                ];
                foreach ($cat_labels as $i => $lbl):
                    $col = $legendColorMap[$lbl] ?? '#aaa';
                ?>
                <span style="display:flex;align-items:center;gap:6px;font-size:11px;color:#768192;">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $col ?>;flex-shrink:0;"></span>
                    <?= htmlspecialchars($lbl) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Chart 3: Stock Depletion (bar chart) -->
    <div class="card-box">
        <div class="card-title">
            <span class="title-dot" style="background:linear-gradient(135deg,#7b78e8,#9a98f0)"></span>
            Stock Depletion Over Time
        </div>
        <div class="card-subtitle">Total materials withdrawn per month (Last 12 months) — shows how fast the warehouse is being emptied</div>
        <div style="position:relative;height:260px">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Chart 4: Usage Trend by Material (line) -->
    <div class="card-box">
        <div class="card-title">
            <span class="title-dot" style="background:linear-gradient(135deg,#e8a000,#f5c842)"></span>
            Usage Trend by Material
        </div>
        <div class="card-subtitle">Withdrawal history for a specific material (Last 12 months)</div>
        <div class="filter-row">
            <label>Material:</label>
            <select id="materialSelect" onchange="loadMaterialTrend()">
                <?php foreach ($material_list as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['description']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>From:</label>
            <input type="date" id="trendFrom">
            <label>To:</label>
            <input type="date" id="trendTo">
            <button class="btn-apply" onclick="loadMaterialTrend()"><i class="fas fa-filter"></i> Filter</button>
        </div>
        <div style="position:relative;height:100px">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

</div><!-- /main -->

<script>
/* ===================== CHART 1: Switchable line chart per material ===================== */
(function () {
    const materialData = <?= json_encode($material_chart_data) ?>;
    const monthLabels  = <?= json_encode($short_month_labels) ?>;

    const ctx = document.getElementById('usageChart').getContext('2d');
    let currentChart = null;
    let animFrame    = null;
    let phase        = 0;

    const glowPlugin = {
        id: 'glowLine',
        beforeDatasetsDraw(chart) {
            const c = chart.ctx;
            c.save();
            const glow = 0.55 + Math.abs(Math.sin(phase)) * 0.45;
            const blur = 8 + Math.abs(Math.sin(phase)) * 14;
            c.shadowColor = `rgba(51,153,255,${glow})`;
            c.shadowBlur  = blur;
        },
        afterDatasetsDraw(chart) { chart.ctx.restore(); }
    };

    function renderChart(idx) {
        if (animFrame)    { cancelAnimationFrame(animFrame); animFrame = null; }
        if (currentChart) { currentChart.destroy(); currentChart = null; }
        phase = 0;

        const mat = materialData[idx];
        document.getElementById('activeMatLabel').textContent = mat.description;

        // Highlight active row
        document.querySelectorAll('.mat-row').forEach(function (r, i) {
            r.classList.toggle('mat-row-active', i === idx);
        });

        currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels  : monthLabels,
                datasets: [lineDataset(
                    mat.monthly,
                    makeGradient(ctx, 51, 153, 255),
                    '#3399ff'
                )]
            },
            options: lineOptions('withdrawals'),
            plugins : [glowPlugin]
        });

        (function animate() {
            phase += 0.018;
            const alpha = 0.08 + Math.abs(Math.sin(phase)) * 0.30;
            const grad  = ctx.createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, `rgba(51,153,255,${alpha})`);
            grad.addColorStop(1, `rgba(51,153,255,0.01)`);
            currentChart.data.datasets[0].backgroundColor = grad;
            currentChart.update('none');
            animFrame = requestAnimationFrame(animate);
        })();
    }

    // Wire up row clicks
    document.querySelectorAll('.mat-row').forEach(function (row) {
        row.addEventListener('click', function () {
            renderChart(parseInt(this.dataset.idx, 10));
        });
    });

    // Default: show first material
    if (materialData.length) renderChart(0);
})();

/* ===================== CHART 2: Category Distribution (donut) ===================== */
(function () {
    const ctx        = document.getElementById('donutChart').getContext('2d');
    const canvas     = document.getElementById('donutChart');
    const realData   = <?= json_encode($cat_data) ?>;
    const realLabels = <?= json_encode($cat_labels) ?>;
    const colorMap   = {
        'Line Materials':        '#3399ff',
        'Special Equipment':     '#e8a000',
        'Housewiring Materials': '#2eb85c',
    };
    const fallback = ['#e8a000','#3399ff','#2eb85c','#e55353','#f5c842','#20a8d8'];
    const palette  = realLabels.map((l, i) => colorMap[l] || fallback[i]);

    // Pre-calculate each segment's start & span angles (12 o'clock = -π/2)
    const total    = realData.reduce((a, b) => a + b, 0);
    const spans    = realData.map(v => (v / total) * Math.PI * 2);
    const starts   = [];
    let cum = -Math.PI / 2;
    spans.forEach(s => { starts.push(cum); cum += s; });

    let sweepProgress = 0;   // 0 → 1
    let sweepDone     = false;

    // Plugin: paints the colored ring segments up to the current sweep angle
    const sweepPlugin = {
        id: 'colorSweep',
        afterDraw(chart) {
            if (sweepDone) return;
            const meta = chart.getDatasetMeta(0);
            if (!meta.data.length) return;

            const arc    = meta.data[0];
            const c      = chart.ctx;
            const cx     = arc.x,  cy = arc.y;
            const outerR = arc.outerRadius;
            const innerR = arc.innerRadius;
            const nowAngle = -Math.PI / 2 + sweepProgress * Math.PI * 2;

            for (let i = 0; i < realData.length; i++) {
                const segEnd = starts[i] + spans[i];
                if (nowAngle <= starts[i]) break;          // not reached yet
                const drawTo = Math.min(segEnd, nowAngle); // clamp to sweep tip

                c.save();
                // Soft glow at the leading edge
                c.shadowColor = palette[i] || '#fff';
                c.shadowBlur  = 10;

                // Draw annular sector (ring slice) for this segment
                c.beginPath();
                c.arc(cx, cy, outerR, starts[i], drawTo);
                c.arc(cx, cy, innerR, drawTo, starts[i], true);
                c.closePath();
                c.fillStyle = palette[i] || '#aaa';
                c.fill();
                c.restore();
            }
        }
    };

    // Chart starts with invisible segments — sweep plugin draws them manually
    const donutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: realLabels,
            datasets: [{
                data           : realData,
                backgroundColor: realData.map(() => 'rgba(0,0,0,0)'),
                borderColor    : 'transparent',
                borderWidth    : 0,
                hoverOffset    : 8
            }]
        },
        options: {
            responsive         : true,
            maintainAspectRatio: false,
            cutout             : '62%',
            animation          : { duration: 0 },
            plugins: {
                legend : { display: false },
                tooltip: { enabled: false }
            }
        },
        plugins: [sweepPlugin]
    });

    // Phase 1 — sweep the ring clockwise, color by color
    (function runSweep() {
        sweepProgress = Math.min(1, sweepProgress + 0.005);
        donutChart.update('none');
        if (sweepProgress < 1) {
            requestAnimationFrame(runSweep);
        } else {
            // Ring is full — hand off to real Chart.js segments & enable tooltips
            sweepDone = true;
            donutChart.data.datasets[0].backgroundColor = palette.slice(0, realData.length);
            donutChart.options.plugins.tooltip = {
                enabled        : true,
                backgroundColor: '#1a1a3e',
                titleColor     : '#fff',
                bodyColor      : '#ccc',
                padding        : 10,
                callbacks      : { label: c => '  ' + c.label + ': ' + c.parsed + ' items' }
            };
            donutChart.update('none');

            // Phase 2 — glow in, hold 2 s, glow out
            let glowVal = 0, glowState = 'in';
            (function animateGlow() {
                if (glowState === 'in') {
                    glowVal = Math.min(1, glowVal + 0.018);
                    canvas.style.filter = `drop-shadow(0 0 ${glowVal * 20}px rgba(120,200,255,${glowVal}))`;
                    if (glowVal < 1) { requestAnimationFrame(animateGlow); }
                    else { glowState = 'hold'; setTimeout(() => {
                            glowState = 'out';
                            // Pop bolt in the center
                            const bolt = document.getElementById('donutBolt');
                            bolt.style.transition = 'transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s ease';
                            bolt.style.transform  = 'translate(-50%,-50%) scale(1)';
                            bolt.style.opacity    = '1';
                            // Once pop lands, start the draw loop
                            setTimeout(() => {
                                bolt.style.transition = '';
                                bolt.classList.add('bolt-chill'); // applies glow filter

                                const bp      = bolt.querySelector('path');
                                const pathLen = bp.getTotalLength();
                                bp.style.strokeDasharray  = pathLen;
                                bp.style.strokeDashoffset = pathLen;
                                bp.style.fill = 'none';

                                let offset = pathLen;
                                let state  = 'draw';

                                (function drawLoop() {
                                    if (state === 'draw') {
                                        offset = Math.max(0, offset - 4);          // drawing speed
                                        bp.style.strokeDashoffset = offset;

                                        // flood fill in the last 20% of the stroke
                                        const pct = 1 - offset / pathLen;
                                        if (pct >= 0.8) {
                                            const a = ((pct - 0.8) / 0.2) * 0.9;
                                            bp.style.fill = `rgba(245,200,66,${a})`;
                                        }

                                        if (offset > 0) {
                                            requestAnimationFrame(drawLoop);
                                        } else {
                                            // fully drawn — pop golden glow on donut, then reset bolt
                                            state = 'hold';
                                            let gv = 0;
                                            (function popGlow() {
                                                gv = Math.min(1, gv + 0.012);
                                                canvas.style.filter = `drop-shadow(0 0 ${gv * 22}px rgba(245,200,66,${gv * 0.85}))`;
                                                if (gv < 1) {
                                                    requestAnimationFrame(popGlow);
                                                } else {
                                                    // hold glow briefly then fade out
                                                    setTimeout(() => {
                                                        (function fadeGlow() {
                                                            gv = Math.max(0, gv - 0.012);
                                                            canvas.style.filter = gv > 0
                                                                ? `drop-shadow(0 0 ${gv * 22}px rgba(245,200,66,${gv * 0.85}))`
                                                                : '';
                                                            if (gv > 0) {
                                                                requestAnimationFrame(fadeGlow);
                                                            } else {
                                                                // glow off — reset bolt and redraw
                                                                bp.style.fill             = 'none';
                                                                bp.style.strokeDashoffset = pathLen;
                                                                offset = pathLen;
                                                                state  = 'draw';
                                                                setTimeout(() => requestAnimationFrame(drawLoop), 180);
                                                            }
                                                        })();
                                                    }, 400);
                                                }
                                            })();
                                        }
                                    }
                                })();
                            }, 420);
                            requestAnimationFrame(animateGlow);
                        }, 1000); }
                } else if (glowState === 'out') {
                    glowVal = Math.max(0, glowVal - 0.025);
                    canvas.style.filter = glowVal > 0 ? `drop-shadow(0 0 ${glowVal * 20}px rgba(120,200,255,${glowVal}))` : '';
                    if (glowVal > 0) requestAnimationFrame(animateGlow);
                }
            })();
        }
    })();
})();

/* ===================== CHART 3: Stock Depletion (bar) ===================== */
(function () {
    const ctx = document.getElementById('monthlyChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [{
                data: <?= json_encode($monthly_data) ?>,
                backgroundColor: 'rgba(123,120,232,0.8)',
                hoverBackgroundColor: 'rgba(123,120,232,1)',
                borderRadius: 7,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                onComplete(anim) {
                    const chart = anim.chart;
                    setTimeout(() => { chart.reset(); chart.update(); }, 500);
                }
            },
            animations: {
                numbers: {
                    type: 'number',
                    duration: 4000,
                    easing: 'easeInOutSine',
                    delay(context) {
                        return context.dataIndex * 300;
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1a3e',
                    titleColor: '#fff',
                    bodyColor: '#ccc',
                    padding: 10,
                    callbacks: { label: c => '  ' + c.parsed.y + ' units withdrawn' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#aaa', font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)', borderDash: [4,4] },
                    border: { display: false }
                },
                x: {
                    ticks: { color: '#aaa', maxRotation: 30, font: { size: 11 } },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
})();

/* ===================== CHART 4: init ===================== */
initDefaultDates();
loadMaterialTrend();
</script>
<script src="../assets/js/nav-animation.js"></script>

</body>
</html>
