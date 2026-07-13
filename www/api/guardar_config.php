<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT func_qr, func_agenda, func_push, func_maintenance FROM app_config WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($config) {
            echo json_encode($config);
        } else {
            echo json_encode(['qr'=>'0','agenda'=>'0','push'=>'0','maintenance'=>'0']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de BD: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }
    $qr = $input['qr'] ?? '0';
    $agenda = $input['agenda'] ?? '0';
    $push = $input['push'] ?? '0';
    $maintenance = $input['maintenance'] ?? '0';
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE app_config SET func_qr = ?, func_agenda = ?, func_push = ?, func_maintenance = ? WHERE id = 1");
        $stmt->execute([$qr, $agenda, $push, $maintenance]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
