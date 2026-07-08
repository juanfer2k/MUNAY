<?php
// police_locations.php - Ubicaciones de cuidadores para el mapa policial
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'police' && $_SESSION['user_role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();

$sql = "SELECT 
            c.id as caregiver_id,
            c.name,
            c.group_type,
            cl.lat,
            cl.lng,
            cl.last_update,
            s.id as shift_id,
            s.start_time as shift_start,
            s.end_time as shift_end,
            s.location,
            s.minor_id,
            s.status
        FROM caregivers c
        LEFT JOIN caregiver_locations cl ON c.id = cl.caregiver_id
        LEFT JOIN shifts s ON cl.shift_id = s.id AND s.status IN ('pending', 'in_progress')
        WHERE c.role != 'admin'
        AND cl.id IS NOT NULL
        ORDER BY c.group_type, c.name";

$stmt = $pdo->query($sql);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($result);
?>