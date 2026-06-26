<?php
// check_system.php - Verificación completa del sistema
echo "=== 🔍 VERIFICACIÓN DEL SISTEMA ===\n\n";

// 1. Verificar PHP
echo "1. PHP: " . PHP_VERSION . " ✅\n";

// 2. Verificar extensiones
$extensions = ['sodium', 'curl', 'json', 'pdo_mysql', 'openssl'];
foreach ($extensions as $ext) {
    echo "   - " . $ext . ": " . (extension_loaded($ext) ? '✅' : '❌') . "\n";
}

// 3. Verificar Composer
echo "\n2. Composer: " . (file_exists(__DIR__ . '/../../vendor/autoload.php') ? '✅' : '❌') . "\n";

// 4. Verificar Firebase
echo "\n3. Firebase:\n";
if (file_exists(__DIR__ . '/firebase-credentials.json')) {
    echo "   - Credenciales: ✅\n";
} else {
    echo "   - Credenciales: ❌ (No existe)\n";
}

if (file_exists(__DIR__ . '/firebase_config.php')) {
    echo "   - Configuración: ✅\n";
    require_once 'firebase_config.php';
    echo "   - SDK: ✅\n";
} else {
    echo "   - Configuración: ❌ (No existe)\n";
}

// 5. Verificar base de datos
echo "\n4. Base de datos:\n";
try {
    require_once 'config.php';
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM caregivers");
    $count = $stmt->fetchColumn();
    echo "   - Conexión: ✅ (Usuarios: $count)\n";
} catch (Exception $e) {
    echo "   - Conexión: ❌ " . $e->getMessage() . "\n";
}

echo "\n=== ✅ VERIFICACIÓN COMPLETADA ===\n";
?>
