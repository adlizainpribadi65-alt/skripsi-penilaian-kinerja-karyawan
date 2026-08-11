<?php
require 'includes/db.php';

$updates = [
    1 => ['name' => 'Presensi', 'weight' => 10], 
    2 => ['name' => 'Produktivitas', 'weight' => 30],
    3 => ['name' => 'Disiplin', 'weight' => 20],
    4 => ['name' => 'Kerja Sama', 'weight' => 15],
    5 => ['name' => 'Kualitas Kerja', 'weight' => 25],
];

foreach ($updates as $id => $data) {
    $stmt = $pdo->prepare("UPDATE criteria SET name = ?, weight = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['weight'], $id]);
}

echo "Database updated successfully!";
?>
