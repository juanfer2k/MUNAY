<?php
// alerts_log.php - Historial completo de alertas con timestamps, de solo lectura.
// A diferencia de alerts.php (usado por el polling), este NUNCA marca alertas como leídas.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$pdo = getDB();
$sql = "SELECT a.id, a.message, a.type, a.origin, a.color, a.is_read, a.created_at,
               c.name AS caregiver_name
        FROM alerts a
        LEFT JOIN caregivers c ON a.caregiver_id = c.id
        ORDER BY a.created_at DESC
        LIMIT 500";
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
