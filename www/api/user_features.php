<?php
// api/user_features.php
// GET  ?user_id=X  → devuelve las features del usuario
// POST             → { user_id, feature, enabled } → actualiza

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

// Solo admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/config.php';

$pdo = getDB();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS user_features (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    feature    VARCHAR(50) NOT NULL,
    enabled    TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_feat (user_id, feature)
)");

// ===== GET =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if (!$user_id) {
        echo json_encode(['error' => 'user_id requerido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT feature, enabled FROM user_features WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $r) {
        $result[$r['feature']] = (bool)$r['enabled'];
    }
    echo json_encode($result);
    exit;
}

// ===== POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $feature = isset($data['feature']) ? trim($data['feature']) : '';
    $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;

    $allowed = ['gps', 'gastos', 'cambios', 'alertas', 'mapa', 'rutas'];
    if (!$user_id || !$feature || !in_array($feature, $allowed)) {
        echo json_encode(['error' => 'Parámetros inválidos']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_features (user_id, feature, enabled)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)
    ");
    $stmt->execute([$user_id, $feature, $enabled ? 1 : 0]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Método no permitido']);
?>
