<?php
// change_password.php - Cambiar contraseña
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';

if (empty($current) || empty($new)) {
    echo json_encode(['error' => 'Contraseña actual y nueva son requeridas']);
    exit;
}

if (strlen($new) < 6) {
    echo json_encode(['error' => 'La nueva contraseña debe tener al menos 6 caracteres']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT password FROM caregivers WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(['error' => 'Contraseña actual incorrecta']);
    exit;
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE caregivers SET password = ? WHERE id = ?");
$stmt->execute([$newHash, $user_id]);

echo json_encode(['success' => true]);
?>