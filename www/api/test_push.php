<?php
// test_push.php - PRUEBA DE ENVÍO DE NOTIFICACIONES
require_once 'config.php';  // ← Esto es lo que faltaba
require_once 'firebase_config.php';

// Obtener un token de prueba de la base de datos
$pdo = getDB();
$stmt = $pdo->query("SELECT token FROM device_tokens LIMIT 1");
$token = $stmt->fetchColumn();

if (!$token) {
    die("❌ No hay tokens registrados en la base de datos.\n");
}

echo "📱 Enviando notificación a: " . substr($token, 0, 20) . "...\n";

$result = sendPushNotification(
    $token,
    "🔔 Prueba de Firebase",
    "¡El SDK está funcionando correctamente!",
    ['type' => 'test', 'timestamp' => time()]
);

if ($result) {
    echo "✅ Notificación enviada exitosamente!\n";
    print_r($result);
} else {
    echo "❌ Error al enviar la notificación.\n";
}
?>
