<?php
// report_expense.php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Datos invalidos']);
    exit;
}

$required = ['type', 'amount'];
foreach ($required as $f) {
    if (!isset($data[$f])) {
        echo json_encode(['error' => "Falta campo: $f"]);
        exit;
    }
}

$pdo = getDB();
$sql = "INSERT INTO caregiver_expenses (caregiver_id, shift_id, type, amount, description) VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_SESSION['user_id'],
    $data['shift_id'] ?? null,
    $data['type'],
    $data['amount'],
    $data['description'] ?? ''
]);

// Alerta SIN emojis (texto plano)
$msg = "Gasto reportado: $" . $data['amount'] . " - " . ($data['description'] ?? $data['type']);
$pdo->prepare("INSERT INTO alerts (caregiver_id, message, type, origin, color) VALUES (?, ?, 'info', 'caregiver', '#f1c40f')")
    ->execute([$_SESSION['user_id'], $msg]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
?>