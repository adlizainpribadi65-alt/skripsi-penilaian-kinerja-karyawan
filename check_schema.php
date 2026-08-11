<?php
require 'includes/db.php';
$stmt = $pdo->query("DESCRIBE employees");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
