<?php

// ================= LOGIN PROTECTION =================
require_once '../class/Admin.php';

$user = new Admin();
if (!$user->isLoggedIn()) {
    header("Location: homepage.php");
    exit();
}

require_once '../class/Connection.php';
$db   = new Connection();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT m.*
    FROM materials m
    ORDER BY m.category ASC, m.material_code ASC
");
$stmt->execute();
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Materials — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="../assets/css/materials-table.css">
<link rel="stylesheet" href="../assets/css/pagination.css">
<style>
.cat-badge-line,
.cat-badge-spec,
.cat-badge-house,
.cat-badge-other { background: rgba(0,0,0,0.06); color:#5a6080; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; border: 1px solid rgba(0,0,0,0.08); }
.search-wrap { position:relative; flex:1; min-width:200px; max-width:320px; }
.search-wrap i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#aab; font-size:12px; pointer-events:none; }
.search-wrap input { width:100%; padding:8px 10px 8px 32px; border:1px solid #dde; border-radius:8px; font-size:13px; color:#2d3142; background:#f8f9fc; outline:none; box-sizing:border-box; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<?php
$catBadge = function(string $cat): string {
    if (stripos($cat, 'Line') !== false)       return 'cat-badge-line';
    if (stripos($cat, 'Special') !== false)    return 'cat-badge-spec';
    if (stripos($cat, 'House') !== false)      return 'cat-badge-house';
    return 'cat-badge-other';
};
$backPages = [
    'Line Materials'        => 'line_materials.php',
    'Special Equipment'     => 'special_equipment.php',
    'Housewiring Materials' => 'housewiring_materials.php',
];
?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-boxes" style="color:#3399ff;margin-right:8px;"></i>All Materials</h1>
        <p>All warehouse materials across every category</p>
    </div>

    <div class="card-box">
        <div class="top-bar">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <a href="add_material.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Material</a>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="matSearch" placeholder="Search code, description, category…">
                </div>
            </div>
        </div>

        <table id="matTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Starting Balance</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($materials)): ?>
                <?php foreach ($materials as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['material_code']) ?></td>
                    <td style="text-align:left"><?= htmlspecialchars($row['description']) ?></td>
                    <td><span class="badge" style="background:#f0f4ff;color:#5a6080;font-weight:700;padding:3px 10px;border-radius:20px;"><?= (int)$row['starting_balance'] ?> <span style="font-size:10px;font-weight:600;opacity:0.75;"><?= htmlspecialchars($row['uom'] ?? 'pcs') ?></span></span></td>
                    <td><span class="<?= $catBadge($row['category']) ?>"><?= htmlspecialchars($row['category']) ?></span></td>
                    <td>
                        <a href="edit_material.php?id=<?= $row['id'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <a href="delete_material.php?id=<?= $row['id'] ?>&back=materials.php" class="btn-action btn-delete delete-btn"><i class="fas fa-trash"></i> Delete</a>
                        <a href="view_material.php?id=<?= $row['id'] ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>
                        <a href="bincard.php?material_id=<?= $row['id'] ?>" class="btn-action btn-bin"><i class="fas fa-book"></i> Bin Card</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="color:#768192;padding:24px;">No materials found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="matPagination" style="display:flex;justify-content:flex-end;align-items:center;gap:6px;margin-top:10px;flex-wrap:wrap;"></div>
</div>

<script src="../assets/js/delete-confirm.js"></script>
<?php if (isset($_GET['deleted'])): ?>
<script>
Swal.fire({
    html: `<div style="text-align:center;padding:10px 0 0;">
        <div style="width:80px;height:80px;border-radius:50%;border:3px solid #2eb85c;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;background:#f0fff4;">
            <svg width="42" height="46" viewBox="0 0 64 72" xmlns="http://www.w3.org/2000/svg">
                <rect x="24" y="1" width="16" height="7" rx="3.5" fill="#2eb85c"/>
                <rect x="3" y="11" width="58" height="11" rx="3" fill="#2eb85c" transform="rotate(-14,32,16)"/>
                <rect x="8" y="27" width="48" height="8" rx="2" fill="#2eb85c"/>
                <path d="M11 35 L14 66 L50 66 L53 35 Z" fill="#2eb85c"/>
                <rect x="24" y="40" width="4" height="21" rx="2" fill="white" opacity="0.35"/>
                <rect x="30" y="40" width="4" height="21" rx="2" fill="white" opacity="0.35"/>
                <rect x="36" y="40" width="4" height="21" rx="2" fill="white" opacity="0.35"/>
            </svg>
        </div>
        <h2 style="font-size:1.4rem;color:#2d3142;margin-bottom:8px;">Removed!</h2>
        <p style="color:#768192;font-size:0.9rem;margin:0;">Material has been successfully removed.</p>
    </div>`,
    confirmButtonColor: '#3399ff',
    confirmButtonText: 'OK'
});
</script>
<?php endif; ?>
<link rel="stylesheet" href="../assets/css/nav-animation.css">
<script src="../assets/js/pagination.js"></script>
<script src="../assets/js/nav-animation.js"></script>
<script>
initPagination('matTable', 'matSearch', 'matPagination');
</script>
</body>
</html>
