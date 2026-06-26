<?php
require_once 'firebase_config.php';
echo "✅ Firebase SDK cargado correctamente\n";
echo "📁 Credenciales: " . (file_exists('firebase-credentials.json') ? '✅ Existe' : '❌ No existe') . "\n";
?>
