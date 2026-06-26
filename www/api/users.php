<?php
session_start(); // <--- MOVER AQUÍ
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$role = $_GET['role'] ?? '';

$sql = "SELECT id, name, email, phone, role, group_type, created_at FROM caregivers";
if ($role) {
    $sql .= " WHERE role = :role";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['role' => $role]);
} else {
    $stmt = $pdo->query($sql);
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);
?>