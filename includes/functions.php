<?php
/**
 * Automatically initializes weekly attendance records (Monday to Saturday) for all employees with 'Absent' status.
 * Runs once a week (based on checking if the current Monday is already initialized).
 */
function initializeWeeklyAttendance($pdo)
{
    $today_dayofweek = date('N'); // 1 (Monday) to 7 (Sunday)
    $monday_time = time() - (($today_dayofweek - 1) * 86400);
    $monday = date('Y-m-d', $monday_time);
    
    // Check if the current week is already initialized for all employees
    $emp_count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE date = ?");
    $stmt->execute([$monday]);
    $attendance_count = $stmt->fetchColumn();
    
    if ($attendance_count >= $emp_count) {
        return; // Already initialized for all employees
    }
    
    // Generate dates for Monday to Saturday (6 days)
    $dates = [];
    for ($i = 0; $i < 6; $i++) {
        $dates[] = date('Y-m-d', $monday_time + ($i * 86400));
    }
    
    $employees = $pdo->query("SELECT id FROM employees")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($employees)) {
        return;
    }
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT IGNORE INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
        foreach ($employees as $emp_id) {
            foreach ($dates as $date) {
                $stmt->execute([$emp_id, $date]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Failed to initialize weekly attendance: " . $e->getMessage());
    }
}

/**
 * Synchronizes attendance percentage to the SAW Scores table.
 * Automatically creates the 'Absensi' criterion if it doesn't exist.
 */
function syncAttendanceToSAW($pdo)
{
    try {
        // 1. Ensure "Absensi" criterion exists
        $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name LIKE '%Absensi%' OR name LIKE '%Attendance%' OR name LIKE '%Kehadiran%' LIMIT 1");
        $stmt->execute();
        $crit = $stmt->fetch();
 
        if (!$crit) {
            // Create default Absensi criterion as BENEFIT
            $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES ('Absensi', 10.00, 'benefit')");
            $stmt->execute();
            $crit_id = $pdo->lastInsertId();
        } else {
            $crit_id = $crit['id'];
            // Force update existing criterion to benefit if it was cost
            $pdo->prepare("UPDATE criteria SET type = 'benefit' WHERE id = ? AND type = 'cost'")->execute([$crit_id]);
        }

 
        // 2. Calculate percentages for each employee based on the CURRENT WEEK ONLY
        // Get Monday of current week
        $today_dayofweek = date('N');
        $monday = date('Y-m-d', time() - (($today_dayofweek - 1) * 86400));

        $stmt = $pdo->prepare("SELECT 
                                e.id as emp_id,
                                COUNT(CASE WHEN a.status IN ('Present', 'Late') THEN 1 END) as present_count,
                                COUNT(a.id) as total_days
                             FROM employees e
                             LEFT JOIN attendance a ON e.id = a.employee_id AND a.date >= ?
                             GROUP BY e.id");
        $stmt->execute([$monday]);
        $stats = $stmt->fetchAll();


        // 3. Update scores table
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("INSERT INTO scores (employee_id, criteria_id, score) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE score = VALUES(score)");

        foreach ($stats as $row) {
            // If criterion is cost, maybe score should be absence rate. 
            // We'll calculate the absence rate if the type is cost, otherwise presence rate.
            // Let's fetch the type of the criterion
            $typeStmt = $pdo->prepare("SELECT type FROM criteria WHERE id = ?");
            $typeStmt->execute([$crit_id]);
            $crit_type = $typeStmt->fetchColumn();

            // Always use presence rate since we now force the criterion to be benefit
            $score = ($row['total_days'] > 0) ? ($row['present_count'] / $row['total_days']) * 100 : 0;
            
            $updateStmt->execute([$row['emp_id'], $crit_id, $score]);

        }
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("Sync Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Initializes default scores for a new employee across all criteria.
 * This prevents 'Incomplete Dataset' errors in the SAW engine.
 */
function initializeEmployeeScores($pdo, $emp_id)
{
    try {
        $criteria = $pdo->query("SELECT id FROM criteria")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT IGNORE INTO scores (employee_id, criteria_id, score) VALUES (?, ?, ?)");
        foreach ($criteria as $crit_id) {
            $stmt->execute([$emp_id, $crit_id, 0]); // Default score 0
        }
        return true;
    } catch (Exception $e) {
        error_log("Init Score Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Synchronizes daily production achievements (units produced) to the SAW Scores table.
 * Automatically creates the 'Hasil Produksi' criterion if it doesn't exist.
 */
function syncProductionToSAW($pdo)
{
    try {
        // 1. Ensure "Produktivitas" criterion exists, search for Produksi or Productivity
        $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name LIKE '%Produksi%' OR name LIKE '%Produktivitas%' OR name LIKE '%Productivity%' LIMIT 1");
        $stmt->execute();
        $crit = $stmt->fetch();

        if (!$crit) {
            // Create default Produktivitas criterion
            $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES ('Produktivitas', 20.00, 'benefit')");
            $stmt->execute();
            $crit_id = $pdo->lastInsertId();
        } else {
            $crit_id = $crit['id'];
            // Force rename to Produktivitas for consistency
            $pdo->prepare("UPDATE criteria SET name = 'Produktivitas' WHERE id = ?")->execute([$crit_id]);
        }


        // 2. Calculate total production for each employee
        $stmt = $pdo->query("SELECT 
                                e.id as emp_id,
                                IFNULL(SUM(p.quantity), 0) as total_produced
                             FROM employees e
                             LEFT JOIN production_logs p ON e.id = p.employee_id
                             GROUP BY e.id");
        $stats = $stmt->fetchAll();

        // 3. Update scores table
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("INSERT INTO scores (employee_id, criteria_id, score) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE score = VALUES(score)");

        foreach ($stats as $row) {
            $updateStmt->execute([$row['emp_id'], $crit_id, (float) $row['total_produced']]);
        }
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("Production Sync Error: " . $e->getMessage());
        return false;
    }
}
?>