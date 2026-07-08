<?php
// api/admin_stats.php
// Devuelve contadores y actividad reciente para el panel admin

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/config.php';
$pdo = getDB();

// Contadores
$users    = $pdo->query("SELECT COUNT(*) FROM caregivers")->fetchColumn();
$shifts   = $pdo->query("SELECT COUNT(*) FROM shifts WHERE shift_date = CURDATE()")->fetchColumn();

// Gastos pendientes (tabla puede no existir aún)
try {
    $expenses = $pdo->query("SELECT COUNT(*) FROM expenses WHERE status = 'pending'")->fetchColumn();
} catch(Exception $e) { $expenses = 0; }

// Cambios pendientes
try {
    $changes = $pdo->query("SELECT COUNT(*) FROM shift_change_requests WHERE status = 'pending'")->fetchColumn();
} catch(Exception $e) { $changes = 0; }

// Actividad reciente: últimos 20 eventos combinados
$recent = [];

// Turnos recientes
try {
    $stmt = $pdo->query("
        SELECT 'shift' AS type,
               CONCAT('Turno ', s.status) AS description,
               c.name AS user,
               s.created_at AS date
        FROM shifts s
        INNER JOIN caregivers c ON c.id = s.caregiver_id
        ORDER BY s.created_at DESC LIMIT 7
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $recent[] = $r;
} catch(Exception $e) {}

// Gastos recientes
try {
    $stmt = $pdo->query("
        SELECT 'expense' AS type,
               CONCAT('$', FORMAT(amount,0), ' – ', type) AS description,
               c.name AS user,
               e.created_at AS date
        FROM expenses e
        INNER JOIN caregivers c ON c.id = e.caregiver_id
        ORDER BY e.created_at DESC LIMIT 7
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $recent[] = $r;
} catch(Exception $e) {}

// Cambios de turno recientes
try {
    $stmt = $pdo->query("
        SELECT 'change' AS type,
               CONCAT('Cambio ', scr.status) AS description,
               c.name AS user,
               scr.created_at AS date
        FROM shift_change_requests scr
        INNER JOIN caregivers c ON c.id = scr.caregiver_id
        ORDER BY scr.created_at DESC LIMIT 6
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $recent[] = $r;
} catch(Exception $e) {}

// Ordenar por fecha descendente
usort($recent, function($a, $b){
    return strcmp($b['date'], $a['date']);
});
$recent = array_slice($recent, 0, 15);

echo json_encode([
    'users'    => (int)$users,
    'shifts'   => (int)$shifts,
    'expenses' => (int)$expenses,
    'changes'  => (int)$changes,
    'recent'   => $recent
]);
?>
