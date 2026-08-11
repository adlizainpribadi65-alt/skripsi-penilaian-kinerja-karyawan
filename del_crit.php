<?php
require 'includes/db.php';
$pdo->exec("DELETE FROM criteria WHERE name='Absensi'");
echo "Deleted";
