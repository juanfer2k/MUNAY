<?php
// calcular_horario.php - Dado un custodio y una fecha, calcula hora inicio/fin
// según su pattern_code y las rest_rules, igual que generate_shifts.php.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$caregiverId = $_GET['caregiver_id'] ?? '';
$fechaStr = $_GET['fecha'] ?? '';

if (empty($caregiverId) || empty($fechaStr)) {
    http_response_code(400);
    echo json_encode(['error' => 'caregiver_id y fecha son requeridos']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT pattern_code FROM caregivers WHERE id = ?");
$stmt->execute([$caregiverId]);
$patternCode = $stmt->fetchColumn();

if (!$patternCode) {
    echo json_encode(['found' => false, 'error' => 'Este custodio no tiene patrón asignado']);
    exit;
}

$stmt = $pdo->prepare("SELECT weekday_schedule, weekend_schedule, rest_rules FROM shift_patterns WHERE code = ?");
$stmt->execute([$patternCode]);
$patron = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patron) {
    echo json_encode(['found' => false, 'error' => "El patrón '{$patternCode}' no existe en shift_patterns"]);
    exit;
}

try {
    $fecha = new DateTime($fechaStr);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Fecha inválida']);
    exit;
}

$diaIso = (int)$fecha->format('N'); // 1=lunes ... 7=domingo
$weekday = json_decode($patron['weekday_schedule'], true) ?: [];
$weekend = json_decode($patron['weekend_schedule'] ?? 'null', true);
$rest = json_decode($patron['rest_rules'] ?? 'null', true);

if ($diaIso <= 5) {
    $horario = $weekday[(string)$diaIso] ?? null;
    if (!$horario) {
        echo json_encode(['found' => false, 'error' => 'El patrón no define horario para ese día']);
        exit;
    }
    $inicio = $horario['start'];
    $fin = $horario['end'];

    if ($diaIso === 5 && !empty($rest['friday_early_end'])) {
        $fin = $rest['friday_early_end'];
    }
    if ($diaIso === 1 && !empty($rest['monday_late_start'])) {
        $inicio = $rest['monday_late_start'];
    }
} else {
    if (!$weekend) {
        echo json_encode(['found' => false, 'error' => 'El patrón no define horario de fin de semana']);
        exit;
    }
    $inicio = $weekend['start'];
    $fin = $weekend['end'];
}

echo json_encode([
    'found' => true,
    'pattern_code' => $patternCode,
    'start_time' => $inicio,
    'end_time' => $fin,
]);
?>
