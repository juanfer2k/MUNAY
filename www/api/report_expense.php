<?php
// report_expense.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Datos inválidos']);
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

$expense_id = $pdo->lastInsertId();

// Notificar a administradores
$admin_tokens = $pdo->query("SELECT token FROM device_tokens dt JOIN caregivers c ON dt.caregiver_id = c.id WHERE c.role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
$msg = "💰 Gasto reportado: $" . $data['amount'] . " - " . ($data['description'] ?? $data['type']);

// Crear alerta
$pdo->prepare("INSERT INTO alerts (caregiver_id, message, type, origin, color) VALUES (?, ?, 'info', 'caregiver', '#f1c40f')")
    ->execute([$_SESSION['user_id'], $msg]);

echo json_encode(['success' => true, 'id' => $expense_id]);
?>