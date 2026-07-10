<?php
// api/visitas.php - Gestión de rutas (visitas médicas) con lógica de activación
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

// =============================================
// GET - Listar rutas
// =============================================
if ($method === 'GET') {
    $query = "
        SELECT 
            vm.id,
            vm.menor_id,
            vm.visit_date,
            vm.visit_time,
            vm.location,
            vm.motivo,
            vm.tipo,
            vm.custodio_id,
            vm.police_id,
            vm.created_by,
            vm.status,
            vm.created_at,
            m.nombre as paciente_nombre,
            m.apellido as paciente_apellido,
            m.documento as paciente_documento,
            c.name as custodio_nombre,
            c.id as custodio_id,
            p.name as police_nombre
        FROM visitas_medicas vm
        LEFT JOIN menores m ON vm.menor_id = m.id
        LEFT JOIN caregivers c ON vm.custodio_id = c.id
        LEFT JOIN caregivers p ON vm.police_id = p.id
        ORDER BY vm.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($visitas as &$v) {
        $v['qr_token'] = base64_encode($v['id'] . '|' . time() . '|' . $v['visit_date']);
    }
    echo json_encode($visitas);
    exit;
}

// =============================================
// POST - Crear nueva ruta (activación)
// =============================================
if ($method === 'POST') {
    if (!in_array($_SESSION['user_role'], ['admin', 'nursing', 'coordinator'])) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para activar rutas']);
        exit;
    }

    // Obtener datos (puede ser JSON o FormData)
    $input = [];
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = [
            'menor_id' => $_POST['menor_id'] ?? null,
            'visit_date' => $_POST['visit_date'] ?? null,
            'visit_time' => $_POST['visit_time'] ?? null,
            'location' => $_POST['location'] ?? '',
            'motivo' => $_POST['motivo'] ?? '',
            'custodio_id' => $_POST['custodio_id'] ?? null,
            'tipo' => $_POST['tipo'] ?? 'cita'
        ];
    }

    if (empty($input['menor_id']) || empty($input['visit_date']) || empty($input['visit_time']) || empty($input['custodio_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Paciente, fecha, hora y formador son obligatorios']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insertar la ruta en visitas_medicas
        $stmt = $pdo->prepare("
            INSERT INTO visitas_medicas 
            (menor_id, visit_date, visit_time, location, motivo, tipo, custodio_id, police_id, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        $stmt->execute([
            $input['menor_id'],
            $input['visit_date'],
            $input['visit_time'],
            $input['location'] ?? null,
            $input['motivo'] ?? null,
            $input['tipo'] ?? 'cita',
            $input['custodio_id'],
            $_SESSION['user_id'], // police_id (temporal, deberías elegir un policía)
            $user_id
        ]);
        $ruta_id = $pdo->lastInsertId();

        // --- NUEVA LÓGICA DE ACTIVACIÓN DE RUTA ---

        // 2. Obtener turno activo del formador en la fecha/hora de la ruta
        $stmtTurno = $pdo->prepare("
            SELECT id, caregiver_id, shift_date, start_time, end_time, status
            FROM shifts
            WHERE caregiver_id = ?
              AND shift_date = ?
              AND status IN ('in_progress', 'pending')
              AND start_time <= ? 
              AND end_time > ?
            LIMIT 1
        ");
        $stmtTurno->execute([
            $input['custodio_id'],
            $input['visit_date'],
            $input['visit_time'],
            $input['visit_time']
        ]);
        $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);

        if ($turno) {
            // 2.1 Marcar turno como interrumpido
            $stmtUpdate = $pdo->prepare("UPDATE shifts SET status = 'interrupted' WHERE id = ?");
            $stmtUpdate->execute([$turno['id']]);
            $turno_id = $turno['id'];
        } else {
            $turno_id = null;
        }

        // 3. Crear gastos automáticos (transporte y parking/toll)
        $tiposGasto = ['transport', 'parking'];
        foreach ($tiposGasto as $tipo) {
            $desc = ($tipo === 'transport') 
                ? "Transporte por activación de ruta - ID: $ruta_id"
                : "Gastos de ruta - ID: $ruta_id";
            $stmtGasto = $pdo->prepare("
                INSERT INTO caregiver_expenses 
                (caregiver_id, shift_id, type, amount, description, status, created_at)
                VALUES (?, ?, ?, 0, ?, 'pending', NOW())
            ");
            $stmtGasto->execute([
                $input['custodio_id'],
                $turno_id,
                $tipo,
                $desc
            ]);
        }

        // 4. Notificar al siguiente formador disponible
        $activador = $_SESSION['user_name'] ?? 'Sistema';
        $fichaUrl = "https://elcerritovalle.org/MUNAY/www/api/ficha_visita.php?token=" . base64_encode($ruta_id . '|' . time() . '|' . $input['visit_date']);
        $mensaje = "Ruta activada por $activador. Turno interrumpido. QR disponible: $fichaUrl";

        // 4.1 Obtener formadores con turnos activos en esa fecha (excluyendo al actual)
        $stmtFormadores = $pdo->prepare("
            SELECT DISTINCT caregiver_id
            FROM shifts
            WHERE shift_date = ?
              AND status IN ('in_progress', 'pending')
              AND caregiver_id != ?
        ");
        $stmtFormadores->execute([$input['visit_date'], $input['custodio_id']]);
        $formadores = $stmtFormadores->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($formadores)) {
            // Regla de selección: primero aleatorio, luego por orden de ID
            // Guardamos el orden en una variable de sesión para mantener consistencia
            if (!isset($_SESSION['ruta_notify_order']) || empty($_SESSION['ruta_notify_order'])) {
                shuffle($formadores);
                $_SESSION['ruta_notify_order'] = $formadores;
            }
            $siguiente_formador_id = array_shift($_SESSION['ruta_notify_order']);
            // Si se acaba la lista, la rotamos
            if (empty($_SESSION['ruta_notify_order'])) {
                $_SESSION['ruta_notify_order'] = $formadores;
                shuffle($_SESSION['ruta_notify_order']);
            }

            // 4.2 Insertar alerta
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alerts (caregiver_id, message, type, origin, is_read, created_at)
                VALUES (?, ?, 'warning', 'ruta', 0, NOW())
            ");
            $stmtAlerta->execute([$siguiente_formador_id, $mensaje]);
        } else {
            // Si no hay formador disponible, enviar alerta a admin o coordinador
            $stmtAlerta = $pdo->prepare("
                INSERT INTO alerts (caregiver_id, message, type, origin, is_read, created_at)
                VALUES (?, ?, 'warning', 'ruta', 0, NOW())
            ");
            // Buscar un admin o coordinador (ejemplo: el usuario con rol admin o coordinator)
            $stmtAdmin = $pdo->prepare("SELECT id FROM caregivers WHERE role IN ('admin', 'coordinator') LIMIT 1");
            $stmtAdmin->execute();
            $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
            if ($admin) {
                $stmtAlerta->execute([$admin['id'], $mensaje . " (No hay formadores disponibles)"]);
            }
        }

        $pdo->commit();

        // Generar token QR para respuesta
        $qr_token = base64_encode($ruta_id . '|' . time() . '|' . $input['visit_date']);

        echo json_encode([
            'success' => true,
            'id' => $ruta_id,
            'qr_token' => $qr_token,
            'message' => 'Ruta activada correctamente'
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error en base de datos: ' . $e->getMessage()]);
    }
    exit;
}

// =============================================
// PATCH - Actualizar estado de ruta
// =============================================
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id']) || empty($input['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID y estado son requeridos']);
        exit;
    }
    $statusValidos = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($input['status'], $statusValidos)) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado no válido']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("UPDATE visitas_medicas SET status = ? WHERE id = ?");
        $stmt->execute([$input['status'], $input['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar: ' . $e->getMessage()]);
    }
    exit;
}

// =============================================
// DELETE - Eliminar ruta (solo admin)
// =============================================
if ($method === 'DELETE') {
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requerido']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM visitas_medicas WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no soportado']);