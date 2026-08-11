<?php
require 'includes/db.php';
$stmt = $pdo->query('DESCRIBE scores');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT * FROM scores');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
