<?php
// api/login_por_id.php - Login con ID de usuario (cédula) contra tabla usuarios_login
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// 1. CONFIGURACIÓN DE SESIÓN
// ============================================================
session_start([
    'cookie_lifetime' => 0,
    'cookie_secure'   => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// ============================================================
// 2. INCLUIR CONFIGURACIÓN Y CONEXIÓN A BD
// ============================================================
require_once 'config.php';

// ============================================================
// 3. OBTENER DATOS DEL REQUEST (JSON o FormData)
// ============================================================
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
    echo json_encode([
        'success' => false,
        'error' => 'El ID de usuario y la contraseña son obligatorios.'
    ]);
    exit;
}

// ============================================================
// 4. AUTENTICACIÓN CONTRA LA TABLA usuarios_login
// ============================================================
try {
    $pdo = getDB();

    // Buscar usuario por id_usuario
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
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas.']);
        exit;
    }

    // Verificar si la cuenta está bloqueada
    if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
        $tiempo_restante = ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
        echo json_encode(['success' => false, 'error' => "Cuenta bloqueada por {$tiempo_restante} minutos."]);
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        // Incrementar intentos fallidos
        $nuevos_intentos = ($user['intentos_fallidos'] ?? 0) + 1;
        if ($nuevos_intentos >= 5) {
            $bloqueo = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $update = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id_usuario = ?");
            $update->execute([$nuevos_intentos, $bloqueo, $id_usuario]);
            echo json_encode(['success' => false, 'error' => 'Demasiados intentos. Cuenta bloqueada 15 minutos.']);
        } else {
            $update = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = ? WHERE id_usuario = ?");
            $update->execute([$nuevos_intentos, $id_usuario]);
            echo json_encode(['success' => false, 'error' => 'Credenciales inválidas.']);
        }
        exit;
    }

    // Si llegamos aquí, credenciales correctas -> resetear intentos
    if ($user['intentos_fallidos'] > 0) {
        $reset = $pdo->prepare("UPDATE usuarios_login SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?");
        $reset->execute([$id_usuario]);
    }

    // Regenerar sesión por seguridad
    session_regenerate_id(true);

    // ============================================================
    // 5. MAPEAR ROLES DE usuarios_login A LOS QUE ESPERA EL FRONTEND
    // ============================================================
    $roleMap = [
        'admin'         => 'admin',
        'enfermeria'    => 'nursing',
        'custodio'      => 'custodio',
        'coordinacion'  => 'coordinator',
        'ponal'         => 'police'
    ];
    $roleFrontend = $roleMap[$user['rol']] ?? 'custodio';

    // Guardar datos en sesión
    $_SESSION['user_id']   = $user['id_usuario'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $roleFrontend;
    $_SESSION['user_id_num'] = $user['id_usuario']; // Para compatibilidad

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
    error_log("Error en login_por_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de conexión con la base de datos.']);
} catch (Exception $e) {
    error_log("Error en login_por_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error inesperado.']);
}