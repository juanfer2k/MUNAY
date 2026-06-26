<?php
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/assets.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title ?? 'Turnos Hospital'; ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="shortcut icon" href="favicon.ico">
    <!-- ===== LIBRERÍAS (cargadas UNA sola vez para toda la app) ===== -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- ===== ÍCONOS: la misma fuente que usa PHP (includes/icons.php), expuesta al JS ===== -->
    <script>window.MUNAY_ICONS = <?php echo json_encode(MUNAY_ICON_PATHS); ?>;</script>
    <script src="<?php echo asset_url('js/icons.js'); ?>"></script>
    <script src="<?php echo asset_url('js/common.js'); ?>"></script>
</head>
<body>
<header class="app-header">
    <div class="logo">
        <a href="index.php">
            <img src="img/MUNAY-removebg-preview.png" alt="MUNAY">
            <span>Turnos</span>
        </a>
    </div>
    <div class="user-info">
        <span id="userName" class="user-name">Cargando...</span>
        <span id="userRole" class="user-role"><?php echo $_SESSION['user_role'] ?? 'invitado'; ?></span>
        <button id="btnPerfil" class="btn-icon" title="Mi perfil"><?= munay_icon('settings', 18) ?></button>
        <button id="btnLogout" class="btn-logout">Salir</button>
    </div>
</header>
<nav class="nav-menu" id="mainNav">
    <!-- El menú se genera dinámicamente con JS (ver js/common.js → renderNav) -->
</nav>
<main>
