<?php
// Database connection
$host = "localhost";
$dbname = "noreco1_mater_inventory";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch available materials
$materials = $conn->query("SELECT * FROM materials")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $po_no = $_POST['po_no'];
    $source = $_POST['source'];
    $received_by = $_POST['received_by'];
    $purchase_date = $_POST['purchase_date'];

    try {
        $conn->beginTransaction();

        // Insert purchase
        $stmt = $conn->prepare("INSERT INTO purchases (po_no, source, received_by, purchase_date) 
                                VALUES (:po_no, :source, :received_by, :purchase_date)");
        $stmt->execute([
            ':po_no' => $po_no,
            ':source' => $source,
            ':received_by' => $received_by,
            ':purchase_date' => $purchase_date
        ]);
        $purchase_id = $conn->lastInsertId();

        // Insert purchase items
        foreach ($_POST['material_id'] as $i => $material_id) {
            $qty = $_POST['qty'][$i];

            $stmt2 = $conn->prepare("INSERT INTO purchase_items (purchase_id, material_id, qty)
                                     VALUES (:purchase_id, :material_id, :qty)");
            $stmt2->execute([
                ':purchase_id' => $purchase_id,
                ':material_id' => $material_id,
                ':qty' => $qty
            ]);

            // Update stock
            $conn->prepare("UPDATE materials SET quantity = quantity + :qty WHERE id = :id")
                ->execute([':qty' => $qty, ':id' => $material_id]);
        }

        $conn->commit();
        echo "<script>alert('Purchase added successfully!'); window.location='purchases.php';</script>";
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Purchase - Warehouse</title>
<style>
    body { font-family: Arial; background: #f4f6f9; margin: 0; }
    .navbar { background: #003366; color: white; padding: 15px; text-align: center; font-weight: bold; }
    .container { background: white; width: 600px; margin: 50px auto; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input, select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
    button { margin-top: 15px; padding: 10px 20px; background: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #0055a5; }
    .add-item { background: #28a745; margin-top: 10px; }
</style>
</head>
<body>

<div class="navbar">NORECO 1 Warehouse Materials  Management System</div>

<div class="container">
    <h2>Add Purchase Order</h2>
    <form method="POST">
        <label>PO No:</label>
        <input type="text" name="po_no" required>

        <label>Source:</label>
        <input type="text" name="source" required>

        <label>Received By:</label>
        <input type="text" name="received_by" required>

        <label>Purchase Date:</label>
        <input type="date" name="purchase_date" required>

        <h3>Purchase Items</h3>
        <div id="items">
            <div>
                <select name="material_id[]" required>
                    <option value="">-- Select Material --</option>
                    <?php foreach ($materials as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= $m['description'] ?> (Stock: <?= $m['quantity'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="qty[]" placeholder="Quantity" min="1" required>
            </div>
        </div>
        <button type="button" class="add-item" onclick="addItem()">+ Add Another Material</button>

        <button type="submit">Save Purchase</button>
    </form>
</div>

<script>
function addItem() {
    const div = document.createElement('div');
    div.innerHTML = `
        <select name="material_id[]" required>
            <option value="">-- Select Material --</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= $m['id'] ?>"><?= $m['description'] ?> (Stock: <?= $m['quantity'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="qty[]" placeholder="Quantity" min="1" required>
    `;
    document.getElementById('items').appendChild(div);
}
</script>

</body>
</html>
