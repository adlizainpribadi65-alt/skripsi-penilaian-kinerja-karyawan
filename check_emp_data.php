<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, nik, name FROM employees");
file_put_contents('emp_data.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
?>
