<?php
// my_shifts.php - Obtener turnos del cuidador autenticado
session_start(); // <-- Inicio de sesión obligatorio
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Obtener turnos del día
$stmt = $pdo->prepare("
    SELECT id, shift_date, start_time, end_time, location, status, minor_id
    FROM shifts 
    WHERE caregiver_id = ? AND shift_date = ?
    ORDER BY start_time
");
$stmt->execute([$user_id, $today]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener alertas no leídas del usuario
$stmt = $pdo->prepare("
    SELECT message FROM alerts 
    WHERE caregiver_id = ? AND is_read = 0
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'shifts' => $shifts,
    'alerts' => $alerts
]);
?>