<?php
require_once 'includes/db.php';

try {
    // 1. Create perbandingan_ahp table
    $sql1 = "CREATE TABLE IF NOT EXISTS perbandingan_ahp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kriteria_1 INT NOT NULL,
        id_kriteria_2 INT NOT NULL,
        nilai DECIMAL(10,4) NOT NULL,
        UNIQUE KEY(id_kriteria_1, id_kriteria_2)
    )";
    $pdo->exec($sql1);

    // 2. Create bobot_ahp table
    $sql2 = "CREATE TABLE IF NOT EXISTS bobot_ahp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kriteria INT NOT NULL UNIQUE,
        bobot DECIMAL(10,4) NOT NULL,
        ci DECIMAL(10,4),
        cr DECIMAL(10,4)
    )";
    $pdo->exec($sql2);

    // 3. Clear existing criteria (optional, but requested to have these 5). 
    $kriteria = [
        ['name' => 'Disiplin', 'type' => 'benefit', 'weight' => 20.00],
        ['name' => 'Produktivitas', 'type' => 'benefit', 'weight' => 20.00],
        ['name' => 'Kerja Sama', 'type' => 'benefit', 'weight' => 20.00],
        ['name' => 'Kualitas Kerja', 'type' => 'benefit', 'weight' => 20.00],
        ['name' => 'Absensi', 'type' => 'benefit', 'weight' => 20.00]
    ];

    foreach ($kriteria as $k) {
        $stmt = $pdo->prepare("SELECT id FROM criteria WHERE name = ?");
        $stmt->execute([$k['name']]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO criteria (name, type, weight) VALUES (?, ?, ?)");
            $insert->execute([$k['name'], $k['type'], $k['weight']]);
        }
    }

    echo "Database updated successfully.\n";

} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
