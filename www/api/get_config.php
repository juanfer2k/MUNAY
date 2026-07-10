<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$configs = [
    'qr' => file_get_contents(__DIR__ . '/qr_enabled.txt') ?: '0',
    'agenda' => file_get_contents(__DIR__ . '/agenda_enabled.txt') ?: '0',
    'push' => file_get_contents(__DIR__ . '/push_enabled.txt') ?: '0',
    'maintenance' => file_get_contents(__DIR__ . '/maintenance_enabled.txt') ?: '0'
];

echo json_encode($configs);
?>