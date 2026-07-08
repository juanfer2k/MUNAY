<?php
// guardar_config.php
header('Content-Type: application/json');
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Permite que el dashboard cargue el estado guardado al abrir el panel.
    $archivos = [
        'qr' => __DIR__ . '/qr_enabled.txt',
        'agenda' => __DIR__ . '/agenda_enabled.txt',
        'push' => __DIR__ . '/push_enabled.txt',
        'maintenance' => __DIR__ . '/maintenance.txt',
    ];
    $estado = [];
    foreach ($archivos as $key => $path) {
        $estado[$key] = file_exists($path) ? trim(@file_get_contents($path)) : '0';
    }
    echo json_encode($estado);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// Rutas ABSOLUTAS (__DIR__), no relativas: el script ya vive dentro de api/,
// así que 'api/archivo.txt' podía apuntar a api/api/archivo.txt según cómo
// el servidor resuelva el directorio de trabajo, y eso rompía el guardado.
$files = [
    'qr' => __DIR__ . '/qr_enabled.txt',
    'agenda' => __DIR__ . '/agenda_enabled.txt',
    'push' => __DIR__ . '/push_enabled.txt',
    'maintenance' => __DIR__ . '/maintenance.txt',
];

$fallos = [];
foreach ($files as $key => $path) {
    if (isset($data[$key])) {
        $ok = @file_put_contents($path, $data[$key]);
        if ($ok === false) {
            $fallos[] = $key;
        }
    }
}

if ($fallos) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo escribir: ' . implode(', ', $fallos) . ' (revisa permisos de escritura en la carpeta api/)']);
    exit;
}

echo json_encode(['success' => true]);
?>
