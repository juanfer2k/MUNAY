<?php
// ===== INICIO DE SESIÓN (YA CONFIGURADO EN CONFIG.PHP) =====
require_once 'config.php'; // Esto ya inicia la sesión
header('Content-Type: application/json');

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

if ($user && password_verify($password, $user['password'])) {
    // ===== GUARDAR SESIÓN =====
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    
    // Guardar sesión explícitamente
    session_write_close();
    
    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'name' => $user['name'],
        'id' => $user['id']
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales inválidas']);
}
?>