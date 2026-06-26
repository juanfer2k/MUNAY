<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado', 'session' => $_SESSION, 'cookie' => $_COOKIE]);
    exit;
}

require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT id, name, email, role FROM caregivers LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['users' => $users, 'session' => $_SESSION]);
?>