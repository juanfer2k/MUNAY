<?php
// shift.php - CRUD de turnos
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
            $stmt->execute([$id]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($shift) {
                echo json_encode($shift);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Turno no encontrado']);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['caregiver_id']) || empty($data['shift_date']) || empty($data['start_time'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan campos obligatorios']);
                exit;
            }
            if (isset($data['id']) && !empty($data['id'])) {
                // Actualizar
                $sql = "UPDATE shifts SET caregiver_id=?, shift_date=?, start_time=?, end_time=?, location=?, minor_id=?, shift_type=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['caregiver_id'],
                    $data['shift_date'],
                    $data['start_time'],
                    $data['end_time'] ?? '00:00:00',
                    $data['location'] ?? '',
                    $data['minor_id'] ?? '',
                    $data['shift_type'] ?? 'base',
                    $data['status'] ?? 'pending',
                    (int)$data['id']
                ]);
                echo json_encode(['success' => true]);
            } else {
                // Crear
                $sql = "INSERT INTO shifts (caregiver_id, shift_date, start_time, end_time, location, minor_id, shift_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['caregiver_id'],
                    $data['shift_date'],
                    $data['start_time'],
                    $data['end_time'] ?? '00:00:00',
                    $data['location'] ?? '',
                    $data['minor_id'] ?? '',
                    $data['shift_type'] ?? 'base',
                    $data['status'] ?? 'pending'
                ]);
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Turno no encontrado']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Mижtodo no permitido']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>