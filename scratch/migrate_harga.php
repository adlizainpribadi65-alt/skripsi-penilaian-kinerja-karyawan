<?php
require_once "c:/xampp/htdocs/inventori/config/koneksi.php";

$sql1 = "ALTER TABLE barang_masuk ADD COLUMN harga DECIMAL(15, 2) DEFAULT 0 AFTER jumlah";
$sql2 = "ALTER TABLE barang_keluar ADD COLUMN harga DECIMAL(15, 2) DEFAULT 0 AFTER jumlah";

if (mysqli_query($conn, $sql1)) {
    echo "Column 'harga' added to barang_masuk successfully.\n";
} else {
    echo "Error adding column to barang_masuk: " . mysqli_error($conn) . "\n";
}

if (mysqli_query($conn, $sql2)) {
    echo "Column 'harga' added to barang_keluar successfully.\n";
} else {
    echo "Error adding column to barang_keluar: " . mysqli_error($conn) . "\n";
}
?>
