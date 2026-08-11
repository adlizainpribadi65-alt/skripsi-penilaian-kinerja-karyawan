<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query('SELECT * FROM criteria');
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query('SELECT * FROM perbandingan_ahp');
    $perbandingan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query('SELECT * FROM bobot_ahp');
    $bobot = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query('SELECT e.id, e.name, s.criteria_id, s.score FROM employees e JOIN scores s ON e.id = s.employee_id');
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [
        'criteria' => $criteria,
        'perbandingan' => $perbandingan,
        'bobot' => $bobot,
        'scores' => $scores
    ];
    file_put_contents('scratch/output.json', json_encode($out, JSON_PRETTY_PRINT));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
