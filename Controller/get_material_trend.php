<?php
header('Content-Type: application/json');

$host     = "localhost";
$dbname   = "noreco1_mater_inventory";
$username = "root";
$password = "";

$conn = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$material_id = (int)($_GET['material_id'] ?? 0);
$from        = $_GET['from'] ?? null;
$to          = $_GET['to']   ?? null;

if (!$material_id) {
    echo json_encode([]);
    exit;
}

// Default: last 12 months if no date range given
if (!$from || !$to) {
    $from = date('Y-m-d', strtotime('-12 months'));
    $to   = date('Y-m-d');
}

$sql = "
    SELECT
        DATE_FORMAT(b.Date, '%b %Y') AS month_label,
        DATE_FORMAT(b.Date, '%Y-%m') AS month_sort,
        SUM(b.Issues)                AS total_issued
    FROM bincard b
    WHERE b.material_id = :material_id
      AND b.Date BETWEEN :from AND :to
    GROUP BY month_sort, month_label
    ORDER BY month_sort ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':material_id' => $material_id,
    ':from'        => $from,
    ':to'          => $to,
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Index DB results by month_sort key
$dbData = [];
foreach ($rows as $row) {
    $dbData[$row['month_sort']] = (int)$row['total_issued'];
}

// Generate every month in the date range so months with 0 issues still appear
$allMonths = [];
$cursor = new DateTime(date('Y-m-01', strtotime($from)));
$end    = new DateTime(date('Y-m-01', strtotime($to)));
while ($cursor <= $end) {
    $key = $cursor->format('Y-m');
    $allMonths[] = [
        'month_label'  => $cursor->format('M Y'),
        'month_sort'   => $key,
        'total_issued' => $dbData[$key] ?? 0,
    ];
    $cursor->modify('+1 month');
}

echo json_encode($allMonths);
?>
