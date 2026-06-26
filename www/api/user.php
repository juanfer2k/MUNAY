<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

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
        if (!$id) {
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, name, email, phone, role, group_type FROM caregivers WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['error' => 'Usuario no encontrado']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
            echo json_encode(['error' => 'Nombre, email y teléfono son obligatorios']);
            exit;
        }
        if (!empty($data['id'])) {
            // Actualizar
            $sql = "UPDATE caregivers SET name=?, email=?, phone=?, role=?, group_type=?";
            $params = [$data['name'], $data['email'], $data['phone'], $data['role'], $data['group_type']];
            if (!empty($data['password'])) {
                $sql .= ", password=?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id=?";
            $params[] = $data['id'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true]);
        } else {
            // Crear
            $hash = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO caregivers (name, email, phone, password, role, group_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['email'], $data['phone'], $hash, $data['role'], $data['group_type']]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        if (!$id) {
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM caregivers WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
?>