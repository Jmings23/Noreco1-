<?php
// Current page detection for active nav highlight
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="../assets/css/sidebar.css">
<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="fas fa-warehouse"></i></div>
        <div class="sidebar-brand-text">
            <div class="brand-name">NORECO 1 WMS</div>
            <div class="brand-sub">Warehouse Monitoring System</div>
        </div>
    </a>

    <span class="nav-section-label">Menu</span>

    <a href="dashboard.php" class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a href="materials.php" class="nav-item <?= in_array($current, ['materials.php','line_materials.php','special_equipment.php','housewiring_materials.php','add_material.php','edit_material.php','view_material.php']) ? 'active' : '' ?>">
        <i class="fas fa-boxes"></i> Materials
    </a>
    <a href="inventory_reports.php" class="nav-item <?= $current === 'inventory_reports.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i> Stock Reports
    </a>
    <a href="bincard_importer.php" class="nav-item <?= $current === 'bincard_importer.php' ? 'active' : '' ?>">
        <i class="fas fa-file-import"></i> Bin Card Importer
    </a>

    <div class="sidebar-spacer"></div>

    <a href="logout.php" class="nav-item logout">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</aside>
