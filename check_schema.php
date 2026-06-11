<?php
require_once 'includes/db.php';
$cols = $pdo->query("DESCRIBE scores")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
?>
