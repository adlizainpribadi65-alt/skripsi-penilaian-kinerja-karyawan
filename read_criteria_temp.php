<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, name FROM criteria ORDER BY id ASC");
$rows = $stmt->fetchAll();
file_put_contents('temp.json', json_encode($rows, JSON_PRETTY_PRINT));
