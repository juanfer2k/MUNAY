<?php
// migrar_menores_encriptados.php - MIGRACIÓN ÚNICA
// EJECUTAR MANUALMENTE: php migrar_menores_encriptados.php
// NO INCLUIR EN NAVEGADOR (sin salida web)

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/crypto_helper.php';

if (php_sapi_name() !== 'cli') {
    die('Este script solo se ejecuta desde terminal (CLI)');
}

echo "=== MIGRACIÓN: Menores a menores_seguro (cifrados) ===\n\n";

try {
    $pdo = getDB();
    
    // Las tablas ya deben estar creadas (usuarios_seguro.sql ya se ejecutó en phpMyAdmin)
    // No intentar crear tablas aquí
    
    // 1. Consultar todos los menores de la tabla vieja
    $stmt = $pdo->query("SELECT id, documento, nombre, apellido, fecha_nacimiento FROM menores WHERE documento IS NOT NULL");
    $menores_viejos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontrados " . count($menores_viejos) . " menores para migrar\n";
    
    // 3. Insertar en tabla nueva con cifrado
    $insert = $pdo->prepare("
        INSERT INTO menores_seguro 
        (codigo_anonimo, documento_cifrado, nombre_cifrado, apellido_cifrado, fecha_nacimiento_cifrada, estado_custodia)
        VALUES (?, ?, ?, ?, ?, 'Activo')
        ON DUPLICATE KEY UPDATE 
        documento_cifrado = VALUES(documento_cifrado),
        nombre_cifrado = VALUES(nombre_cifrado)
    ");
    
    $migrados = 0;
    $errores = 0;
    
    foreach ($menores_viejos as $menor) {
        try {
            $codigo = "MNY-2026-" . str_pad($menor['id'], 4, '0', STR_PAD_LEFT);
            
            $insert->execute([
                $codigo,
                encriptarDato($menor['documento'] ?? ''),
                encriptarDato($menor['nombre'] ?? ''),
                encriptarDato($menor['apellido'] ?? ''),
                encriptarDato($menor['fecha_nacimiento'] ?? '')
            ]);
            
            $migrados++;
            echo "✓ Migrado: ID {$menor['id']} → {$codigo}\n";
            
        } catch (Exception $e) {
            $errores++;
            echo "✗ Error en ID {$menor['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "Migrados: $migrados\n";
    echo "Errores: $errores\n";
    echo "\n⚠️  PRÓXIMOS PASOS:\n";
    echo "1. Verificar que los datos se cifren correctamente: SELECT * FROM menores_seguro LIMIT 1;\n";
    echo "2. Hacer backup de la tabla vieja 'menores' antes de eliminarla\n";
    echo "3. Actualizar todas las consultas para usar menores_seguro\n";
    echo "4. En endpoints que lean datos sensibles, llamar desencriptarDato() después de SELECT\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
?>