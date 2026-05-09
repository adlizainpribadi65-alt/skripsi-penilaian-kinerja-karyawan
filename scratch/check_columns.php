<?php
require_once "c:/xampp/htdocs/inventori/config/koneksi.php";

foreach (['barang_masuk', 'barang_keluar'] as $table) {
    echo "Columns for $table:\n";
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($res)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";
}
?>
