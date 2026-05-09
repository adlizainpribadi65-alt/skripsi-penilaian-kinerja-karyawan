<?php
require_once "c:/xampp/htdocs/inventori/config/koneksi.php";

$sql = "
    (SELECT kategori, model, warna, ukuran, jumlah, tanggal_masuk as tanggal, 'Masuk' as tipe, pengirim as dari, penerima as ke FROM barang_masuk)
    UNION ALL
    (SELECT kategori, model, warna, ukuran, jumlah, tanggal_keluar as tanggal, 'Keluar' as tipe, '-' as dari, penerima as ke FROM barang_keluar)
    ORDER BY tanggal DESC
";

$q = mysqli_query($conn, $sql);

if (!$q) {
    die("SQL Error: " . mysqli_error($conn));
} else {
    echo "Query Successful. Found " . mysqli_num_rows($q) . " rows.";
}
?>
