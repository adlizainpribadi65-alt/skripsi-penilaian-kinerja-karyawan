<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE industrial_logs");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
