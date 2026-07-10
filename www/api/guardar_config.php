<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

// Guardar cada opción en un archivo .txt
$configs = [
    'qr' => $input['qr'] ?? '0',
    'agenda' => $input['agenda'] ?? '0',
    'push' => $input['push'] ?? '0',
    'maintenance' => $input['maintenance'] ?? '0'
];

foreach ($configs as $key => $value) {
    file_put_contents(__DIR__ . "/{$key}_enabled.txt", $value);
}

echo json_encode(['success' => true]);
?>