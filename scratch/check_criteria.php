<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM criteria");
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($criteria);
