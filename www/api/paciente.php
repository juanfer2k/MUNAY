<?php
// api/paciente.php - CRUD individual de pacientes (menores)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Solo admin y enfermería pueden crear/editar
if (!in_array($_SESSION['user_role'], ['admin', 'nursing'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ===== POST: Crear o actualizar =====
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $id = $input['id'] ?? null;
    $tipo_doc = $input['tipo_doc'] ?? 'TI';
    $documento = trim($input['documento'] ?? '');
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $fecha_nac = !empty($input['fecha_nacimiento']) ? $input['fecha_nacimiento'] : null;
    $genero = $input['genero'] ?? 'I';
    $custodio = !empty($input['custodio_responsable']) ? $input['custodio_responsable'] : null;
    $telefono = !empty($input['telefono_contacto']) ? $input['telefono_contacto'] : null;
    $observaciones = !empty($input['observaciones']) ? $input['observaciones'] : null;

    if (empty($documento) || empty($nombre)) {
        http_response_code(400);
        echo json_encode(['error' => 'Documento y Nombre son obligatorios']);
        exit;
    }

    try {
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE menores SET 
                    tipo_doc = ?, documento = ?, nombre = ?, apellido = ?, 
                    fecha_nacimiento = ?, genero = ?, custodio_responsable = ?, 
                    telefono_contacto = ?, observaciones = ?, activo = 1
                WHERE id = ?
            ");
            $stmt->execute([$tipo_doc, $documento, $nombre, $apellido, $fecha_nac, $genero, $custodio, $telefono, $observaciones, $id]);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            // Insertar nuevo, verificar duplicado
            $check = $pdo->prepare("SELECT id FROM menores WHERE documento = ?");
            $check->execute([$documento]);
            if ($check->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Ya existe un paciente con ese documento']);
                exit;
            }
            $stmt = $pdo->prepare("
                INSERT INTO menores (tipo_doc, documento, nombre, apellido, fecha_nacimiento, genero, custodio_responsable, telefono_contacto, observaciones, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$tipo_doc, $documento, $nombre, $apellido, $fecha_nac, $genero, $custodio, $telefono, $observaciones]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en BD: ' . $e->getMessage()]);
    }
    exit;
}

// ===== DELETE: Desactivar (solo admin) =====
if ($method === 'DELETE') {
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("UPDATE menores SET activo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);