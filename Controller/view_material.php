<?php
$host = "localhost"; $dbname = "noreco1_mater_inventory"; $username = "root"; $password = "";
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Connection failed: " . $e->getMessage()); }

if (!isset($_GET['id'])) { header("Location: materials.php"); exit; }
$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM materials WHERE id = :id");
$stmt->execute(['id' => $id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$material) die("Material not found.");

// Image logic
$image = "../assets/images/default.jpg";
$desc  = strtolower($material['description']);
if (!empty($material['image']) && file_exists("../assets/images/" . $material['image'])) {
    $image = "../assets/images/" . htmlspecialchars($material['image']);
} elseif (strpos($desc,'pole') !== false)        { $image = "../assets/images/pole.jpg"; }
  elseif (strpos($desc,'transformer') !== false)  { $image = "../assets/images/transformers.JPG"; }
  elseif (strpos($desc,'meter') !== false)        { $image = "../assets/images/meter.jpg"; }
  elseif (strpos($desc,'tape') !== false)         { $image = "../assets/images/tape.png"; }
  elseif (strpos($desc,'bolt') !== false || strpos($desc,'nut') !== false) { $image = "../assets/images/bolt.jpg"; }
  elseif (strpos($desc,'wire') !== false)         { $image = "../assets/images/wire.png"; }

// Category accent color
$catColors = ['Line Materials'=>'#3399ff','Special Equipment'=>'#d97706','Housewiring Materials'=>'#e55353'];
$accentColor = $catColors[$material['category']] ?? '#3399ff';
$catPages = ['Line Materials'=>'line_materials.php','Special Equipment'=>'special_equipment.php','Housewiring Materials'=>'housewiring_materials.php'];
$backPage = $catPages[$material['category']] ?? 'materials.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Material — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/view-material.css">
<style>
    /* Category accent color (dynamic PHP variable) */
    .material-img-wrap { border: 2px solid <?= $accentColor ?>; }
    .cat-badge          { background: <?= $accentColor ?>; }
    .detail-icon        { background: <?= $accentColor ?>; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-eye" style="color:<?= $accentColor ?>;margin-right:8px;"></i>Material Details</h1>
        <p>Full information for this material</p>
    </div>

    <div class="card-box detail-card">
        <div class="material-header">
            <div class="material-img-wrap">
                <img src="<?= $image ?>" alt="Material Image" onerror="this.src='../assets/images/default.jpg'">
            </div>
            <div class="material-meta">
                <h2><?= htmlspecialchars($material['description']) ?></h2>
                <span class="cat-badge"><?= htmlspecialchars($material['category']) ?></span>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-icon"><i class="fas fa-barcode"></i></div>
            <div>
                <div class="detail-label">Material Code</div>
                <div class="detail-value"><?= htmlspecialchars($material['material_code']) ?></div>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-icon"><i class="fas fa-tag"></i></div>
            <div>
                <div class="detail-label">Description</div>
                <div class="detail-value"><?= htmlspecialchars($material['description']) ?></div>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-icon"><i class="fas fa-layer-group"></i></div>
            <div>
                <div class="detail-label">Category</div>
                <div class="detail-value"><?= htmlspecialchars($material['category']) ?></div>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-icon"><i class="fas fa-cubes"></i></div>
            <div>
                <div class="detail-label">Quantity in Stock</div>
                <div class="detail-value"><?= htmlspecialchars($material['quantity']) ?> units</div>
            </div>
        </div>

        <div class="action-row">
            <a href="edit_material.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
            <a href="bincard.php?material_id=<?= $id ?>" class="btn" style="background:linear-gradient(135deg,#d97706,#f5c842);color:white;"><i class="fas fa-book"></i> Bin Card</a>
            <a href="materials.php?<?=$id ?> " class="btn btn-dark"> <i class="fas fa-arrow-left"></i> Back to Materials </a>
        </div>
    </div>
</div>

</body>
</html>
