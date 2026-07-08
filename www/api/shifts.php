<?php
session_start();
// shifts.php - Lista de turnos con teléfono del cuidador
header('Content-Type: application/json');
require_once 'config.php';


if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'nursing'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$sql = "SELECT s.*, c.name as caregiver_name, c.phone as caregiver_phone
        FROM shifts s 
        LEFT JOIN caregivers c ON s.caregiver_id = c.id 
        ORDER BY s.shift_date DESC, s.start_time";
$stmt = $pdo->query($sql);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula el estado real (pendiente / en curso / completado) según fecha y hora,
// sin modificar lo guardado en BD. Los 'cancelled' nunca se sobreescriben.
function calcularEstadoTurno($shift_date, $start_time, $end_time, $estadoActual) {
    if ($estadoActual === 'cancelled') {
        return $estadoActual;
    }
    try {
        $tz = new DateTimeZone('America/Bogota');
        $inicio = new DateTime($shift_date . ' ' . $start_time, $tz);
        $fin = new DateTime($shift_date . ' ' . ($end_time ?: '23:59:59'), $tz);
        if ($fin <= $inicio) {
            $fin->modify('+1 day'); // turno nocturno que cruza medianoche
        }
        $ahora = new DateTime('now', $tz);
        if ($ahora < $inicio) return 'pending';
        if ($ahora <= $fin) return 'in_progress';
        return 'completed';
    } catch (Exception $e) {
        return $estadoActual;
    }
}

foreach ($shifts as &$s) {
    $s['status'] = calcularEstadoTurno($s['shift_date'], $s['start_time'], $s['end_time'], $s['status']);
}
unset($s);

echo json_encode($shifts);
?>