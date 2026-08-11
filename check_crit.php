<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE criteria");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('criteria_schema.json', json_encode($result, JSON_PRETTY_PRINT));
} catch (Exception $e) {
    file_put_contents('criteria_schema.json', "ERROR: " . $e->getMessage());
}
?>
