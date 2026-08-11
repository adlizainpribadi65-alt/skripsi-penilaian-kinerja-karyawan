<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM criteria");
foreach ($stmt->fetchAll() as $row) {
    echo $row['id'] . ' : ' . $row['name'] . "\n";
}
