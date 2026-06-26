<?php
// police_update_shift.php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'police' && $_SESSION['user_role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$shift_id = $data['shift_id'] ?? 0;

if (!$shift_id) {
    echo json_encode(['error' => 'ID de turno requerido']);
    exit;
}

$pdo = getDB();
$updates = [];
$params = [];

$allowed_fields = ['estimated_pickup', 'estimated_dropoff', 'actual_pickup', 'actual_dropoff', 'police_notes', 'status'];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $updates[] = "$field = ?";
        $params[] = $data[$field];
    }
}

if (empty($updates)) {
    echo json_encode(['error' => 'Sin campos para actualizar']);
    exit;
}

$params[] = $shift_id;
$sql = "UPDATE shifts SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Obtener nombre del cuidador para la alerta
$caregiver = $pdo->query("SELECT name FROM caregivers c JOIN shifts s ON c.id = s.caregiver_id WHERE s.id = $shift_id")->fetchColumn();

// Crear alerta
$alert_msg = "🚔 Policía actualizó el turno #$shift_id ($caregiver)";
if (isset($data['actual_pickup'])) {
    $alert_msg = "🚔 Menor recogido en turno #$shift_id ($caregiver)";
}
if (isset($data['status']) && $data['status'] === 'completed') {
    $alert_msg = "✅ Turno #$shift_id ($caregiver) completado según policía";
}

$pdo->prepare("INSERT INTO alerts (shift_id, message, type, origin, color) VALUES (?, ?, 'warning', 'police', '#f39c12')")
    ->execute([$shift_id, $alert_msg]);

// Notificar a administradores
$tokens = $pdo->query("SELECT token FROM device_tokens dt JOIN caregivers c ON dt.caregiver_id = c.id WHERE c.role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($tokens)) {
    require_once 'firebase_config.php';
    sendPushToMultiple($tokens, "🚔 Actualización Policial", $alert_msg, ['shift_id' => $shift_id]);
}

echo json_encode(['success' => true]);
?>