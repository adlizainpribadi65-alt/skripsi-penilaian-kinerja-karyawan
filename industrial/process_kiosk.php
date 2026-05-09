<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$nik = $_POST['nik'] ?? '';

if (empty($nik)) {
    echo json_encode(['success' => false, 'message' => 'ID Kosong']);
    exit;
}

try {
    // 1. Find Employee by NIK
    $stmt = $pdo->prepare("SELECT id, name FROM employees WHERE nik = ?");
    $stmt->execute([$nik]);
    $employee = $stmt->fetch();

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'ID Tidak Terdaftar']);
        exit;
    }

    $emp_id = $employee['id'];

    // 2. Determine Log Type (IN or OUT)
    // Rule: If last log today for this user was 'IN', then this is 'OUT'
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT type FROM industrial_logs WHERE employee_id = ? AND DATE(timestamp) = ? ORDER BY timestamp DESC LIMIT 1");
    $stmt->execute([$emp_id, $today]);
    $last_log = $stmt->fetch();

    $type = ($last_log && $last_log['type'] === 'IN') ? 'OUT' : 'IN';

    // 3. Determine Shift
    $hour = (int)date('H');
    $shift = "Shift 1";
    if ($hour >= 14 && $hour < 22) $shift = "Shift 2";
    if ($hour >= 22 || $hour < 6) $shift = "Shift 3";

    // 4. Record Log
    $stmt = $pdo->prepare("INSERT INTO industrial_logs (employee_id, type, shift, status) VALUES (?, ?, ?, 'OK')");
    $stmt->execute([$emp_id, $type, $shift]);

    // 5. Update the main 'attendance' table exactly representing the current scan
    $time_now = date('H:i:s');
    if ($type === 'IN') {
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, time_in) 
                               VALUES (?, ?, 'Present', ?) 
                               ON DUPLICATE KEY UPDATE status = 'Present', time_in = IF(time_in IS NULL, ?, time_in)");
        $stmt->execute([$emp_id, $today, $time_now, $time_now]);
    } else {
        $stmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE employee_id = ? AND date = ?");
        $stmt->execute([$time_now, $emp_id, $today]);
    }

    $message = ($type === 'IN') ? "SELAMAT BEKERJA" : "SAMPAI JUMPA";

    // 6. Instant Sync to DSS SAW performance engine
    // This ensures 'Kinerja Karyawan' is updated immediately after scan
    syncAttendanceToSAW($pdo);

    echo json_encode([
        'success' => true,
        'name' => $employee['name'],
        'message' => $type . ": " . $message . " (" . $shift . ")"
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>
