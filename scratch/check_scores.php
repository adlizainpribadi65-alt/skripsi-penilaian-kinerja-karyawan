<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Running syncAttendanceToSAW...\n";
    $success = syncAttendanceToSAW($pdo);
    echo "Sync status: " . ($success ? "SUCCESS" : "FAILED") . "\n\n";

    echo "Absensi scores for each employee (Criterion ID = 3):\n";
    $stmt = $pdo->query("SELECT s.*, e.name FROM scores s JOIN employees e ON s.employee_id = e.id WHERE s.criteria_id = 3 ORDER BY e.name ASC");
    $scores = $stmt->fetchAll();
    
    foreach ($scores as $s) {
        // Let's also fetch total days and present count from attendance to show the raw values
        $stmt_raw = $pdo->prepare("SELECT 
                                    COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) as present_count,
                                    COUNT(id) as total_days
                                 FROM attendance 
                                 WHERE employee_id = ?");
        $stmt_raw->execute([$s['employee_id']]);
        $raw = $stmt_raw->fetch();
        
        echo "- Employee: {$s['name']} (ID: {$s['employee_id']}) -> Present: {$raw['present_count']}/{$raw['total_days']} days, Score: {$s['score']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
