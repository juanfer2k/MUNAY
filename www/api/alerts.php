<?php
// alerts.php - Devuelve alertas NO leídas por el usuario actual
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] === 'admin');

// Verificar que la tabla alert_reads existe, si no, crearla
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS alert_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_id INT NOT NULL,
        user_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_read (alert_id, user_id)
    )");
} catch (PDOException $e) {
    // Si falla, continuar (puede que la tabla ya exista)
}

// Obtener alertas que el usuario NO ha leído
$sql = "SELECT a.*, c.name as caregiver_name 
        FROM alerts a 
        LEFT JOIN caregivers c ON a.caregiver_id = c.id 
        WHERE a.id NOT IN (
            SELECT alert_id FROM alert_reads WHERE user_id = ?
        )";
if (!$is_admin) {
    $sql .= " AND (a.caregiver_id = ? OR a.caregiver_id IS NULL)";
}
$sql .= " ORDER BY a.created_at DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
if (!$is_admin) {
    $stmt->execute([$user_id, $user_id]);
} else {
    $stmt->execute([$user_id]);
}
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar como leídas las que se van a mostrar
if (!empty($alerts)) {
    $insertStmt = $pdo->prepare("INSERT IGNORE INTO alert_reads (alert_id, user_id) VALUES (?, ?)");
    foreach ($alerts as $alert) {
        $insertStmt->execute([$alert['id'], $user_id]);
    }
}

// Reemplazar origen con nombre del usuario si existe
foreach ($alerts as &$alert) {
    if ($alert['caregiver_name']) {
        $alert['origin'] = $alert['caregiver_name'];
    }
    // Asegurar que el mensaje no tenga caracteres extraños
    $alert['message'] = htmlspecialchars($alert['message']);
}

echo json_encode($alerts);
?>