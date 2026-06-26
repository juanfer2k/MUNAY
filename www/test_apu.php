<?php
// test_api.php - Prueba rápida de conexión a API
header('Content-Type: text/plain');
require_once 'api/config.php';

echo "=== PRUEBA DE API ===\n\n";

// 1. Probar conexión a BD
try {
    $pdo = getDB();
    echo "✅ Conexión a BD: OK\n";
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "\n";
}

// 2. Probar consulta a usuarios
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM caregivers");
    $count = $stmt->fetchColumn();
    echo "✅ Usuarios en BD: $count\n";
} catch (Exception $e) {
    echo "❌ Error en consulta: " . $e->getMessage() . "\n";
}

// 3. Probar autenticación (simulada)
echo "\nPrueba de login con admin@ejemplo.com / admin123\n";
$email = 'admin@ejemplo.com';
$password = 'admin123';
$stmt = $pdo->prepare("SELECT password FROM caregivers WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user && password_verify($password, $user['password'])) {
    echo "✅ Autenticación correcta\n";
} else {
    echo "❌ Autenticación fallida (hash incorrecto o usuario no existe)\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>