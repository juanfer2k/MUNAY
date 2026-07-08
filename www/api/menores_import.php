<?php
// menores_import.php - Importación masiva de pacientes (menores) vía CSV, solo admin
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió el archivo CSV']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo debe tener extensión .csv']);
    exit;
}

$pdo = getDB();

$handle = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$handle) {
    http_response_code(400);
    echo json_encode(['error' => 'No se pudo leer el archivo']);
    exit;
}

$encabezados = fgetcsv($handle);
if (!$encabezados) {
    fclose($handle);
    http_response_code(400);
    echo json_encode(['error' => 'El archivo está vacío o mal formado']);
    exit;
}
$encabezados = array_map(fn($h) => strtolower(trim($h)), $encabezados);

$columnasValidas = ['tipo_doc', 'documento', 'nombre', 'apellido', 'fecha_nacimiento', 'genero', 'custodio_responsable', 'telefono_contacto', 'observaciones'];
$tiposDocValidos = ['TI', 'RC', 'CC', 'CE', 'PA', 'CN', 'PT', 'DE', 'SC', 'CD'];
$generosValidos = ['M', 'F', 'I'];

$insertados = 0;
$omitidos = 0;

$stmtCheck = $pdo->prepare("SELECT id FROM menores WHERE documento = ? LIMIT 1");
$stmtInsert = $pdo->prepare("
    INSERT INTO menores (tipo_doc, documento, nombre, apellido, fecha_nacimiento, genero, custodio_responsable, telefono_contacto, observaciones, activo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
");

while (($fila = fgetcsv($handle)) !== false) {
    if (count(array_filter($fila, fn($v) => trim((string)$v) !== '')) === 0) {
        continue; // fila vacía
    }
    $datos = array_combine(
        array_slice($encabezados, 0, count($fila)),
        array_map('trim', $fila)
    );

    $documento = $datos['documento'] ?? '';
    $nombre = $datos['nombre'] ?? '';

    if (empty($documento) || empty($nombre)) {
        $omitidos++;
        continue;
    }

    $stmtCheck->execute([$documento]);
    if ($stmtCheck->fetch()) {
        $omitidos++; // ya existe, no se duplica
        continue;
    }

    $tipoDoc = strtoupper($datos['tipo_doc'] ?? 'TI');
    if (!in_array($tipoDoc, $tiposDocValidos, true)) {
        $tipoDoc = 'TI';
    }
    $genero = strtoupper($datos['genero'] ?? 'I');
    if (!in_array($genero, $generosValidos, true)) {
        $genero = 'I';
    }
    $fechaNacimiento = !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null;

    try {
        $stmtInsert->execute([
            $tipoDoc,
            $documento,
            $nombre,
            $datos['apellido'] ?? '',
            $fechaNacimiento,
            $genero,
            $datos['custodio_responsable'] ?? null,
            $datos['telefono_contacto'] ?? null,
            $datos['observaciones'] ?? null,
        ]);
        $insertados++;
    } catch (PDOException $e) {
        $omitidos++;
    }
}

fclose($handle);

echo json_encode(['success' => true, 'insertados' => $insertados, 'omitidos' => $omitidos]);
?>
