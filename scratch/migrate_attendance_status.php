<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Altering attendance table status column...\n";
    $sql = "ALTER TABLE attendance MODIFY COLUMN status ENUM('Present', 'Absent', 'Sick', 'Leave', 'Late') NOT NULL";
    $pdo->exec($sql);
    echo "Successfully updated attendance table schema.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
