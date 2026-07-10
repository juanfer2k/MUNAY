<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}
$visita_id = $_GET['id'] ?? null;
if (!$visita_id) {
    echo json_encode(['error' => 'ID de visita requerido']);
    exit;
}
// Conectar a la BD y obtener los datos reales
require_once 'config.php';
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id, paciente_nombre, location, custodio_id, visit_date, visit_time FROM visitas WHERE id = ?");
$stmt->bind_param("i", $visita_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Visita no encontrada']);
    exit;
}
$visita = $result->fetch_assoc();
// Obtener nombre del formador
$stmt2 = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt2->bind_param("i", $visita['custodio_id']);
$stmt2->execute();
$result2 = $stmt2->get_result();
$formador = $result2->fetch_assoc();
$formador_nombre = $formador['name'] ?? 'No asignado';
// Generar token con datos reales
$token_data = json_encode([
    'visita_id' => $visita_id,
    'paciente' => $visita['paciente_nombre'],
    'destino' => $visita['location'],
    'formador' => $formador_nombre,
    'fecha' => $visita['visit_date'] . ' ' . $visita['visit_time'],
    'valida_hasta' => date('Y-m-d H:i', strtotime($visita['visit_date'] . ' ' . $visita['visit_time'] . ' +4 hours'))
]);
$token = base64_encode($token_data);
$ficha_url = "https://elcerritovalle.org/MUNAY/www/api/ficha_visita.php?token=" . urlencode($token);
$color = '1ABC9C';
$qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($ficha_url) . "&color=" . $color;
echo json_encode([
    'success' => true,
    'qr_image' => $qr_image,
    'ficha_url' => $ficha_url,
    'token' => $token,
    'color' => '#' . $color
]);
