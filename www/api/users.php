<?php
// users.php - Lista de usuarios con patrón, sección y roles traducidos
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'nursing'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$role = $_GET['role'] ?? '';

// Incluir pattern_code y section_name. Nunca se selecciona 'password': no debe salir
// del servidor, ni siquiera para el rol admin.
$sql = "SELECT c.id, c.name, c.email, c.phone, c.group_type, c.role, c.police_escort,
               c.qr_code, c.created_at, c.updated_at, c.pattern_code, c.section_id,
               c.rotation_anchor, c.is_coordinator,
               s.name AS section_name
        FROM caregivers c
        LEFT JOIN sections s ON c.section_id = s.id";

if ($role) {
    $sql .= " WHERE c.role = :role";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['role' => $role]);
} else {
    $stmt = $pdo->query($sql);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
?>
