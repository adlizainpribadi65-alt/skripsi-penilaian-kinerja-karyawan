<?php
require 'includes/db.php';
try {
    $emp_id = 3;
    $today = date('Y-m-d');
    
    echo "1. employee...\n";
    $stmt = $pdo->prepare("SELECT id, name FROM employees WHERE nik = ?");
    $stmt->execute(['ID-001']);
    
    echo "2. industrial_logs SELECT type...\n";
    $stmt = $pdo->prepare("SELECT type FROM industrial_logs WHERE employee_id = ? AND DATE(timestamp) = ? ORDER BY timestamp DESC LIMIT 1");
    $stmt->execute([$emp_id, $today]);
    
    echo "3. industrial_logs INSERT...\n";
    $stmt = $pdo->prepare("INSERT INTO industrial_logs (employee_id, type, shift, status) VALUES (?, ?, ?, 'OK')");
    $stmt->execute([$emp_id, 'IN', 'Shift 1']);
    
    echo "4. syncAttendanceToSAW...\n";
    // Inline sync function subset
    $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name LIKE '%Absensi%' OR name LIKE '%Attendance%' OR name LIKE '%Kehadiran%' LIMIT 1");
    $stmt->execute();
    $crit = $stmt->fetch();
    echo "Done script.\n";
} catch (Exception $e) {
    echo "\nFAILED!\n" . $e->getMessage();
}
?>
