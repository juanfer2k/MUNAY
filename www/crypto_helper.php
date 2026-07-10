<?php
// api/crypto_helper.php - Encriptación AES-256-CBC de datos sensibles

// Cargar .env en desarrollo (en producción, usar variables de sistema)
if (file_exists(__DIR__ . '/../.env')) {
    $env_lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!defined($key) && !getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// ENCRYPTION_KEY desde .env o variable de entorno
if (!defined('ENCRYPTION_KEY')) {
    $key = getenv('ENCRYPTION_KEY') ?: $_ENV['ENCRYPTION_KEY'] ?? '';
    if (empty($key) || strlen($key) < 32) {
        // En desarrollo: usar una clave por defecto (NUNCA en producción)
        if (getenv('APP_ENV') !== 'production') {
            $key = 'DEV_ONLY_CAMBIAR_EN_PRODUCCION_32ch!!!';
            error_log("⚠️  ADVERTENCIA: Usando ENCRYPTION_KEY de desarrollo. Cambiar en producción.");
        } else {
            die("CRÍTICO: ENCRYPTION_KEY no configurada en producción. Revisar .env");
        }
    }
    define('ENCRYPTION_KEY', $key);
}

/**
 * Encripta un string usando AES-256-CBC
 * @param string $cadena Datos a encriptar
 * @return string Base64 encoded IV::encrypted_text
 */
function encriptarDato($cadena) {
    if (empty($cadena)) return '';
    
    $metodo = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);  // true = binary output
    $iv_length = openssl_cipher_iv_length($metodo);
    $iv = openssl_random_pseudo_bytes($iv_length);

    $encrypted = openssl_encrypt($cadena, $metodo, $key, OPENSSL_RAW_DATA, $iv);
    
    // Concatenar IV + encrypted, separados por '::', y codificar en Base64
    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Desencripta un string cifrado con encriptarDato()
 * @param string $cadena_cifrada Base64 encoded IV::encrypted_text
 * @return string|false Datos desencriptados o false si falla
 */
function desencriptarDato($cadena_cifrada) {
    if (empty($cadena_cifrada)) return '';
    
    $metodo = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);
    
    $datos = base64_decode($cadena_cifrada, true);
    if ($datos === false) return false;
    
    // Separar IV y texto cifrado
    $partes = explode('::', $datos, 2);
    if (count($partes) !== 2) return false;
    
    list($iv, $texto_cifrado) = $partes;
    $descifrado = openssl_decrypt($texto_cifrado, $metodo, $key, OPENSSL_RAW_DATA, $iv);
    
    return $descifrado ?: false;
}

/**
 * Hash seguro de contraseña (BCRYPT)
 * @param string $password Contraseña en plaintext
 * @return string Hash BCRYPT
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica una contraseña contra su hash
 * @param string $password Contraseña en plaintext
 * @param string $hash Hash BCRYPT
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
