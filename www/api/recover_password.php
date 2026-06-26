<?php
// recover_password.php - Enviar enlace de recuperación
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(['error' => 'Correo electrónico requerido']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name FROM caregivers WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'No existe una cuenta con ese correo']);
    exit;
}

// Generar token único
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Guardar token en la base de datos (crear tabla si no existe)
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $token, $expires]);

// Construir enlace de recuperación
$recoveryLink = BASE_URL . "reset_password.html?token=" . $token;

// Enviar correo (simplificado - usar mail() o PHPMailer en producción)
$subject = "Recuperación de contraseña - Turnos Hospital";
$message = "Hola " . $user['name'] . ",\n\n";
$message .= "Haz clic en el siguiente enlace para restablecer tu contraseña:\n";
$message .= $recoveryLink . "\n\n";
$message .= "Este enlace es válido por 1 hora.\n";
$message .= "Si no solicitaste este cambio, ignora este mensaje.\n\n";
$message .= "Saludos,\nEquipo de Turnos Hospital";

$headers = "From: no-reply@elcerritovalle.org\r\n";
$headers .= "Reply-To: soporte@elcerritovalle.org\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// En entorno de desarrollo, mostrar el enlace en consola
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    echo json_encode([
        'success' => true,
        'message' => 'Enlace generado (modo desarrollo)',
        'debug_link' => $recoveryLink
    ]);
    exit;
}

// En producción, enviar correo
if (mail($email, $subject, $message, $headers)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Error al enviar el correo. Contacta al administrador.']);
}
?>