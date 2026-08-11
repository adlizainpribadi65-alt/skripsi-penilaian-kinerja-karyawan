<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT id, name FROM criteria');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

// target weights
$t_w = [
    'Presensi' => 10,
    'Produktivitas' => 30,
    'Disiplin' => 20,
    'Kerja Sama' => 15,
    'Kualitas Kerja' => 25
];

// force these weights in criteria table
foreach ($res as $row) {
    if (isset($t_w[$row['name']])) {
        $pdo->query("UPDATE criteria SET weight = " . $t_w[$row['name']] . " WHERE id = " . $row['id']);
    }
}
echo "Synced targets directly to DB criteria.";
