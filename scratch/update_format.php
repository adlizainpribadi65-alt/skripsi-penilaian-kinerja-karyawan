<?php
require __DIR__ . '/../includes/db.php';
$count = $pdo->exec("UPDATE employees SET nik = CONCAT('ID-', nik) WHERE nik NOT LIKE 'ID-%' AND nik != '' AND nik IS NOT NULL");
echo "Updated $count existing records to have ID- prefix.\n";
