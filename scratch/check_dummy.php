<?php
require __DIR__ . '/../includes/db.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM employees');
$total = $stmt->fetchColumn();
echo "Total employees: $total\n";

$stmt = $pdo->query('SELECT id, nik, name FROM employees ORDER BY id DESC LIMIT 20');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['id'] . " | " . $row['nik'] . " | " . $row['name'] . "\n";
}
