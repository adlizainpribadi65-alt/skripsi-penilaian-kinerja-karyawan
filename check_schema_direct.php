<?php
require 'includes/db.php';
$stmt = $pdo->query("DESCRIBE employees");
file_put_contents('schema_direct.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
?>
