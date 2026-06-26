<?php
session_start();
// dashboard_stats.php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();

// Total de cuidadores (excluyendo admins)
$total = $pdo->query("SELECT COUNT(*) FROM caregivers WHERE role != 'admin'")->fetchColumn();

// Turnos de hoy
$today = date('Y-m-d');
$todayShifts = $pdo->query("SELECT COUNT(*) FROM shifts WHERE shift_date = '$today'")->fetchColumn();

// Activos ahora (en progreso)
$activeNow = $pdo->query("SELECT COUNT(*) FROM shifts WHERE status = 'in_progress'")->fetchColumn();

// Alertas pendientes
$alerts = $pdo->query("SELECT COUNT(*) FROM alerts WHERE is_read = 0")->fetchColumn();

// Gastos pendientes
$expenses = $pdo->query("SELECT COUNT(*) FROM caregiver_expenses WHERE status = 'pending'")->fetchColumn();

// Solicitudes de cambio pendientes
$requests = $pdo->query("SELECT COUNT(*) FROM shift_requests WHERE status = 'pending'")->fetchColumn();

// Turnos completados hoy
$completed = $pdo->query("SELECT COUNT(*) FROM shifts WHERE shift_date = '$today' AND status = 'completed'")->fetchColumn();

echo json_encode([
    'totalCaregivers' => (int)$total,
    'todayShifts' => (int)$todayShifts,
    'activeNow' => (int)$activeNow,
    'pendingAlerts' => (int)$alerts,
    'pendingExpenses' => (int)$expenses,
    'pendingRequests' => (int)$requests,
    'completedToday' => (int)$completed
]);
?>