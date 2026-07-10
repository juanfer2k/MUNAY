<?php
// visitas.php - Visitas médicas (creadas por enfermería, acompañadas por un custodio;
// la escolta policial se identifica vía ficha con QR, sin usuario asignado)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Verificamos el rol directo desde la BD (no confiamos en la sesión).
$stmt = $pdo->prepare("SELECT role FROM caregivers WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}
$miRol = $me['role'];

try {
    if ($method === 'GET') {
        $sql = "SELECT v.*,
                       CONCAT(m.nombre, ' ', COALESCE(m.apellido, '')) AS paciente_nombre,
                       cu.name AS custodio_name
                FROM visitas_medicas v
                LEFT JOIN menores m ON v.menor_id = m.id
                LEFT JOIN caregivers cu ON v.custodio_id = cu.id";

        if (in_array($miRol, ['admin', 'nursing'], true)) {
            $sql .= " ORDER BY v.visit_date DESC, v.visit_time DESC";
            $stmt = $pdo->query($sql);
        } elseif ($miRol === 'custodio') {
            $sql .= " WHERE v.custodio_id = ? ORDER BY v.visit_date DESC, v.visit_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($method === 'POST') {
        if (!in_array($miRol, ['nursing', 'admin'], true)) {
            http_response_code(403);
            echo json_encode(['error' => 'Solo enfermería o el administrador pueden crear acompañamientos']);
            exit;
        }

        // Los datos llegan como multipart/form-data (para poder incluir el adjunto),
        // así que se leen de $_POST / $_FILES, no del cuerpo JSON.
        $menor_id = $_POST['menor_id'] ?? '';
        $visit_date = $_POST['visit_date'] ?? '';
        $visit_time = $_POST['visit_time'] ?? '';
        $custodio_id = $_POST['custodio_id'] ?? '';
        $tiposValidos = ['cita', 'ruta_emergencia', 'juzgado', 'cambio_centro'];
        $tipo = in_array($_POST['tipo'] ?? '', $tiposValidos, true) ? $_POST['tipo'] : 'cita';

        if (empty($menor_id) || empty($visit_date) || empty($visit_time) || empty($custodio_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan campos obligatorios']);
            exit;
        }

        // Validar que custodio_id corresponda a un usuario con rol custodio.
        $stmt = $pdo->prepare("SELECT role FROM caregivers WHERE id = ?");
        $stmt->execute([$custodio_id]);
        $rolCustodio = $stmt->fetchColumn();
        if ($rolCustodio !== 'custodio') {
            http_response_code(400);
            echo json_encode(['error' => 'El custodio seleccionado no es válido']);
            exit;
        }

        // Adjunto opcional.
        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Tipo de archivo no permitido (usa PDF, JPG, PNG o WEBP)']);
                exit;
            }
            if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(['error' => 'El archivo supera el tamaño máximo de 5MB']);
                exit;
            }
            $uploadDir = __DIR__ . '/uploads/visitas/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $nombreArchivo = uniqid('visita_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $nombreArchivo)) {
                $attachmentPath = 'uploads/visitas/' . $nombreArchivo;
            }
        }

        $qrToken = bin2hex(random_bytes(20));

        $sql = "INSERT INTO visitas_medicas (menor_id, visit_date, visit_time, location, motivo, tipo, custodio_id, created_by, status, qr_token, attachment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $menor_id,
            $visit_date,
            $visit_time,
            $_POST['location'] ?? '',
            $_POST['motivo'] ?? '',
            $tipo,
            $custodio_id,
            $user_id,
            $qrToken,
            $attachmentPath
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'qr_token' => $qrToken]);
        exit;
    }

    if ($method === 'PATCH') {
        if ($miRol !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Solo el administrador puede actualizar el estado']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $estadosValidos = ['confirmed', 'completed', 'cancelled'];
        if (empty($data['id']) || !in_array($data['status'] ?? '', $estadosValidos, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID y estado válido son requeridos']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE visitas_medicas SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>