<?php
require_once 'includes/db.php';
try {
    // Add created_at if it doesn't exist
    $pdo->exec("ALTER TABLE scores ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Columns added or already exist.\n";
    
    // To support history, we need to allow multiple entries per employee/criteria but for different dates.
    // However, for now, let's just use created_at to filter the LATEST scores within a date range.
    // Actually, if the user explicitly wants weekly rekap, they probably want to see scores FROM that week.
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
