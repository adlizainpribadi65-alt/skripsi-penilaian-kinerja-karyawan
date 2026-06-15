<?php
require __DIR__ . '/../includes/db.php';
// Menghapus data dummy (biasanya memiliki ID tinggi atau NIK kosong)
$count = $pdo->exec("DELETE FROM employees WHERE id > 20 OR nik IS NULL OR nik = ''");
echo "Berhasil menghapus $count data dummy.\n";
