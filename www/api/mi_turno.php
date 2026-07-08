<?php
// mi_turno.php - Autoservicio: custodio o coordinador de convivencia solicita su propio turno
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

// Verificamos el rol directo desde la BD (no confiamos en la sesión,
// que puede quedar desactualizada tras cambios de rol).
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
    echo json_encode(['error' => 'No autorizado para solicitar turnos']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['shift_date']) || empty($data['start_time'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fecha y hora de inicio son obligatorias']);
    exit;
}

try {
    $sql = "INSERT INTO shifts (caregiver_id, shift_date, start_time, end_time, location, minor_id, shift_type, status, assigned_by)
            VALUES (?, ?, ?, ?, ?, ?, 'base', 'pending', NULL)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $data['shift_date'],
        $data['start_time'],
        $data['end_time'] ?? '00:00:00',
        $data['location'] ?? '',
        $data['minor_id'] ?? '',
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
