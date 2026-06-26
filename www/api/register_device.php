<?php
// register_device.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'auth_check.php';

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
if (!$token) {
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("INSERT INTO device_tokens (caregiver_id, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), updated_at = CURRENT_TIMESTAMP");
$stmt->execute([$_SESSION['user_id'], $token]);

echo json_encode(['success' => true]);
?>
