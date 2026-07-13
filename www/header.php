<?php header('Content-Type: text/html; charset=utf-8'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title ?? 'Turnos MUNAY'; ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        body { opacity: 0; transition: opacity 0.15s; }
        body.loaded { opacity: 1; }
        .nav-menu svg { width: 24px !important; height: 24px !important; fill: currentColor; flex-shrink: 0; }
        .logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .logo img { height: 40px; width: auto; display: block; }
    </style>
</head>
<body>
<header class="app-header">
    <div class="logo">
        <a href="dashboard.php">
            <img src="img/MUNAY-removebg-preview.png" alt="MUNAY">
            <span>Turnos</span>
        </a>
    </div>
    <div class="user-info">
        <span id="userName" class="user-name">Cargando...</span>
        <span id="userRole" class="user-role">
            <?php 
                $role = $_SESSION['user_role'] ?? 'invitado';
                $display = match($role) {
                    'admin' => 'Administrador',
                    'custodio' => 'Formador',
                    'coordinator' => 'C Convivencia',
                    'nursing' => 'Enfermería',
                    'police' => 'PONAL',
                    default => 'invitado'
                };
                echo $display;
            ?>
        </span>
        <button id="btnPerfil" class="btn-icon" title="Mi perfil">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </button>
        <button id="nightModeToggle" class="night-toggle" title="Alternar modo noche">
            <span class="icon">☀️</span> <span class="label" id="themeLabel">Día</span>
        </button>
        <button id="btnLogout" class="btn-logout">Salir</button>
    </div>
</header>
<nav class="nav-menu" id="mainNav">
    <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="nav-links" id="navLinks">
        <!-- Los enlaces se generan dinámicamente con JavaScript -->
    </div>
</nav>
<main style="flex:1;padding:16px 24px;max-width:1400px;margin:0 auto;width:100%;">
