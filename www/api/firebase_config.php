<?php
// firebase_config.php
require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

// Ruta al archivo de credenciales
$firebaseCredentials = __DIR__ . '/firebase-credentials.json';

if (!file_exists($firebaseCredentials)) {
    die('❌ No se encuentra el archivo de credenciales de Firebase');
}

// Inicializar Firebase
$factory = (new Factory)
    ->withServiceAccount($firebaseCredentials)
    ->withProjectId('tu-project-id'); // Reemplaza con tu Project ID

$messaging = $factory->createMessaging();

// Función para enviar a un dispositivo
function sendPushNotification($deviceToken, $title, $body, $data = []) {
    global $messaging;
    if (empty($deviceToken)) return false;
    
    try {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withToken($deviceToken);
        return $messaging->send($message);
    } catch (Exception $e) {
        error_log("Error FCM: " . $e->getMessage());
        return false;
    }
}

// Función para enviar a múltiples dispositivos
function sendPushToMultiple($tokens, $title, $body, $data = []) {
    global $messaging;
    if (empty($tokens)) return false;
    
    try {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);
        return $messaging->sendMulticast($message, $tokens);
    } catch (Exception $e) {
        error_log("Error FCM multicast: " . $e->getMessage());
        return false;
    }
}

// Función para enviar a un tópico
function sendPushToTopic($topic, $title, $body, $data = []) {
    global $messaging;
    try {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->withTopic($topic);
        return $messaging->send($message);
    } catch (Exception $e) {
        error_log("Error enviando a tópico: " . $e->getMessage());
        return false;
    }
}
?>