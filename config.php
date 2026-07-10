<?php
// ===== CONFIGURACIÓN DE SESIÓN (ANTES DE CUALQUIER SALIDA) =====
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ===== CONFIGURACIÓN DE BASE DE DATOS =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'elcerrit_MUNAY');
define('DB_USER', 'elcerrit_munay');
define('DB_PASS', '9_3}dJ+[bK=U'); // <--- CAMBIA ESTO

define('BASE_URL', 'https://elcerritovalle.org/MUNAY/www/');
define('SECRET_KEY', 'a1b2c3d4e5f6');

date_default_timezone_set('America/Bogota');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Firebase desactivado temporalmente (si no lo usas, comenta)
// require_once 'firebase_config.php';
?>