<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $today_dayofweek = date('N'); // 1 (Monday) to 7 (Sunday)
    $monday_time = time() - (($today_dayofweek - 1) * 86400);
    $monday = date('Y-m-d', $monday_time);
    
    echo "Current local time: " . date('Y-m-d H:i:s') . "\n";
    echo "Calculated Monday of this week: $monday\n";
    
    $emp_count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    echo "Total employees: $emp_count\n";
    
    $emp_ids = $pdo->query("SELECT id, name FROM employees")->fetchAll();
    echo "Employees list:\n";
    foreach ($emp_ids as $e) {
         echo " - ID: {$e['id']}, Name: {$e['name']}\n";
    }
    
    $monday_records = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = '$monday'")->fetchColumn();
    echo "Records for Monday ($monday): $monday_records\n";
    
    $all_this_week = $pdo->query("SELECT * FROM attendance WHERE date >= '$monday'")->fetchAll();
    echo "Attendance records from Monday onwards (" . count($all_this_week) . " records):\n";
    foreach ($all_this_week as $row) {
         echo " - EmpID: {$row['employee_id']}, Date: {$row['date']}, Status: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
