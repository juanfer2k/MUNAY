<?php
// police_routes.php - Rutas de transporte
// GET: devuelve las rutas de hoy del policía autenticado (o todas, si admin).
// PATCH: actualiza el estado de una ruta (iniciar/completar/cancelar).
// Antes el frontend llamaba a 'api/police_route.php' (singular, no existe)
// para el PATCH, dejando los botones de acción sin efecto.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'police' && $_SESSION['user_role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$userId = $_SESSION['user_id'];
$isPolice = ($_SESSION['user_role'] === 'police');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Si la tabla police_routes no existe, devolvemos array vacío
    // Pero intentamos consultar por si existe
    try {
        $sql = "SELECT pr.*, 
                       c.name as caregiver_name, 
                       c.phone as caregiver_phone,
                       s.location as shift_location,
                       s.minor_id
                FROM police_routes pr
                JOIN shifts s ON pr.shift_id = s.id
                JOIN caregivers c ON s.caregiver_id = c.id
                WHERE DATE(pr.pickup_time) = CURDATE()";

        if ($isPolice) {
            $sql .= " AND pr.police_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query($sql);
        }

        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($routes);

    } catch (PDOException $e) {
        // Si la tabla no existe, devolvemos array vacío
        echo json_encode([]);
    }

} elseif ($method === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['status'])) {
        echo json_encode(['error' => 'ID y estado requeridos']);
        exit;
    }
    if (!in_array($data['status'], ['pending', 'in_transit', 'completed', 'cancelled'], true)) {
        echo json_encode(['error' => 'Estado inválido']);
        exit;
    }

    try {
        if ($isPolice) {
            // Un policía solo puede actualizar sus propias rutas
            $stmt = $pdo->prepare("UPDATE police_routes SET status = ? WHERE id = ? AND police_id = ?");
            $stmt->execute([$data['status'], $data['id'], $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE police_routes SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'No se pudo actualizar la ruta']);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>