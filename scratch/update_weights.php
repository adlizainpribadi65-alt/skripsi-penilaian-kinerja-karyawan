<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();

    // Map existing IDs (from previous check: 3, 4, 5)
    // 3 -> Absensi (10, cost)
    // 4 -> Produktivitas (30, benefit)
    // 5 -> Kualitas Kerja (25, benefit)
    
    $stmt = $pdo->prepare("UPDATE criteria SET name = ?, weight = ?, type = ? WHERE id = ?");
    $stmt->execute(['Absensi', 10.00, 'cost', 3]);
    $stmt->execute(['Produktivitas', 30.00, 'benefit', 4]);
    $stmt->execute(['Kualitas Kerja', 25.00, 'benefit', 5]);

    // Insert new criteria: Disiplin (20, benefit), Kerjasama (15, benefit)
    $stmtInsert = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES (?, ?, ?)");
    $stmtInsert->execute(['Disiplin', 20.00, 'benefit']);
    $stmtInsert->execute(['Kerjasama', 15.00, 'benefit']);

    $pdo->commit();
    echo "Criteria updated successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}
