<?php
// requests.php - Lista de solicitudes de cambio de turno
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();

// Verificar si la tabla shift_requests existe
try {
    $sql = "SELECT sr.*, 
                   s.id as shift_id,
                   c.name as caregiver_name
            FROM shift_requests sr
            LEFT JOIN shifts s ON sr.shift_id = s.id
            LEFT JOIN caregivers c ON s.caregiver_id = c.id
            ORDER BY sr.created_at DESC";
    $stmt = $pdo->query($sql);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($requests);
} catch (PDOException $e) {
    // Si la tabla no existe, devolvemos array vacío
    echo json_encode([]);
}
?>