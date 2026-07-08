<?php
// mis_gastos.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, type, amount, description, status, created_at
    FROM caregiver_expenses 
    WHERE caregiver_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($expenses);
?>