<?php
// api/police_locations.php
// Devuelve la última ubicación conocida de todos los cuidadores activos
// Consumido por el mapa en police_view.php cada 30 segundos

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

// Solo policías y admins pueden consultar esto
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['police', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $pdo = getDB();

    // Devuelve cuidadores con ubicación registrada en las últimas 2 horas
    $stmt = $pdo->query("
        SELECT
            cl.caregiver_id,
            c.name,
            c.group_type,
            cl.lat,
            cl.lng,
            cl.last_update,
            c.location
        FROM caregiver_locations cl
        INNER JOIN caregivers c ON c.id = cl.caregiver_id
        WHERE cl.last_update >= NOW() - INTERVAL 2 HOUR
        ORDER BY c.name ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir lat/lng a float para que el JSON no los mande como string
    foreach ($rows as &$row) {
        $row['lat'] = (float)$row['lat'];
        $row['lng'] = (float)$row['lng'];
    }

    echo json_encode($rows);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
?>
