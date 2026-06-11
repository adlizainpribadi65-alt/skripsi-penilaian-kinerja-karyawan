<?php
// Set a mock POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Let's fetch an employee's NIK first
require_once __DIR__ . '/../includes/db.php';

$employees = $pdo->query("SELECT id, name, nik FROM employees")->fetchAll();
echo "Active Employees:\n";
foreach ($employees as $emp) {
    echo "- ID: {$emp['id']}, Name: {$emp['name']}, NIK: {$emp['nik']}\n";
}

if (empty($employees)) {
    echo "No employees to test with.\n";
    exit;
}

$test_emp = $employees[1]; // Let's use 'udi' (ID: 2) this time
$nik = $test_emp['nik'];
$_POST['nik'] = $nik;

echo "\n--- Simulating Kiosk scan IN for employee {$test_emp['name']} (NIK: $nik) ---\n";

ob_start();
include __DIR__ . '/../industrial/process_kiosk.php';
$output = ob_get_clean();

echo "Response from process_kiosk.php:\n";
echo $output . "\n";

// Check the database state for today using a fresh statement to prevent collisions
$today = date('Y-m-d');
$check_stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$check_stmt->execute([$test_emp['id'], $today]);
$record = $check_stmt->fetch();

echo "\nState in 'attendance' table today:\n";
print_r($record);

// Now let's try a scan OUT
echo "\n--- Simulating Kiosk scan OUT for employee {$test_emp['name']} (NIK: $nik) ---\n";
$_POST['nik'] = $nik;
ob_start();
include __DIR__ . '/../industrial/process_kiosk.php';
$output2 = ob_get_clean();
echo "Response from process_kiosk.php:\n";
echo $output2 . "\n";

// Check database again
$check_stmt->execute([$test_emp['id'], $today]);
$record2 = $check_stmt->fetch();
echo "\nState in 'attendance' table after scan OUT:\n";
print_r($record2);
