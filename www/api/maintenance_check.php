<?php
header('Content-Type: application/json');

$maintenance = file_get_contents(__DIR__ . '/maintenance_enabled.txt') ?: '0';
echo json_encode(['maintenance' => $maintenance === '1']);
?>