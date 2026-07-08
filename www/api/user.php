<?php
// user.php - CRUD de usuarios (incluye pattern_code y section_id)
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
            $stmt = $pdo->prepare("SELECT id, name, email, phone, role, pattern_code, section_id, group_type FROM caregivers WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['name']) || empty($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nombre y email son obligatorios']);
                exit;
            }

            // Si no tiene ID, es nuevo; si tiene, se actualiza
            if (empty($data['id'])) {
                // CREAR
                $hash = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO caregivers 
                    (name, email, phone, password, role, pattern_code, section_id, group_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?? '',
                    $hash,
                    $data['role'] ?? 'caregiver',
                    $data['pattern_code'] ?? null,
                    $data['section_id'] ?? null,
                    $data['group_type'] ?? 'A'
                ]);
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                // ACTUALIZAR
                $sql = "UPDATE caregivers SET 
                        name = ?,
                        email = ?,
                        phone = ?,
                        role = ?,
                        pattern_code = ?,
                        section_id = ?,
                        group_type = ?";
                $params = [
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?? '',
                    $data['role'] ?? 'caregiver',
                    $data['pattern_code'] ?? null,
                    $data['section_id'] ?? null,
                    $data['group_type'] ?? 'A'
                ];
                if (!empty($data['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id = ?";
                $params[] = (int)$data['id'];

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true]);
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
            // Verificar que no sea el propio usuario logueado
            if ($id == $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['error' => 'No puedes eliminarte a ti mismo']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM caregivers WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>