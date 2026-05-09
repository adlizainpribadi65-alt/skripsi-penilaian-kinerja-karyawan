<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

try {
    // Fetch all employees who have enrolled faces
    $stmt = $pdo->query("SELECT id, name, nik, face_descriptor FROM employees WHERE face_descriptor IS NOT NULL");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode descriptors
    foreach ($data as &$row) {
        $row['face_descriptor'] = json_decode($row['face_descriptor']);
    }
    
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([]);
}
