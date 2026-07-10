<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$titulo = $input['titulo'] ?? 'Notificación';
$mensaje = $input['mensaje'] ?? '';

// Aquí deberías obtener los tokens de los dispositivos desde la base de datos
// y enviar la notificación usando FCM o similar

// Ejemplo con Firebase (requiere librería)
/*
require_once 'vendor/autoload.php';
use Kreait\Firebase\Factory;

$factory = (new Factory)->withServiceAccount(__DIR__.'/firebase_credentials.json');
$messaging = $factory->createMessaging();

$tokens = ['token1', 'token2']; // Obtener de la BD
$message = \Kreait\Firebase\Messaging\CloudMessage::new()
    ->withNotification(['title' => $titulo, 'body' => $mensaje]);

$messaging->sendMulticast($message, $tokens);
*/

echo json_encode(['success' => true, 'message' => 'Notificación enviada (simulada)']);
?>