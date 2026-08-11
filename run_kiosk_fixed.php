<?php
require 'includes/db.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nik'] = '1';

ob_start();
require 'industrial/process_kiosk.php';
$output = ob_get_clean();

file_put_contents('kiosk_output_fixed.json', $output);
?>
