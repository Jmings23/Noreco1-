<?php
$host = "localhost"; $dbname = "noreco1_mater_inventory"; $username = "root"; $password = "";
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Connection failed: " . $e->getMessage()); }

if (!isset($_GET['id'])) die("Error: Material ID not found.");
$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM materials WHERE id = :id");
$stmt->execute([':id' => $id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$material) die("Error: Material not found.");
$backPage = 'materials.php';

$successMsg = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $validUoms = ['pcs','m','ft','roll'];
        $uom = in_array($_POST['uom'] ?? '', $validUoms) ? $_POST['uom'] : 'pcs';
        $update = $conn->prepare("UPDATE materials SET material_code=:mc, description=:desc, category=:cat, quantity=:qty, uom=:uom WHERE id=:id");
        $update->execute([':mc'=>$_POST['material_code'],':desc'=>$_POST['description'],':cat'=>$_POST['category'],':qty'=>$_POST['quantity'],':uom'=>$uom,':id'=>$id]);
        $successMsg = $backPage;
    } catch (PDOException $e) { echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Material — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/edit-material.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-edit" style="color:#3399ff;margin-right:8px;"></i>Edit Material</h1>
        <p>Update the details for <strong><?= htmlspecialchars($material['description']) ?></strong></p>
    </div>

    <div class="card-box form-card">
        <form method="POST">

            <div class="form-group">
                <label><i class="fas fa-barcode"></i> Material Code</label>
                <input type="text" name="material_code" value="<?= htmlspecialchars($material['material_code']) ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-tag"></i> Description</label>
                <input type="text" name="description" value="<?= htmlspecialchars($material['description']) ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Category</label>
                <select name="category" required>
                    <option value="Line Materials"         <?= $material['category']=='Line Materials'?'selected':'' ?>>Line Materials</option>
                    <option value="Special Equipment"      <?= $material['category']=='Special Equipment'?'selected':'' ?>>Special Equipment</option>
                    <option value="Housewiring Materials"  <?= $material['category']=='Housewiring Materials'?'selected':'' ?>>Housewiring Materials</option>
                
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-cubes"></i> QTY / UOM</label>
                <div class="qty-uom-row">
                    <input type="number" name="quantity" value="<?= htmlspecialchars($material['quantity']) ?>" min="0" readonly>
                    <select name="uom">
                        <?php foreach (['pcs','m','ft','roll'] as $u): ?>
                        <option value="<?= $u ?>" <?= ($material['uom'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="form-divider">

            <button type="submit" class="btn btn-primary submit-btn">
                <i class="fas fa-save"></i> Update Material
            </button>
        </form>

        <div style="margin-top:14px;text-align:center;">
            <a href="<?= $backPage ?>" style="font-size:13px;color:#768192;text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Back to Materials
            </a>
        </div>
    </div>
</div>

<?php if ($successMsg): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Material Updated!',
        text: 'The material has been successfully updated.',
        confirmButtonColor: '#3399ff',
        confirmButtonText: 'OK'
    }).then(() => { window.location = '<?= $successMsg ?>'; });
</script>
<?php endif; ?>
</body>
</html>
