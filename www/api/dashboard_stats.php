<?php
// dashboard_stats.php - Estadísticas del sistema con patrones y secciones
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();

// ===== ESTADÍSTICAS GENERALES =====

// Total de custodios (tarjeta "Custodios"): solo el rol custodio, no coordinador/enfermería/policía.
$totalCaregivers = $pdo->query("SELECT COUNT(*) FROM caregivers WHERE role = 'custodio'")->fetchColumn();

// Turnos de hoy
$today = date('Y-m-d');
$todayShifts = $pdo->query("SELECT COUNT(*) FROM shifts WHERE shift_date = '$today'")->fetchColumn();

// Activos ahora (tarjeta "Activos"): total de usuarios del sistema, de cualquier rol (incluye admin).
$activeNow = $pdo->query("SELECT COUNT(*) FROM caregivers")->fetchColumn();

// Alertas pendientes
$pendingAlerts = $pdo->query("SELECT COUNT(*) FROM alerts WHERE is_read = 0")->fetchColumn();

// Gastos pendientes
$pendingExpenses = $pdo->query("SELECT COUNT(*) FROM caregiver_expenses WHERE status = 'pending'")->fetchColumn();

// Solicitudes de cambio pendientes
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM shift_requests WHERE status = 'pending'")->fetchColumn();

// Turnos completados hoy
$completedToday = $pdo->query("SELECT COUNT(*) FROM shifts WHERE shift_date = '$today' AND status = 'completed'")->fetchColumn();

// ===== ESTADÍSTICAS POR PATRÓN =====

$patternStats = $pdo->query("
    SELECT 
        c.pattern_code,
        sp.description AS pattern_name,
        COUNT(*) AS total
    FROM caregivers c
    LEFT JOIN shift_patterns sp ON c.pattern_code = sp.code
    WHERE c.role != 'admin' AND c.pattern_code IS NOT NULL
    GROUP BY c.pattern_code
")->fetchAll(PDO::FETCH_ASSOC);

// ===== ESTADÍSTICAS POR SECCIÓN =====

$sectionStats = $pdo->query("
    SELECT 
        c.section_id,
        s.name AS section_name,
        COUNT(*) AS total
    FROM caregivers c
    LEFT JOIN sections s ON c.section_id = s.id
    WHERE c.role != 'admin' AND c.section_id IS NOT NULL
    GROUP BY c.section_id
")->fetchAll(PDO::FETCH_ASSOC);

// ===== ESTADÍSTICAS DE TURNOS POR PATRÓN (hoy) =====

$shiftPatternStats = $pdo->query("
    SELECT 
        s.pattern_code,
        sp.description AS pattern_name,
        COUNT(*) AS total
    FROM shifts s
    LEFT JOIN shift_patterns sp ON s.pattern_code = sp.code
    WHERE s.shift_date = '$today' AND s.pattern_code IS NOT NULL
    GROUP BY s.pattern_code
")->fetchAll(PDO::FETCH_ASSOC);

// ===== ESTADÍSTICAS DE TURNOS POR SECCIÓN (hoy) =====

$shiftSectionStats = $pdo->query("
    SELECT 
        s.section_id,
        sec.name AS section_name,
        COUNT(*) AS total
    FROM shifts s
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.shift_date = '$today' AND s.section_id IS NOT NULL
    GROUP BY s.section_id
")->fetchAll(PDO::FETCH_ASSOC);

// ===== CONSTRUIR RESPUESTA =====

echo json_encode([
    'totalCaregivers' => (int)$totalCaregivers,
    'todayShifts' => (int)$todayShifts,
    'activeNow' => (int)$activeNow,
    'pendingAlerts' => (int)$pendingAlerts,
    'pendingExpenses' => (int)$pendingExpenses,
    'pendingRequests' => (int)$pendingRequests,
    'completedToday' => (int)$completedToday,
    'patternStats' => $patternStats,
    'shiftPatternStats' => $shiftPatternStats,
    'sectionStats' => $sectionStats,
    'shiftSectionStats' => $shiftSectionStats
]);
?>
