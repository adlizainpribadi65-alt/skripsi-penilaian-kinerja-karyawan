<?php
require 'includes/db.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nik'] = '1';
require 'industrial/process_kiosk.php';
?>
