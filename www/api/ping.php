<?php
session_start();
header('Content-Type: application/json');
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'ok', 'user_id' => $_SESSION['user_id']]);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
}
