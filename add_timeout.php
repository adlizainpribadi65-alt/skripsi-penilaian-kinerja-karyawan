<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE attendance ADD COLUMN time_out TIME NULL");
    echo "Added time_out successfully!";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
