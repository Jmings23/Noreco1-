<?php
$host = "localhost"; $dbname = "noreco1_mater_inventory"; $username = "root"; $password = "";
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Connection failed: " . $e->getMessage()); }

if (!isset($_GET['id'])) die("Error: Material ID not found.");
$id = $_GET['id'];

$allowed = ['line_materials.php','special_equipment.php','housewiring_materials.php','materials.php'];
$back = (isset($_GET['back']) && in_array($_GET['back'], $allowed)) ? $_GET['back'] : 'materials.php';

try {
    $stmt = $conn->prepare("DELETE FROM materials WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: " . $back . "?deleted=1");
    exit;
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
