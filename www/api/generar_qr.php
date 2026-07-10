<?php
// api/generar_qr.php - Usando tus tablas reales
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

// Conexión a la BD
require_once 'config.php';
$conn = getDBConnection();

// Obtener datos de la visita médica
$stmt = $conn->prepare("
    SELECT 
        vm.id,
        vm.menor_id,
        vm.visit_date,
        vm.visit_time,
        vm.location,
        vm.custodio_id,
        vm.status,
        m.nombre as paciente_nombre,
        m.apellido as paciente_apellido,
        c.name as custodio_nombre
    FROM visitas_medicas vm
    LEFT JOIN menores m ON vm.menor_id = m.id
    LEFT JOIN caregivers c ON vm.custodio_id = c.id
    WHERE vm.id = ?
");
$stmt->bind_param("i", $visita_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Visita no encontrada']);
    exit;
}

$visita = $result->fetch_assoc();

// Construir datos para el token
$datos_visita = [
    'visita_id' => $visita['id'],
    'paciente' => trim($visita['paciente_nombre'] . ' ' . $visita['paciente_apellido']),
    'destino' => $visita['location'] ?? 'No especificado',
    'formador' => $visita['custodio_nombre'] ?? 'No asignado',
    'fecha' => date('Y-m-d H:i', strtotime($visita['visit_date'] . ' ' . $visita['visit_time'])),
    'valida_hasta' => date('Y-m-d H:i', strtotime($visita['visit_date'] . ' ' . $visita['visit_time'] . ' +4 hours'))
];

// Generar token con los datos
$token = base64_encode(json_encode($datos_visita));

// URL de la ficha
$ficha_url = "https://elcerritovalle.org/MUNAY/www/api/ficha_visita.php?token=" . urlencode($token);

// QR con color institucional
$color = '1ABC9C';
$qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($ficha_url) . "&color=" . $color;

echo json_encode([
    'success' => true,
    'qr_image' => $qr_image,
    'ficha_url' => $ficha_url,
    'token' => $token,
    'datos' => $datos_visita // Para depuración
]);

$stmt->close();
$conn->close();
?>