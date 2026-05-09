<?php
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
            // Create default Absensi criterion
            $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES ('Absensi', 10.00, 'cost')");
            $stmt->execute();
            $crit_id = $pdo->lastInsertId();
        } else {
            $crit_id = $crit['id'];
        }

        // 2. Calculate percentages for each employee
        // Based on all logs in the 'attendance' table
        $stmt = $pdo->query("SELECT 
                                e.id as emp_id,
                                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                                COUNT(a.id) as total_days
                             FROM employees e
                             LEFT JOIN attendance a ON e.id = a.employee_id
                             GROUP BY e.id");
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

            if ($crit_type === 'cost') {
                $score = ($row['total_days'] > 0) ? (($row['total_days'] - $row['present_count']) / $row['total_days']) * 100 : 0;
            } else {
                $score = ($row['total_days'] > 0) ? ($row['present_count'] / $row['total_days']) * 100 : 0;
            }
            
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
 * Synchronizes inventory productivity (items handled) to the SAW Scores table.
 * Automatically creates the 'Produktivitas' criterion if it doesn't exist.
 */
function syncInventoryToSAW($pdo)
{
    try {
        // 1. Ensure "Produktivitas" criterion exists
        $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name LIKE '%Produktivitas%' OR name LIKE '%Productivity%' OR name LIKE '%Hasil Kerja%' LIMIT 1");
        $stmt->execute();
        $crit = $stmt->fetch();

        if (!$crit) {
            // Create default Productivity criterion
            $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES ('Produktivitas', 0.15, 'benefit')");
            $stmt->execute();
            $crit_id = $pdo->lastInsertId();
        } else {
            $crit_id = $crit['id'];
        }

        // 2. Calculate productivity for each employee
        // We count total items handled (both incoming and outgoing)
        $stmt = $pdo->query("SELECT 
                                e.id as emp_id,
                                (
                                    SELECT COUNT(*) FROM inventori_reggioella.barang_masuk bm WHERE bm.employee_id = e.id
                                ) + (
                                    SELECT COUNT(*) FROM inventori_reggioella.barang_keluar bk WHERE bk.employee_id = e.id
                                ) as total_handled
                             FROM employees e");
        $stats = $stmt->fetchAll();

        // 3. Update scores table
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("INSERT INTO scores (employee_id, criteria_id, score) 
                                     VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE score = VALUES(score)");

        foreach ($stats as $row) {
            // We use the count directly as the score. SAW normalization will handle the scaling.
            $updateStmt->execute([$row['emp_id'], $crit_id, (float) $row['total_handled']]);
        }
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("Inventory Sync Error: " . $e->getMessage());
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
        // 1. Ensure "Hasil Produksi" criterion exists
        $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name LIKE '%Produksi%' OR name LIKE '%Achievement%' LIMIT 1");
        $stmt->execute();
        $crit = $stmt->fetch();

        if (!$crit) {
            // Create default Production criterion
            $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES ('Hasil Produksi', 0.25, 'benefit')");
            $stmt->execute();
            $crit_id = $pdo->lastInsertId();
        } else {
            $crit_id = $crit['id'];
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