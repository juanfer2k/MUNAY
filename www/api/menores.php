<?php
// api/menores.php - Lista todos los pacientes (menores)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();

$query = "SELECT id, tipo_doc, documento, nombre, apellido, fecha_nacimiento, genero, custodio_responsable, telefono_contacto, observaciones, activo, created_at FROM menores ORDER BY nombre ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$menores = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($menores as &$m) {
    $m['nombre_completo'] = trim($m['nombre'] . ' ' . $m['apellido']);
    $m['edad'] = $m['fecha_nacimiento'] ? date_diff(date_create($m['fecha_nacimiento']), date_create('today'))->y . ' años' : 'N/A';
}

echo json_encode($menores);
