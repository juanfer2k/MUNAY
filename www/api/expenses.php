<?php
session_start();
// expenses.php - Lista de gastos con teléfono
header('Content-Type: application/json');
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$sql = "SELECT e.*, c.name as caregiver_name, c.phone as caregiver_phone
        FROM caregiver_expenses e 
        LEFT JOIN caregivers c ON e.caregiver_id = c.id 
        ORDER BY e.created_at DESC";
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>