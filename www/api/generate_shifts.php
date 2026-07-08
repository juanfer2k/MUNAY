<?php
// generate_shifts.php - Genera los turnos de la semana para todos los custodios/coordinadores
// según su pattern_code (shift_patterns). No duplica turnos ya existentes ese día.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$adminId = $_SESSION['user_id'];

// ===== Determinar el lunes de la semana a generar =====
$weekStartParam = $_GET['week_start'] ?? '';
try {
    if ($weekStartParam) {
        $monday = new DateTime($weekStartParam);
    } else {
        $monday = new DateTime('now');
    }
    // Nos aseguramos de que sea lunes, sin importar qué día venga en el parámetro.
    $isoDow = (int)$monday->format('N'); // 1=lunes ... 7=domingo
    if ($isoDow !== 1) {
        $monday->modify('-' . ($isoDow - 1) . ' days');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Fecha de semana inválida']);
    exit;
}

// ===== Cargar patrones =====
$patronesRaw = $pdo->query("SELECT code, weekday_schedule, weekend_schedule, rest_rules FROM shift_patterns")->fetchAll(PDO::FETCH_ASSOC);
$patrones = [];
foreach ($patronesRaw as $p) {
    $patrones[$p['code']] = [
        'weekday' => json_decode($p['weekday_schedule'], true) ?: [],
        'weekend' => json_decode($p['weekend_schedule'], true) ?: null,
        'rest' => json_decode($p['rest_rules'] ?? 'null', true) ?: null,
    ];
}

// ===== Cargar custodios y coordinadores con patrón asignado =====
$personas = $pdo->query("
    SELECT id, pattern_code, section_id
    FROM caregivers
    WHERE role IN ('custodio', 'coordinator') AND pattern_code IS NOT NULL AND pattern_code != ''
")->fetchAll(PDO::FETCH_ASSOC);

$stmtExiste = $pdo->prepare("SELECT id FROM shifts WHERE caregiver_id = ? AND shift_date = ? LIMIT 1");
$stmtInsertar = $pdo->prepare("
    INSERT INTO shifts (caregiver_id, shift_date, start_time, end_time, shift_type, location, minor_id, status, section_id, assigned_by, pattern_code)
    VALUES (?, ?, ?, ?, 'base', '', '', 'pending', ?, ?, ?)
");

$creados = 0;
$omitidos = 0;
$errores = [];

foreach ($personas as $persona) {
    $codigo = $persona['pattern_code'];
    if (!isset($patrones[$codigo])) {
        $errores[] = "Custodio ID {$persona['id']}: patrón '{$codigo}' no existe en shift_patterns";
        continue;
    }
    $patron = $patrones[$codigo];

    for ($i = 0; $i < 7; $i++) {
        $fecha = (clone $monday)->modify("+{$i} days");
        $diaIso = (int)$fecha->format('N'); // 1..7
        $fechaStr = $fecha->format('Y-m-d');

        if ($diaIso <= 5) {
            $horario = $patron['weekday'][(string)$diaIso] ?? null;
            if (!$horario) {
                continue; // el patrón no define ese día laboral
            }
            $inicio = $horario['start'];
            $fin = $horario['end'];

            // Reglas de descanso: viernes corto / lunes tarde
            if ($diaIso === 5 && !empty($patron['rest']['friday_early_end'])) {
                $fin = $patron['rest']['friday_early_end'];
            }
            if ($diaIso === 1 && !empty($patron['rest']['monday_late_start'])) {
                $inicio = $patron['rest']['monday_late_start'];
            }
        } else {
            if (!$patron['weekend']) {
                continue; // el patrón no define fin de semana
            }
            $inicio = $patron['weekend']['start'];
            $fin = $patron['weekend']['end'];
        }

        // No duplicar: si ya existe un turno ese día para ese custodio, se omite.
        $stmtExiste->execute([$persona['id'], $fechaStr]);
        if ($stmtExiste->fetch()) {
            $omitidos++;
            continue;
        }

        try {
            $stmtInsertar->execute([
                $persona['id'],
                $fechaStr,
                $inicio,
                $fin,
                $persona['section_id'],
                $adminId,
                $codigo,
            ]);
            $creados++;
        } catch (PDOException $e) {
            $errores[] = "Custodio ID {$persona['id']} ({$fechaStr}): " . $e->getMessage();
        }
    }
}

echo json_encode([
    'success' => true,
    'created' => $creados,
    'skipped' => $omitidos,
    'errors' => $errores,
    'week_start' => $monday->format('Y-m-d'),
]);
?>
