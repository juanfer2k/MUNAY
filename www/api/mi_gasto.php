<?php
// mi_gasto.php - Autoservicio: custodio o coordinador de convivencia reporta su propio gasto
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Verificamos el rol directo desde la BD (no confiamos en la sesión).
$stmt = $pdo->prepare("SELECT role, pattern_code, is_coordinator FROM caregivers WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$esCustodio = $me['role'] === 'custodio';
$esCoordinadorConvivencia = $me['role'] === 'coordinator' && ($me['pattern_code'] === 'CONV' || (int)$me['is_coordinator'] === 1);

if (!$esCustodio && !$esCoordinadorConvivencia) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado para reportar gastos']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tiposValidos = ['parking', 'transport', 'food', 'toll', 'other'];
$type = in_array($data['type'] ?? '', $tiposValidos, true) ? $data['type'] : 'other';
$amount = isset($data['amount']) ? (float)$data['amount'] : 0;

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Monto inválido']);
    exit;
}

try {
    $sql = "INSERT INTO caregiver_expenses (caregiver_id, type, amount, description, status)
            VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $type,
        $amount,
        $data['description'] ?? '',
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
