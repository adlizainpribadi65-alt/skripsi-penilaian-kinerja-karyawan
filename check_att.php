<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE attendance");
    file_put_contents('att_schema.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
} catch (Exception $e) {
    file_put_contents('att_schema.json', "ERROR: " . $e->getMessage());
}
?>
