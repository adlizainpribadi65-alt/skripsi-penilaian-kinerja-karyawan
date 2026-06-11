<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

try {
    $today = date('Y-m-d');

    // 1. Get Summary Stats
    // Hadir
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status IN ('Present', 'Late')");
    $stmt->execute([$today]);
    $stats['hadir'] = $stmt->fetch()['count'];

    // Terlambat (after 08:30)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND status = 'Late'");
    $stmt->execute([$today]);
    $stats['terlambat'] = $stmt->fetch()['count'];

    // Total Employees
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
    $total_emp = $stmt->fetch()['count'];
    $stats['absen'] = max(0, $total_emp - $stats['hadir']);

    // Ditolak (from industrial_logs)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM industrial_logs WHERE DATE(timestamp) = ? AND status = 'DENIED'");
    $stmt->execute([$today]);
    $stats['ditolak'] = $stmt->fetch()['count'];

    // 2. Fetch Latest Logs (Main Table)
    $stmt = $pdo->query("
        SELECT a.*, e.name, e.nik, e.position 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        ORDER BY a.date DESC, a.id DESC 
        LIMIT 20
    ");
    $logs = $stmt->fetchAll();

    // 3. Fetch Kiosk Live Feed
    $stmt2 = $pdo->query("
        SELECT l.*, e.name, e.nik 
        FROM industrial_logs l 
        LEFT JOIN employees e ON l.employee_id = e.id 
        ORDER BY l.timestamp DESC 
        LIMIT 10
    ");
    $kiosk_scans = $stmt2->fetchAll();

    // 4. Chart Data (Last 7 Days)
    $stmt3 = $pdo->query("
        SELECT date, COUNT(*) as count 
        FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY date 
        ORDER BY date ASC
    ");
    $chart_data = $stmt3->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'logs' => $logs,
        'kiosk_scans' => $kiosk_scans,
        'chart_data' => $chart_data
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
