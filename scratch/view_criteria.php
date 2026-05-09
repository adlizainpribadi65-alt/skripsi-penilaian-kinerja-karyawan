<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM criteria");
$criteria = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($criteria, JSON_PRETTY_PRINT);
