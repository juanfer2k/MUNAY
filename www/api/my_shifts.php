<?php
// my_shifts.php - Obtener turnos del cuidador autenticado con patrón y sección
session_start();
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

// Obtener turnos del día con patrón y sección
$stmt = $pdo->prepare("
    SELECT 
        s.id, 
        s.shift_date, 
        s.start_time, 
        s.end_time, 
        s.location, 
        s.status, 
        s.minor_id,
        s.pattern_code,
        sec.name AS section_name,
        sp.description AS pattern_description
    FROM shifts s
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN shift_patterns sp ON s.pattern_code = sp.code
    WHERE s.caregiver_id = ? AND s.shift_date = ?
    ORDER BY s.start_time
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