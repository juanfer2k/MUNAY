<?php
// update_pass_2026.php - Actualizar todas las contraseñas a "2026*"
require_once 'config.php';

try {
    $pdo = getDB();
    $new_hash = password_hash('2026*', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios_login SET password_hash = ?");
    $stmt->execute([$new_hash]);
    
    $count = $stmt->rowCount();
    echo "✅ Contraseñas actualizadas a '2026*' para $count usuarios.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
