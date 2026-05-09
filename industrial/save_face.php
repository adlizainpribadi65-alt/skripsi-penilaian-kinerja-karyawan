<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
checkLogin();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['descriptor'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$id = $data['id'];
// Store descriptor as a JSON string
$descriptor = json_encode($data['descriptor']);

try {
    $stmt = $pdo->prepare("UPDATE employees SET face_descriptor = ? WHERE id = ?");
    $stmt->execute([$descriptor, $id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
