<?php
header('Content-Type: application/json');
require_once 'config.php'; // Esto ya inicia la sesión

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? 0;
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['caregiver_id']) || empty($data['shift_date']) || empty($data['start_time'])) {
            echo json_encode(['error' => 'Campos requeridos faltantes']);
            exit;
        }

        if (!empty($data['id'])) {
            // Actualizar
            $sql = "UPDATE shifts SET caregiver_id=?, shift_date=?, start_time=?, end_time=?, location=?, minor_id=?, shift_type=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['caregiver_id'], $data['shift_date'], $data['start_time'], $data['end_time'],
                $data['location'], $data['minor_id'], $data['shift_type'], $data['status'], $data['id']
            ]);
            echo json_encode(['success' => true]);
        } else {
            // Crear
            $sql = "INSERT INTO shifts (caregiver_id, shift_date, start_time, end_time, location, minor_id, shift_type, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['caregiver_id'], $data['shift_date'], $data['start_time'], $data['end_time'],
                $data['location'], $data['minor_id'], $data['shift_type'], $data['status'] ?? 'pending'
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        if (!$id) { echo json_encode(['error' => 'ID requerido']); exit; }
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
?>