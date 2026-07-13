<?php
// api/login_por_id.php - Login con ID de usuario (cédula) contra tabla usuarios_login
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

session_start([
    'cookie_lifetime' => 0,
    'cookie_secure'   => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $errstr]);
    exit;
});

require_once 'config.php';

$inputData = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $inputData = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $inputData = $_POST;
}
if (empty($inputData) && isset($_GET['id_usuario'])) {
    $inputData = $_GET;
}

$id_usuario = isset($inputData['id_usuario']) ? trim($inputData['id_usuario']) : '';
$password   = isset($inputData['password']) ? trim($inputData['password']) : '';

if (empty($id_usuario) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID de usuario y la contraseña son obligatorios.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id_usuario, nombre, email, password_hash, rol, 
               intentos_fallidos, bloqueado_hasta, estado 
        FROM usuarios_login 
        WHERE id_usuario = ? AND estado = 1
        LIMIT 1
    ");
    $stmt->execute([$id_usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas.']);
        exit;
    }

    if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
        $restante = ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => "Cuenta bloqueada por {$restante} minutos."]);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $nuevos_intentos = ($user['intentos_fallidos'] ?? 0) + 1;
        if ($nuevos_intentos >= 5) {
            $bloqueo = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $update = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id_usuario = ?");
            $update->execute([$nuevos_intentos, $bloqueo, $id_usuario]);
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Demasiados intentos. Cuenta bloqueada 15 minutos.']);
        } else {
            $update = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = ? WHERE id_usuario = ?");
            $update->execute([$nuevos_intentos, $id_usuario]);
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Credenciales inválidas.']);
        }
        exit;
    }

    if ($user['intentos_fallidos'] > 0) {
        $reset = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
        $reset->execute([$id_usuario]);
    }

    session_regenerate_id(true);

    $roleMap = [
        'admin'         => 'admin',
        'enfermeria'    => 'nursing',
        'custodio'      => 'custodio',
        'coordinacion'  => 'coordinator',
        'ponal'         => 'police'
    ];
    $roleFrontend = $roleMap[$user['rol']] ?? 'custodio';

    $_SESSION['user_id']   = $user['id_usuario'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $roleFrontend;

    echo json_encode([
        'success' => true,
        'user' => [
            'id'    => $user['id_usuario'],
            'name'  => $user['nombre'],
            'email' => $user['email'],
            'role'  => $roleFrontend
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
} catch (Exception $e) {
    error_log("Error login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error inesperado.']);
}
