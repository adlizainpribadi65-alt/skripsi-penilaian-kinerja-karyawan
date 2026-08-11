<?php
require 'includes/db.php';

try {
    // 1. Check and add columns to industrial_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS industrial_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add missing columns if they don't exist
    $columns = [
        'type' => 'VARCHAR(10) DEFAULT "IN"',
        'shift' => 'VARCHAR(50) DEFAULT "Shift 1"',
        'status' => 'VARCHAR(50) DEFAULT "OK"'
    ];

    foreach ($columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE industrial_logs ADD COLUMN $col $def");
        } catch (PDOException $e) {
            // Might already exist, ignore
        }
    }

    // 2. Check and fix attendance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        date DATE NOT NULL,
        time_in TIME,
        time_out TIME,
        status VARCHAR(50),
        UNIQUE KEY employee_date (employee_id, date)
    )");

    echo "Database tables fixed successfully!";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
