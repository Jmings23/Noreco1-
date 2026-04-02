<?php

// ================= LOGIN PROTECTION =================
require_once '../class/admin.php';

$user = new admin();
if (!$user->isLoggedIn()) {
    header("Location: homepage.php");
    exit();
}

$host = "localhost"; $dbname = "noreco1_mater_inventory"; $username = "root"; $password = "";
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die("Connection failed: " . $e->getMessage()); }

// Ensure uom column exists (silently skips if already present)
try { $conn->exec("ALTER TABLE materials ADD COLUMN uom VARCHAR(10) NOT NULL DEFAULT 'pcs'"); } catch (PDOException $e) {}

// Generate next material code
$lastCode = $conn->query("SELECT material_code FROM materials ORDER BY id DESC LIMIT 1")->fetchColumn();
$nextNumber = $lastCode ? ((int) preg_replace('/\D/', '', $lastCode)) + 1 : 1;
$material_code = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

// Pre-select category from URL
$preCategory = $_GET['category'] ?? '';
$catPages = ['Line Materials'=>'line_materials.php','Special Equipment'=>'special_equipment.php','Housewiring Materials'=>'housewiring_materials.php'];
$backPage = $catPages[$preCategory] ?? 'materials.php';

$validUoms = ['pcs','m','ft','length','roll','set'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST['description']);
    $category    = $_POST['category'];
    $quantity    = (int) $_POST['quantity'];
    $uom         = in_array($_POST['uom'] ?? '', $validUoms) ? $_POST['uom'] : 'pcs';
    $image_name  = null;

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg','jpeg','png','gif'])) {
            $upload_path = '../assets/images/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            $image_name = uniqid('mat_', true) . '.' . $file_ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $image_name);
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO materials (material_code, description, category, quantity, uom, image) VALUES (:mc, :desc, :cat, :qty, :uom, :img)");
        $stmt->execute([':mc'=>$material_code,':desc'=>$description,':cat'=>$category,':qty'=>$quantity,':uom'=>$uom,':img'=>$image_name]);
        $showSuccess = true;
        $redirectPage = $catPages[$category] ?? 'materials.php';
    } catch (PDOException $e) { echo "<p style='color:red;'>Error: {$e->getMessage()}</p>"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Material — NORECO 1</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body::before { display: none; }
    body { background: #f4f6f9; }
    .form-card { max-width: 520px; margin: 0 auto; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #2d3142; margin-bottom: 6px; }
    .form-group input, .form-group select { width: 100%; }
    .form-group input[readonly] { background: #f0f2f5; color: #768192; cursor: not-allowed; }
    .form-divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    .submit-btn { width: 100%; padding: 11px; font-size: 14px; margin-top: 4px; }
    .qty-uom-row { display: flex; gap: 10px; }
    .qty-uom-row input  { flex: 1; width: auto; }
    .qty-uom-row select { width: 100px; flex-shrink: 0; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fas fa-plus-circle" style="color:#3399ff;margin-right:8px;"></i>Add New Material</h1>
        <p>Fill in the details below to add a new material to the inventory</p>
    </div>

    <div class="card-box form-card">
        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label><i class="fas fa-barcode"></i> Material Code</label>
                <input type="text" value="<?= htmlspecialchars($material_code) ?>" readonly>
            </div>

            <div class="form-group">
                <label><i class="fas fa-tag"></i> Description</label>
                <input type="text" name="description" placeholder="Enter material description" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Category</label>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Line Materials" <?= $preCategory=='Line Materials'?'selected':'' ?>>Line Materials</option>
                    <option value="Special Equipment" <?= $preCategory=='Special Equipment'?'selected':'' ?>>Special Equipment</option>
                    <option value="Housewiring Materials" <?= $preCategory=='Housewiring Materials'?'selected':'' ?>>Housewiring Materials</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-cubes"></i> QTY / UOM</label>
                <div class="qty-uom-row">
                    <input type="number" name="quantity" min="0" value="0" placeholder="0" required>
                    <select name="uom">
                        <option value="pcs">pcs</option>
                        <option value="m">m</option>
                        <option value="ft">ft</option>
                        <option value="roll">roll</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-image"></i> Upload Image <span style="color:#e55353;font-weight:700;">*</span></label>
                <input type="file" name="image" id="imageInput" accept="image/*">
            </div>

            <hr class="form-divider">

            <button type="submit" class="btn btn-primary submit-btn">
                <i class="fas fa-save"></i> Add Material
            </button>
        </form>

        <div style="margin-top:14px;text-align:center;">
            <a href="<?= $backPage ?>" style="font-size:13px;color:#768192;text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Back to <?= htmlspecialchars($preCategory ?: 'Materials') ?>
            </a>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const img = document.getElementById('imageInput');
    if (!img.files || img.files.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Image Required',
            text: 'Please upload an image before adding the material.',
            confirmButtonColor: '#3399ff',
            confirmButtonText: 'OK'
        });
    }
});
</script>
<?php if (!empty($showSuccess)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Material Added!',
        text: 'The material has been successfully added.',
        confirmButtonColor: '#3399ff',
        confirmButtonText: 'OK'
    }).then(() => { window.location = '<?= $redirectPage ?>'; });
</script>
<?php endif; ?>
</body>
</html>
