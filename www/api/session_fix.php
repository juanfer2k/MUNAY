<?php
session_start();
echo json_encode(['session_id' => session_id(), 'user_id' => $_SESSION['user_id'] ?? null]);
?>