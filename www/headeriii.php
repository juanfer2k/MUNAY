<?php
// header.php - Cabecera común para toda la aplicación
// Con <base> dinámico para rutas relativas desde cualquier carpeta
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- ===== BASE URL DINÁMICA ===== -->
    <?php
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    // Si estamos en /api/ o /subcarpeta/, subimos un nivel
    if (strpos($base_path, '/api') !== false) {
        $base_path = dirname($base_path);
    }
    $base_url = $protocol . $host . $base_path . '/';
    ?>
    <base href="<?php echo $base_url; ?>">
    
    <title><?php echo $page_title ?? 'MUNAY - Turnos'; ?></title>
    
    <!-- ===== RECURSOS EXTERNOS (CDN) ===== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- ===== CSS PROPIO (ruta relativa al base) ===== -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <!-- ===== FAVICON ===== -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    
    <style>
        /* ===== ESTILOS MÍNIMOS (solo para evitar FOUC) ===== */
        body {
            opacity: 0;
            transition: opacity 0.15s;
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        body.loaded { opacity: 1; }
        
        /* ===== HEADER ===== */
        .app-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;  /* Sin verde */
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #1a2634;
        }
        .logo img {
            height: 40px;
            width: auto;
            display: block;
        }
        .logo span {
            font-size: 20px;
            font-weight: 700;
            color: #1a2634;
        }
        .logo span::after {
            content: "· Turnos";
            color: #5d6d7e;
            font-weight: 400;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #5d6d7e;
            flex-wrap: wrap;
        }
        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .user-role {
            background: #f0f2f5;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #2c3e50;
            font-weight: 500;
        }
        .btn-icon, .night-toggle, .btn-logout {
            background: none;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #5d6d7e;
            transition: all 0.2s;
        }
        .btn-icon:hover, .night-toggle:hover, .btn-logout:hover {
            background: #f8f9fa;
            border-color: #c0c0c0;
        }
        .btn-logout {
            color: #e74c3c;
            border-color: #fde8e8;
        }
        .btn-logout:hover {
            background: #fde8e8;
        }
        .night-toggle .icon {
            font-size: 16px;
        }
        
        /* ===== NAV ===== */
        .nav-menu {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 0 24px;
            display: flex;
            align-items: center;
        }
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            padding: 12px 0;
            cursor: pointer;
            flex-direction: column;
            gap: 4px;
        }
        .nav-toggle span {
            display: block;
            width: 24px;
            height: 2px;
            background: #2c3e50;
            border-radius: 2px;
        }
        .nav-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 8px 0;
        }
        .nav-links a {
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            color: #5d6d7e;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-links a:hover {
            background: #f0f2f5;
            color: #1a2634;
        }
        .nav-links a.active {
            background: #1ABC9C;
            color: #fff;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .app-header {
                flex-direction: column;
                align-items: stretch;
                padding: 10px 16px;
            }
            .user-info {
                justify-content: space-between;
            }
            .nav-toggle {
                display: flex;
            }
            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                padding: 8px 0;
            }
            .nav-links.open {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="logo">
            <a href="dashboard.php">
                <img src="img/MUNAY-removebg-preview.png" alt="MUNAY">
                <span>MUNAY</span>
            </a>
        </div>
        <div class="user-info">
            <span id="userName" class="user-name">Cargando...</span>
            <span id="userRole" class="user-role">
                <?php 
                    $role = $_SESSION['user_role'] ?? 'invitado';
                    $display = match($role) {
                        'admin' => 'Administrador',
                        'custodio' => 'Custodio',
                        'coordinator' => 'C Convivencia',
                        'nursing' => 'Enfermería',
                        'police' => 'PONAL',
                        default => 'invitado'
                    };
                    echo $display;
                ?>
            </span>
            <button id="btnPerfil" class="btn-icon" title="Mi perfil">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </button>
            <button id="nightModeToggle" class="night-toggle" title="Alternar modo noche">
                <span class="icon">☀️</span> <span class="label">Día</span>
            </button>
            <button id="btnLogout" class="btn-logout">Salir</button>
        </div>
    </header>
    
    <nav class="nav-menu" id="mainNav">
        <button class="nav-toggle" id="navToggle" aria-label="Menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <!-- Los enlaces se generan dinámicamente con JavaScript -->
        </div>
    </nav>
    
    <main style="flex:1; padding:16px 24px; max-width:1400px; margin:0 auto; width:100%;">