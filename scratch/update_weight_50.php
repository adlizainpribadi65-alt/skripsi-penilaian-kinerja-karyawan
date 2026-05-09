<?php
require_once 'c:/xampp/htdocs/dss-saw/includes/db.php';
try {
    $stmt = $pdo->prepare("UPDATE criteria SET weight = 50 WHERE name LIKE '%Kehadiran%'");
    $stmt->execute();
    echo "Successfully updated 'Kehadiran' weight to 50%.\n";
    
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
