<?php
require_once __DIR__ . '/../includes/db.php';

foreach (['attendance', 'industrial_logs'] as $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        echo $stmt->fetchColumn(1) . "\n\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}
