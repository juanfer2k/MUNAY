<?php
// calculate_payroll.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'auth_check.php';
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Solo administradores']);
    exit;
}

$pdo = getDB();
$config = $pdo->query("SELECT * FROM payroll_config ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$config) die(json_encode(['error' => 'Configuración de nómina no encontrada']));

$caregiver_id = $_GET['caregiver_id'] ?? 0;
$month = $_GET['month'] ?? date('Y-m');

if (!$caregiver_id) {
    echo json_encode(['error' => 'ID de cuidador requerido']);
    exit;
}

// Obtener todas las horas trabajadas (base + extras aprobadas)
$stmt = $pdo->prepare("
    SELECT 
        s.id as shift_id,
        s.start_time,
        s.end_time,
        s.shift_date,
        s.shift_type,
        o.hours as extra_hours,
        o.type as extra_type
    FROM shifts s
    LEFT JOIN overtime_requests o ON s.id = o.shift_id AND o.status = 'approved'
    WHERE s.caregiver_id = ? AND DATE_FORMAT(s.shift_date, '%Y-%m') = ?
");
$stmt->execute([$caregiver_id, $month]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_ordinary_hours = 0;
$total_overtime_125 = 0;
$total_overtime_175 = 0;

foreach ($shifts as $shift) {
    // Calcular horas ordinarias (hasta 8 horas diarias)
    $start = new DateTime($shift['shift_date'] . ' ' . $shift['start_time']);
    $end = new DateTime($shift['shift_date'] . ' ' . $shift['end_time']);
    if ($end < $start) $end->modify('+1 day');
    $diff = $start->diff($end);
    $hours = $diff->h + ($diff->i / 60);
    $total_ordinary_hours += min($hours, 8);
    
    // Si tiene horas extras aprobadas
    if ($shift['extra_hours']) {
        if ($shift['extra_type'] == 'nocturna') $total_overtime_175 += $shift['extra_hours'];
        else $total_overtime_125 += $shift['extra_hours'];
    }
}

$base_salary = $config['base_salary'];
$transport = $config['transport_subsidy'];
$health_ded = $base_salary * ($config['health_deduction'] / 100);
$pension_ded = $base_salary * ($config['pension_deduction'] / 100);
$hour_rate = $config['legal_hour_rate'];

$pay_ordinary = $total_ordinary_hours * $hour_rate;
$pay_overtime_125 = $total_overtime_125 * ($hour_rate * $config['overtime_rate_125']);
$pay_overtime_175 = $total_overtime_175 * ($hour_rate * $config['overtime_rate_175']);
$total_devengado = $pay_ordinary + $pay_overtime_125 + $pay_overtime_175 + $transport;
$total_deducciones = $health_ded + $pension_ded + $config['other_deductions'];
$total_pagar = $total_devengado - $total_deducciones;

echo json_encode([
    'caregiver_id' => $caregiver_id,
    'month' => $month,
    'ordinary_hours' => round($total_ordinary_hours, 2),
    'overtime_125' => round($total_overtime_125, 2),
    'overtime_175' => round($total_overtime_175, 2),
    'total_devengado' => round($total_devengado, 2),
    'total_deducciones' => round($total_deducciones, 2),
    'total_pagar' => round($total_pagar, 2),
    'detalle' => $shifts
]);
?>
