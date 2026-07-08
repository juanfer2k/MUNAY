<?php
// api/update_location.php
// Recibe lat/lng del cuidador y actualiza su posición en la BD

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$caregiver_id = isset($data['caregiver_id']) ? (int)$data['caregiver_id'] : 0;
$lat          = isset($data['lat'])          ? (float)$data['lat']          : null;
$lng          = isset($data['lng'])          ? (float)$data['lng']          : null;

if (!$caregiver_id || $lat === null || $lng === null) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Validar rango geográfico básico
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['error' => 'Coordenadas inválidas']);
    exit;
}

try {
    $pdo = getDB();

    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS caregiver_locations (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        caregiver_id  INT NOT NULL UNIQUE,
        lat           DECIMAL(10,7) NOT NULL,
        lng           DECIMAL(10,7) NOT NULL,
        last_update   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_caregiver (caregiver_id)
    )");

    // INSERT o UPDATE según si ya existe una fila para este cuidador
    $stmt = $pdo->prepare("
        INSERT INTO caregiver_locations (caregiver_id, lat, lng)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), last_update = NOW()
    ");
    $stmt->execute([$caregiver_id, $lat, $lng]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
