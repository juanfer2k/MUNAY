<?php
// login_test.php - PRUEBA DE LOGIN SIN SESIONES
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email y contraseña requeridos']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, email, role, password FROM caregivers WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$password_valid = password_verify($password, $user['password']);

echo json_encode([
    'user' => $user,
    'password_verify_result' => $password_valid,
    'hash_from_db' => $user['password'],
    'debug' => 'Verifica que el hash en la BD sea correcto'
]);
?>