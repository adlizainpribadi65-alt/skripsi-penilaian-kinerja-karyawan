<?php
require_once 'c:/xampp/htdocs/dss-saw/includes/db.php';

try {
    // 1. Check current criteria
    $stmt = $pdo->query("SELECT * FROM criteria");
    $criteria = $stmt->fetchAll();
    
    echo "Current Criteria:\n";
    foreach ($criteria as $c) {
        echo "ID: {$c['id']} | Name: {$c['name']} | Weight: {$c['weight']}%\n";
    }
    echo "\nUpdating 'Kehadiran' weight to 70%...\n";

    // 2. Perform Update
    $stmt = $pdo->prepare("UPDATE criteria SET weight = 70 WHERE name LIKE '%Kehadiran%'");
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    if ($affected > 0) {
        echo "Successfully updated $affected row(s).\n";
    } else {
        echo "No criterion named 'Kehadiran' found to update.\n";
    }

    // 3. Final check
    $stmt = $pdo->query("SELECT * FROM criteria");
    $criteria = $stmt->fetchAll();
    echo "\nNew Criteria State:\n";
    foreach ($criteria as $c) {
        echo "ID: {$c['id']} | Name: {$c['name']} | Weight: {$c['weight']}%\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
