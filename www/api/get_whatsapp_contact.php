<?php
// get_whatsapp_contact.php - Obtener número de WhatsApp de un cuidador
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$caregiver_id = $_GET['id'] ?? 0;
if (!$caregiver_id) {
    echo json_encode(['error' => 'ID de cuidador requerido']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT name, phone FROM caregivers WHERE id = ? AND role != 'admin'");
$stmt->execute([$caregiver_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'Cuidador no encontrado']);
    exit;
}

// Formatear número para WhatsApp (eliminar espacios, símbolos, solo números)
$phone = preg_replace('/[^0-9]/', '', $user['phone']);
// Si tiene 10 dígitos, asumir código de país 57 (Colombia)
if (strlen($phone) === 10) {
    $phone = '57' . $phone;
}

echo json_encode([
    'name' => $user['name'],
    'phone' => $user['phone'],
    'whatsapp_url' => 'https://wa.me/' . $phone
]);
?>