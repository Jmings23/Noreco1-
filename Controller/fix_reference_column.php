<?php
require_once '../class/Connection.php';
$db   = new Connection();
$conn = $db->connect();

try {
    // Change Reference column from numeric to text so full values like "2026-080124" are stored
    $conn->exec("ALTER TABLE bincard MODIFY COLUMN `Reference` VARCHAR(100)");
    echo "<p style='font-family:sans-serif;color:green;font-size:16px;'>
            ✅ Done! The Reference column has been updated to accept full text values like \"2026-080124\".<br><br>
            <a href='bincard.php?material_id=1' style='color:#3399ff;'>Go back to Bin Card</a>
          </p>";
} catch (PDOException $e) {
    echo "<p style='font-family:sans-serif;color:red;font-size:16px;'>Error: " . $e->getMessage() . "</p>";
}
?>
