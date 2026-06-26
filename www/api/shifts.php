<?php
session_start();
// shifts.php - Lista de turnos con teléfono del cuidador
header('Content-Type: application/json');
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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
echo json_encode($shifts);
?>