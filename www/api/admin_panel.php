<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
require_once 'api/config.php';
$page_title = 'Panel de Control';
include 'header.php';
?>

<style>
/* ===== VARIABLES ===== */
:root {
    --on:  #1E8449;
    --off: #922B21;
    --warn: #B7950B;
    --blue: #1a5276;
    --border: #e0e6ed;
    --surface: #f8fafc;
    --text: #2c3e50;
    --muted: #5d6d7e;
}

/* ===== LAYOUT ===== */
.cp-grid { display:grid; grid-template-columns:260px 1fr; gap:0; min-height:calc(100vh - 120px); }
.cp-sidebar { background:#0b2a3e; color:#fff; padding:24px 0; }
.cp-sidebar .section-label { font-size:10px; font-weight:700; letter-spacing:1.5px; color:#4a7a99; padding:16px 24px 6px; text-transform:uppercase; }
.cp-nav-item { display:flex; align-items:center; gap:10px; padding:11px 24px; cursor:pointer; font-size:14px; color:#a8c0d4; transition:.15s; border-left:3px solid transparent; }
.cp-nav-item:hover { background:rgba(255,255,255,.06); color:#fff; }
.cp-nav-item.active { background:rgba(46,134,193,.18); color:#fff; border-left-color:#2E86C1; }
.cp-nav-item svg { width:17px; height:17px; flex-shrink:0; }
.cp-content { background:#f0f4f8; padding:28px; overflow-y:auto; }

/* ===== SECTION PANELS ===== */
.cp-panel { display:none; }
.cp-panel.active { display:block; }
.panel-title { font-size:20px; font-weight:700; color:var(--blue); margin-bottom:20px; display:flex; align-items:center; gap:10px; }

/* ===== STATS ROW ===== */
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
.stat-box { background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 1px 4px rgba(0,0,0,.07); }
.stat-box .num { font-size:28px; font-weight:800; }
.stat-box .lbl { font-size:12px; color:var(--muted); margin-top:2px; }
.stat-box.blue .num { color:#2E86C1; }
.stat-box.green .num { color:var(--on); }
.stat-box.orange .num { color:var(--warn); }
.stat-box.red .num { color:var(--off); }

/* ===== CARDS ===== */
.card2 { background:#fff; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,.07); margin-bottom:20px; overflow:hidden; }
.card2-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border); }
.card2-header h3 { font-size:15px; font-weight:700; color:var(--text); }
.card2-body { padding:20px; }

/* ===== FEATURE TOGGLES ===== */
.user-toggle-row { display:grid; grid-template-columns:200px 1fr; gap:0; align-items:start; border-bottom:1px solid var(--border); }
.user-toggle-row:last-child { border-bottom:none; }
.user-col { padding:14px 20px; border-right:1px solid var(--border); }
.user-name-lbl { font-weight:600; font-size:14px; color:var(--text); }
.user-role-lbl { font-size:11px; color:var(--muted); margin-top:2px; }
.toggles-col { padding:12px 20px; display:flex; flex-wrap:wrap; gap:10px; }

.toggle-chip { display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; border:2px solid transparent; transition:.2s; user-select:none; }
.toggle-chip.on  { background:#e9f7ef; color:var(--on);  border-color:#a9dfbf; }
.toggle-chip.off { background:#fdedec; color:var(--off); border-color:#f1948a; }
.toggle-chip .dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.toggle-chip.on  .dot { background:var(--on); }
.toggle-chip.off .dot { background:var(--off); }

/* ===== TABLES ===== */
.t2 { width:100%; border-collapse:collapse; font-size:13px; }
.t2 thead th { background:#f0f4f8; padding:10px 14px; text-align:left; font-weight:700; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.t2 tbody td { padding:11px 14px; border-bottom:1px solid var(--border); color:var(--text); vertical-align:middle; }
.t2 tbody tr:last-child td { border-bottom:none; }
.t2 tbody tr:hover td { background:#f8fafc; }

/* ===== BADGE ===== */
.badge2 { display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
.badge2.pending    { background:#fef9e7; color:#B7950B; }
.badge2.approved   { background:#e9f7ef; color:#1E8449; }
.badge2.rejected   { background:#fdedec; color:#922B21; }
.badge2.completed  { background:#e9f7ef; color:#1E8449; }
.badge2.in_progress{ background:#ebf5fb; color:#1a5276; }
.badge2.cancelled  { background:#f2f3f4; color:#7f8c8d; }
.badge2.admin      { background:#e8daef; color:#6c3483; }
.badge2.caregiver  { background:#d6eaf8; color:#1a5276; }
.badge2.police     { background:#fde8d8; color:#a04000; }

/* ===== FORMS ===== */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.fg { display:flex; flex-direction:column; gap:5px; }
.fg.full { grid-column:1/-1; }
.fg label { font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; }
.fg input, .fg select, .fg textarea {
    padding:10px 12px; border:2px solid var(--border); border-radius:9px;
    font-size:14px; background:var(--surface); transition:.15s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus {
    border-color:#2E86C1; outline:none; background:#fff;
}

/* ===== BUTTONS ===== */
.btn2 { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:.15s; }
.btn2.primary { background:#1a5276; color:#fff; }
.btn2.primary:hover { background:#0e3a54; }
.btn2.success { background:#1E8449; color:#fff; }
.btn2.success:hover { background:#176039; }
.btn2.danger  { background:#922B21; color:#fff; }
.btn2.danger:hover  { background:#6b1f18; }
.btn2.outline { background:#fff; color:var(--text); border:2px solid var(--border); }
.btn2.outline:hover { border-color:#2E86C1; color:#2E86C1; }
.btn2.sm { padding:5px 12px; font-size:12px; }

/* ===== MODAL ===== */
.modal2-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
.modal2-overlay.open { display:flex; }
.modal2 { background:#fff; border-radius:16px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 64px rgba(0,0,0,.2); }
.modal2-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--border); position:sticky; top:0; background:#fff; z-index:1; }
.modal2-header h2 { font-size:17px; font-weight:700; color:var(--text); }
.modal2-close { background:none; border:none; font-size:22px; cursor:pointer; color:var(--muted); line-height:1; }
.modal2-body { padding:24px; }
.modal2-footer { display:flex; gap:10px; justify-content:flex-end; padding:16px 24px; border-top:1px solid var(--border); }

/* ===== SEARCH ===== */
.search-row { display:flex; gap:10px; margin-bottom:16px; align-items:center; }
.search-row input { flex:1; padding:9px 14px; border:2px solid var(--border); border-radius:9px; font-size:13px; }
.search-row input:focus { border-color:#2E86C1; outline:none; }

/* ===== TOAST ===== */
#toast { position:fixed; bottom:28px; right:28px; z-index:9999; display:flex; flex-direction:column; gap:8px; }
.toast-item { padding:13px 20px; border-radius:10px; font-size:13px; font-weight:600; color:#fff; box-shadow:0 4px 16px rgba(0,0,0,.18); animation:slideIn .25s ease; }
.toast-item.ok  { background:#1E8449; }
.toast-item.err { background:#922B21; }
@keyframes slideIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .cp-grid { grid-template-columns:1fr; }
    .cp-sidebar { display:flex; overflow-x:auto; padding:0; }
    .cp-nav-item { flex-direction:column; gap:4px; font-size:10px; padding:12px 14px; border-left:none; border-bottom:3px solid transparent; white-space:nowrap; }
    .cp-nav-item.active { border-bottom-color:#2E86C1; border-left-color:transparent; }
    .stats-row { grid-template-columns:1fr 1fr; }
    .form-grid { grid-template-columns:1fr; }
    .user-toggle-row { grid-template-columns:1fr; }
    .user-col { border-right:none; border-bottom:1px solid var(--border); }
}
</style>

<div class="cp-grid">

<!-- ===== SIDEBAR ===== -->
<aside class="cp-sidebar">
    <div class="section-label">Control</div>
    <div class="cp-nav-item active" data-panel="overview">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Resumen
    </div>
    <div class="cp-nav-item" data-panel="features">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h10c2.76 0 5-2.24 5-5s-2.24-5-5-5zm0 8c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>
        Funciones por Usuario
    </div>

    <div class="section-label">Gestión</div>
    <div class="cp-nav-item" data-panel="users">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Usuarios
    </div>
    <div class="cp-nav-item" data-panel="shifts">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
        Turnos
    </div>
    <div class="cp-nav-item" data-panel="expenses">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        Gastos
    </div>
    <div class="cp-nav-item" data-panel="changes">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
        Cambios de Turno
    </div>

    <div class="section-label">Cuenta</div>
    <div class="cp-nav-item" data-panel="profile">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        Mi Perfil
    </div>
</aside>

<!-- ===== MAIN CONTENT ===== -->
<div class="cp-content">

    <!-- ======= RESUMEN ======= -->
    <div class="cp-panel active" id="panel-overview">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Resumen del Sistema
        </div>
        <div class="stats-row">
            <div class="stat-box blue"><div class="num" id="ov-users">—</div><div class="lbl">Usuarios activos</div></div>
            <div class="stat-box green"><div class="num" id="ov-shifts">—</div><div class="lbl">Turnos hoy</div></div>
            <div class="stat-box orange"><div class="num" id="ov-expenses">—</div><div class="lbl">Gastos pendientes</div></div>
            <div class="stat-box red"><div class="num" id="ov-changes">—</div><div class="lbl">Cambios pendientes</div></div>
        </div>
        <div class="card2">
            <div class="card2-header"><h3>Actividad Reciente</h3></div>
            <div class="card2-body" id="recent-activity">Cargando...</div>
        </div>
    </div>

    <!-- ======= FUNCIONES POR USUARIO ======= -->
    <div class="cp-panel" id="panel-features">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M17 7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h10c2.76 0 5-2.24 5-5s-2.24-5-5-5zm0 8c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>
            Funciones por Usuario
        </div>
        <div class="card2">
            <div class="card2-header">
                <h3>Control de acceso a funciones</h3>
                <span style="font-size:12px;color:var(--muted);">Los cambios se aplican en tiempo real</span>
            </div>
            <div class="card2-body" style="padding:0;" id="features-table">
                <div style="padding:20px;color:var(--muted);">Cargando usuarios...</div>
            </div>
        </div>
        <!-- Leyenda de funciones -->
        <div class="card2" style="margin-top:0;">
            <div class="card2-body">
                <p style="font-size:12px;color:var(--muted);margin-bottom:10px;font-weight:700;">Descripción de funciones</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;font-size:12px;color:var(--muted);">
                    <div><strong style="color:var(--text);">GPS</strong> — Compartir ubicación en mapa</div>
                    <div><strong style="color:var(--text);">Gastos</strong> — Registrar viáticos/gastos</div>
                    <div><strong style="color:var(--text);">Cambios</strong> — Solicitar cambio de turno</div>
                    <div><strong style="color:var(--text);">Alertas</strong> — Recibir notificaciones de alerta</div>
                    <div><strong style="color:var(--text);">Mapa</strong> — Ver mapa con ubicaciones</div>
                    <div><strong style="color:var(--text);">Rutas</strong> — Ver y gestionar rutas asignadas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= USUARIOS ======= -->
    <div class="cp-panel" id="panel-users">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            Usuarios
        </div>
        <div class="card2">
            <div class="card2-header">
                <h3>Lista de usuarios</h3>
                <button class="btn2 primary" onclick="abrirModalUsuario()">+ Nuevo usuario</button>
            </div>
            <div class="card2-body">
                <div class="search-row">
                    <input type="text" id="searchUsers" placeholder="Buscar por nombre, email o rol..." oninput="filtrarTabla('usersTbody', this.value)">
                </div>
                <div style="overflow-x:auto;">
                    <table class="t2">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Grupo</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody id="usersTbody"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted);">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= TURNOS ======= -->
    <div class="cp-panel" id="panel-shifts">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
            Turnos
        </div>
        <div class="card2">
            <div class="card2-header">
                <h3>Gestión de turnos</h3>
                <button class="btn2 primary" onclick="abrirModalTurno()">+ Nuevo turno</button>
            </div>
            <div class="card2-body">
                <div class="search-row">
                    <input type="text" id="searchShifts" placeholder="Buscar por cuidador, fecha, ubicación..." oninput="filtrarTabla('shiftsTbody', this.value)">
                    <select id="filterShiftDate" onchange="cargarTurnos()" style="padding:9px 12px;border:2px solid var(--border);border-radius:9px;font-size:13px;background:var(--surface);">
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
                <div style="overflow-x:auto;">
                    <table class="t2">
                        <thead><tr><th>ID</th><th>Cuidador</th><th>Fecha</th><th>Horario</th><th>Ubicación</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody id="shiftsTbody"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted);">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= GASTOS ======= -->
    <div class="cp-panel" id="panel-expenses">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            Gastos y Viáticos
        </div>
        <div class="card2">
            <div class="card2-header">
                <h3>Gastos reportados</h3>
                <div style="display:flex;gap:8px;">
                    <select id="filterExpenseStatus" onchange="cargarGastos()" style="padding:7px 10px;border:2px solid var(--border);border-radius:8px;font-size:13px;">
                        <option value="">Todos</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobados</option>
                        <option value="rejected">Rechazados</option>
                    </select>
                </div>
            </div>
            <div class="card2-body">
                <div style="overflow-x:auto;">
                    <table class="t2">
                        <thead><tr><th>ID</th><th>Cuidador</th><th>Tipo</th><th>Monto</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody id="expensesTbody"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted);">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= CAMBIOS DE TURNO ======= -->
    <div class="cp-panel" id="panel-changes">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
            Solicitudes de Cambio de Turno
        </div>
        <div class="card2">
            <div class="card2-header">
                <h3>Solicitudes pendientes y resueltas</h3>
            </div>
            <div class="card2-body">
                <div style="overflow-x:auto;">
                    <table class="t2">
                        <thead><tr><th>ID</th><th>Cuidador</th><th>Turno original</th><th>Fecha solicitada</th><th>Hora</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody id="changesTbody"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--muted);">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= PERFIL ======= -->
    <div class="cp-panel" id="panel-profile">
        <div class="panel-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#1a5276"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            Mi Perfil
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="card2">
                <div class="card2-header"><h3>Datos personales</h3></div>
                <div class="card2-body">
                    <div class="fg" style="margin-bottom:14px;"><label>Nombre</label><input type="text" id="pNombre" placeholder="Tu nombre"></div>
                    <div class="fg" style="margin-bottom:14px;"><label>Email</label><input type="email" id="pEmail" placeholder="correo@ejemplo.com"></div>
                    <div class="fg" style="margin-bottom:14px;"><label>Teléfono</label><input type="tel" id="pTelefono" placeholder="3001234567"></div>
                    <div class="fg" style="margin-bottom:20px;"><label>Rol</label><input type="text" id="pRol" readonly style="background:#f0f4f8;"></div>
                    <button class="btn2 primary" onclick="guardarPerfil()">Guardar cambios</button>
                    <div id="profileMsg" style="margin-top:12px;font-size:13px;"></div>
                </div>
            </div>
            <div class="card2">
                <div class="card2-header"><h3>Cambiar contraseña</h3></div>
                <div class="card2-body">
                    <div class="fg" style="margin-bottom:14px;"><label>Contraseña actual</label><input type="password" id="pPassActual" placeholder="••••••••"></div>
                    <div class="fg" style="margin-bottom:14px;"><label>Nueva contraseña</label><input type="password" id="pPassNueva" placeholder="Mínimo 6 caracteres"></div>
                    <div class="fg" style="margin-bottom:20px;"><label>Confirmar nueva</label><input type="password" id="pPassConfirmar" placeholder="Repite la contraseña"></div>
                    <button class="btn2 success" onclick="cambiarPassword()">Actualizar contraseña</button>
                    <div id="passMsg" style="margin-top:12px;font-size:13px;"></div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /cp-content -->
</div><!-- /cp-grid -->

<!-- ======= MODAL USUARIO ======= -->
<div class="modal2-overlay" id="modalUsuario">
    <div class="modal2">
        <div class="modal2-header">
            <h2 id="modalUsuarioTitulo">Nuevo Usuario</h2>
            <button class="modal2-close" onclick="cerrarModal2('modalUsuario')">&times;</button>
        </div>
        <div class="modal2-body">
            <input type="hidden" id="editUserId">
            <div class="form-grid">
                <div class="fg"><label>Nombre *</label><input type="text" id="inputNombre" placeholder="Nombre completo"></div>
                <div class="fg"><label>Email *</label><input type="email" id="inputEmail" placeholder="correo@ejemplo.com"></div>
                <div class="fg"><label>Teléfono *</label><input type="tel" id="inputTelefono" placeholder="3001234567"></div>
                <div class="fg"><label>Contraseña</label><input type="password" id="inputPassword" placeholder="Dejar en blanco para no cambiar"></div>
                <div class="fg"><label>Rol</label>
                    <select id="inputRol">
                        <option value="caregiver">Cuidador</option>
                        <option value="police">Policía</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="fg"><label>Grupo</label>
                    <select id="inputGrupo">
                        <option value="A">A (Diurno)</option>
                        <option value="B">B (Tarde/Noche)</option>
                        <option value="C">C (Médico)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal2-footer">
            <button class="btn2 outline" onclick="cerrarModal2('modalUsuario')">Cancelar</button>
            <button class="btn2 success" onclick="guardarUsuario()">Guardar usuario</button>
        </div>
    </div>
</div>

<!-- ======= MODAL TURNO ======= -->
<div class="modal2-overlay" id="modalTurno">
    <div class="modal2">
        <div class="modal2-header">
            <h2 id="modalTurnoTitulo">Nuevo Turno</h2>
            <button class="modal2-close" onclick="cerrarModal2('modalTurno')">&times;</button>
        </div>
        <div class="modal2-body">
            <input type="hidden" id="editTurnoId">
            <div class="form-grid">
                <div class="fg full"><label>Cuidador</label><select id="selectCuidador"></select></div>
                <div class="fg"><label>Fecha</label><input type="date" id="inputFecha"></div>
                <div class="fg"><label>Hora Inicio</label><input type="time" id="inputInicio"></div>
                <div class="fg"><label>Hora Fin</label><input type="time" id="inputFin"></div>
                <div class="fg"><label>Ubicación</label><input type="text" id="inputUbicacion" placeholder="Clínica, hospital, etc."></div>
                <div class="fg"><label>Menor a cargo</label><input type="text" id="inputMenor" placeholder="ID o nombre"></div>
                <div class="fg"><label>Tipo</label>
                    <select id="inputTipo">
                        <option value="base">Base</option>
                        <option value="extra">Extra</option>
                        <option value="medical">Médico</option>
                        <option value="weekend_day">Fin de semana - Día</option>
                        <option value="weekend_night">Fin de semana - Noche</option>
                    </select>
                </div>
                <div class="fg"><label>Estado</label>
                    <select id="inputEstado">
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En progreso</option>
                        <option value="completed">Completado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal2-footer">
            <button class="btn2 outline" onclick="cerrarModal2('modalTurno')">Cancelar</button>
            <button class="btn2 success" onclick="guardarTurno()">Guardar turno</button>
        </div>
    </div>
</div>

<!-- ======= TOAST ======= -->
<div id="toast"></div>

<script>
(function(){
'use strict';

// ===== NAVEGACIÓN =====
var navItems = document.querySelectorAll('.cp-nav-item');
navItems.forEach(function(item){
    item.addEventListener('click', function(){
        navItems.forEach(function(n){ n.classList.remove('active'); });
        item.classList.add('active');
        var panel = item.dataset.panel;
        document.querySelectorAll('.cp-panel').forEach(function(p){ p.classList.remove('active'); });
        document.getElementById('panel-' + panel).classList.add('active');
        // Cargar datos al entrar a cada panel
        if(panel === 'overview')  cargarResumen();
        if(panel === 'features')  cargarFeatures();
        if(panel === 'users')     cargarUsuarios();
        if(panel === 'shifts')    cargarTurnos();
        if(panel === 'expenses')  cargarGastos();
        if(panel === 'changes')   cargarCambios();
        if(panel === 'profile')   cargarPerfil();
    });
});

// ===== MODAL HELPERS =====
function abrirModal2(id){ document.getElementById(id).classList.add('open'); }
function cerrarModal2(id){ document.getElementById(id).classList.remove('open'); }
window.cerrarModal2 = cerrarModal2;

// ===== TOAST =====
function toast(msg, tipo){
    var t = document.createElement('div');
    t.className = 'toast-item ' + (tipo||'ok');
    t.textContent = msg;
    document.getElementById('toast').appendChild(t);
    setTimeout(function(){ t.remove(); }, 3500);
}

// ===== FILTRO DE TABLA =====
function filtrarTabla(tbodyId, q){
    q = q.toLowerCase();
    var rows = document.getElementById(tbodyId).querySelectorAll('tr');
    rows.forEach(function(r){
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
window.filtrarTabla = filtrarTabla;

// ===== BADGE HELPER =====
function badge(val, map){
    var label = map && map[val] ? map[val] : val;
    return '<span class="badge2 '+val+'">'+label+'</span>';
}
var statusMap = { pending:'Pendiente', approved:'Aprobado', rejected:'Rechazado',
                  completed:'Completado', in_progress:'En progreso', cancelled:'Cancelado' };
var roleMap   = { admin:'Admin', caregiver:'Cuidador', police:'Policía' };

// =====================
// ===== RESUMEN =====
// =====================
function cargarResumen(){
    fetch('api/admin_stats.php')
        .then(function(r){ return r.json(); })
        .then(function(d){
            document.getElementById('ov-users').textContent    = d.users    || 0;
            document.getElementById('ov-shifts').textContent   = d.shifts   || 0;
            document.getElementById('ov-expenses').textContent = d.expenses || 0;
            document.getElementById('ov-changes').textContent  = d.changes  || 0;
            // Actividad reciente
            var html = '<table class="t2"><thead><tr><th>Tipo</th><th>Descripción</th><th>Usuario</th><th>Fecha</th></tr></thead><tbody>';
            if(d.recent && d.recent.length){
                d.recent.forEach(function(r){
                    html += '<tr><td>'+badge(r.type, {shift:'Turno',expense:'Gasto',change:'Cambio'})+'</td>';
                    html += '<td>'+r.description+'</td><td>'+r.user+'</td><td>'+r.date+'</td></tr>';
                });
            } else {
                html += '<tr><td colspan="4" style="text-align:center;color:var(--muted);">Sin actividad reciente</td></tr>';
            }
            html += '</tbody></table>';
            document.getElementById('recent-activity').innerHTML = html;
        })
        .catch(function(){ document.getElementById('recent-activity').innerHTML = '<p style="color:var(--off);">Error al cargar actividad.</p>'; });
}

// =====================
// ===== FEATURES =====
// =====================
var FEATURES = [
    { key:'gps',     label:'GPS'     },
    { key:'gastos',  label:'Gastos'  },
    { key:'cambios', label:'Cambios' },
    { key:'alertas', label:'Alertas' },
    { key:'mapa',    label:'Mapa'    },
    { key:'rutas',   label:'Rutas'   }
];

function cargarFeatures(){
    fetch('api/users.php')
        .then(function(r){ return r.json(); })
        .then(function(users){
            // Para cada usuario cargar sus features
            var promises = users.map(function(u){
                return fetch('api/user_features.php?user_id='+u.id)
                    .then(function(r){ return r.json(); })
                    .then(function(f){ u.features = f; return u; })
                    .catch(function(){ u.features = {}; return u; });
            });
            return Promise.all(promises);
        })
        .then(function(users){
            var html = '';
            users.forEach(function(u){
                html += '<div class="user-toggle-row">';
                html += '<div class="user-col"><div class="user-name-lbl">'+u.name+'</div>';
                html += '<div class="user-role-lbl">'+badge(u.role, roleMap)+'</div></div>';
                html += '<div class="toggles-col">';
                FEATURES.forEach(function(f){
                    var on = u.features[f.key] !== false; // default ON
                    html += '<span class="toggle-chip '+(on?'on':'off')+'" ';
                    html += 'onclick="toggleFeature('+u.id+',\''+f.key+'\',this)" ';
                    html += 'data-uid="'+u.id+'" data-feat="'+f.key+'" data-on="'+(on?'1':'0')+'">';
                    html += '<span class="dot"></span>'+f.label+'</span>';
                });
                html += '</div></div>';
            });
            document.getElementById('features-table').innerHTML = html || '<div style="padding:20px;color:var(--muted);">No hay usuarios.</div>';
        })
        .catch(function(){ document.getElementById('features-table').innerHTML = '<div style="padding:20px;color:var(--off);">Error al cargar funciones.</div>'; });
}

function toggleFeature(userId, feat, el){
    var currentlyOn = el.dataset.on === '1';
    var newVal = !currentlyOn;
    fetch('api/user_features.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ user_id: userId, feature: feat, enabled: newVal })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){
            el.classList.toggle('on', newVal);
            el.classList.toggle('off', !newVal);
            el.dataset.on = newVal ? '1' : '0';
            toast((newVal ? '✅ ' : '🔴 ') + feat + ' ' + (newVal ? 'activado' : 'desactivado'), newVal ? 'ok' : 'err');
        } else {
            toast('Error: '+(d.error||'No se pudo cambiar'), 'err');
        }
    })
    .catch(function(){ toast('Error de conexión','err'); });
}
window.toggleFeature = toggleFeature;

// =====================
// ===== USUARIOS =====
// =====================
function cargarUsuarios(){
    fetch('api/users.php')
        .then(function(r){ return r.json(); })
        .then(function(data){
            var html = '';
            data.forEach(function(u){
                html += '<tr>';
                html += '<td style="color:var(--muted);font-size:12px;">#'+u.id+'</td>';
                html += '<td><strong>'+u.name+'</strong></td>';
                html += '<td>'+u.email+'</td>';
                html += '<td>'+(u.phone||'—')+'</td>';
                html += '<td>'+badge(u.role, roleMap)+'</td>';
                html += '<td>'+(u.group_type||'—')+'</td>';
                html += '<td>'+badge(u.status||'active', {active:'Activo',inactive:'Inactivo'})+'</td>';
                html += '<td style="white-space:nowrap;">';
                html += '<button class="btn2 outline sm" onclick="editarUsuario('+JSON.stringify(u)+')">Editar</button> ';
                html += '<button class="btn2 danger sm" onclick="eliminarUsuario('+u.id+',\''+u.name+'\')">Eliminar</button>';
                html += '</td></tr>';
            });
            document.getElementById('usersTbody').innerHTML = html || '<tr><td colspan="8" style="text-align:center;color:var(--muted);">Sin usuarios registrados.</td></tr>';
        })
        .catch(function(){ document.getElementById('usersTbody').innerHTML = '<tr><td colspan="8" style="color:var(--off);text-align:center;">Error al cargar usuarios.</td></tr>'; });
}

function abrirModalUsuario(){
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('editUserId').value = '';
    ['inputNombre','inputEmail','inputTelefono','inputPassword'].forEach(function(id){ document.getElementById(id).value = ''; });
    document.getElementById('inputRol').value = 'caregiver';
    document.getElementById('inputGrupo').value = 'A';
    abrirModal2('modalUsuario');
}
window.abrirModalUsuario = abrirModalUsuario;

function editarUsuario(u){
    document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
    document.getElementById('editUserId').value = u.id;
    document.getElementById('inputNombre').value = u.name || '';
    document.getElementById('inputEmail').value = u.email || '';
    document.getElementById('inputTelefono').value = u.phone || '';
    document.getElementById('inputPassword').value = '';
    document.getElementById('inputRol').value = u.role || 'caregiver';
    document.getElementById('inputGrupo').value = u.group_type || 'A';
    abrirModal2('modalUsuario');
}
window.editarUsuario = editarUsuario;

function guardarUsuario(){
    var id       = document.getElementById('editUserId').value;
    var nombre   = document.getElementById('inputNombre').value.trim();
    var email    = document.getElementById('inputEmail').value.trim();
    var telefono = document.getElementById('inputTelefono').value.trim();
    var password = document.getElementById('inputPassword').value;
    var rol      = document.getElementById('inputRol').value;
    var grupo    = document.getElementById('inputGrupo').value;

    if(!nombre || !email || !telefono){ toast('Completa los campos obligatorios.','err'); return; }

    var body = { name:nombre, email:email, phone:telefono, role:rol, group_type:grupo };
    if(password) body.password = password;
    if(id) body.id = parseInt(id);

    fetch('api/users.php', {
        method: id ? 'PUT' : 'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(body)
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cerrarModal2('modalUsuario'); cargarUsuarios(); toast(id ? 'Usuario actualizado.' : 'Usuario creado.'); }
        else { toast('Error: '+(d.error||'No se pudo guardar.'),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.guardarUsuario = guardarUsuario;

function eliminarUsuario(id, nombre){
    if(!confirm('¿Eliminar al usuario "'+nombre+'"? Esta acción no se puede deshacer.')) return;
    fetch('api/users.php', {
        method:'DELETE',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ id:id })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cargarUsuarios(); toast('Usuario eliminado.'); }
        else { toast('Error: '+(d.error||'No se pudo eliminar.'),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.eliminarUsuario = eliminarUsuario;

// =====================
// ===== TURNOS =====
// =====================
function cargarTurnos(){
    var rango = document.getElementById('filterShiftDate') ? document.getElementById('filterShiftDate').value : 'today';
    fetch('api/shifts.php?range='+rango)
        .then(function(r){ return r.json(); })
        .then(function(data){
            var html = '';
            data.forEach(function(s){
                html += '<tr>';
                html += '<td style="color:var(--muted);font-size:12px;">#'+s.id+'</td>';
                html += '<td><strong>'+(s.caregiver_name||s.caregiver_id)+'</strong></td>';
                html += '<td>'+s.shift_date+'</td>';
                html += '<td>'+s.start_time+' – '+s.end_time+'</td>';
                html += '<td>'+(s.location||'—')+'</td>';
                html += '<td>'+(s.shift_type||s.type||'—')+'</td>';
                html += '<td>'+badge(s.status, statusMap)+'</td>';
                html += '<td style="white-space:nowrap;">';
                html += '<button class="btn2 outline sm" onclick="editarTurno('+JSON.stringify(s)+')">Editar</button> ';
                html += '<button class="btn2 danger sm" onclick="eliminarTurno('+s.id+')">Eliminar</button>';
                html += '</td></tr>';
            });
            document.getElementById('shiftsTbody').innerHTML = html || '<tr><td colspan="8" style="text-align:center;color:var(--muted);">Sin turnos.</td></tr>';
        })
        .catch(function(){ document.getElementById('shiftsTbody').innerHTML = '<tr><td colspan="8" style="color:var(--off);text-align:center;">Error al cargar turnos.</td></tr>'; });
}
window.cargarTurnos = cargarTurnos;

function cargarSelectCuidadores(){
    fetch('api/users.php?role=caregiver')
        .then(function(r){ return r.json(); })
        .then(function(data){
            var opts = data.map(function(u){ return '<option value="'+u.id+'">'+u.name+'</option>'; }).join('');
            document.getElementById('selectCuidador').innerHTML = opts;
        });
}

function abrirModalTurno(){
    document.getElementById('modalTurnoTitulo').textContent = 'Nuevo Turno';
    document.getElementById('editTurnoId').value = '';
    ['inputFecha','inputInicio','inputFin','inputUbicacion','inputMenor'].forEach(function(id){ document.getElementById(id).value = ''; });
    document.getElementById('inputTipo').value = 'base';
    document.getElementById('inputEstado').value = 'pending';
    cargarSelectCuidadores();
    abrirModal2('modalTurno');
}
window.abrirModalTurno = abrirModalTurno;

function editarTurno(s){
    document.getElementById('modalTurnoTitulo').textContent = 'Editar Turno';
    document.getElementById('editTurnoId').value = s.id;
    cargarSelectCuidadores();
    setTimeout(function(){
        document.getElementById('selectCuidador').value = s.caregiver_id || '';
    }, 300);
    document.getElementById('inputFecha').value = s.shift_date || '';
    document.getElementById('inputInicio').value = s.start_time || '';
    document.getElementById('inputFin').value = s.end_time || '';
    document.getElementById('inputUbicacion').value = s.location || '';
    document.getElementById('inputMenor').value = s.minor_id || '';
    document.getElementById('inputTipo').value = s.shift_type || s.type || 'base';
    document.getElementById('inputEstado').value = s.status || 'pending';
    abrirModal2('modalTurno');
}
window.editarTurno = editarTurno;

function guardarTurno(){
    var id = document.getElementById('editTurnoId').value;
    var body = {
        caregiver_id: parseInt(document.getElementById('selectCuidador').value),
        shift_date:   document.getElementById('inputFecha').value,
        start_time:   document.getElementById('inputInicio').value,
        end_time:     document.getElementById('inputFin').value,
        location:     document.getElementById('inputUbicacion').value.trim(),
        minor_id:     document.getElementById('inputMenor').value.trim(),
        shift_type:   document.getElementById('inputTipo').value,
        status:       document.getElementById('inputEstado').value
    };
    if(!body.caregiver_id || !body.shift_date || !body.start_time || !body.end_time){
        toast('Cuidador, fecha y horario son obligatorios.','err'); return;
    }
    if(id) body.id = parseInt(id);
    fetch('api/shifts.php', {
        method: id ? 'PUT' : 'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(body)
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cerrarModal2('modalTurno'); cargarTurnos(); toast(id ? 'Turno actualizado.' : 'Turno creado.'); }
        else { toast('Error: '+(d.error||'No se pudo guardar.'),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.guardarTurno = guardarTurno;

function eliminarTurno(id){
    if(!confirm('¿Eliminar este turno?')) return;
    fetch('api/shifts.php', {
        method:'DELETE', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id})
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cargarTurnos(); toast('Turno eliminado.'); }
        else { toast('Error: '+(d.error||'No se pudo eliminar.'),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.eliminarTurno = eliminarTurno;

// =====================
// ===== GASTOS =====
// =====================
function cargarGastos(){
    var status = document.getElementById('filterExpenseStatus') ? document.getElementById('filterExpenseStatus').value : '';
    var url = 'api/expenses.php' + (status ? '?status='+status : '');
    fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){
            var html = '';
            data.forEach(function(e){
                html += '<tr>';
                html += '<td style="color:var(--muted);font-size:12px;">#'+e.id+'</td>';
                html += '<td><strong>'+(e.caregiver_name||'—')+'</strong></td>';
                html += '<td>'+(e.type||e.expense_type||'—')+'</td>';
                html += '<td><strong>$'+parseFloat(e.amount||0).toLocaleString('es-CO')+'</strong></td>';
                html += '<td>'+(e.description||'—')+'</td>';
                html += '<td>'+(e.created_at||'—').split(' ')[0]+'</td>';
                html += '<td>'+badge(e.status||'pending', statusMap)+'</td>';
                html += '<td style="white-space:nowrap;">';
                if((e.status||'pending')==='pending'){
                    html += '<button class="btn2 success sm" onclick="resolverGasto('+e.id+',\'approved\')">✓ Aprobar</button> ';
                    html += '<button class="btn2 danger sm"  onclick="resolverGasto('+e.id+',\'rejected\')">✗ Rechazar</button>';
                }
                html += '</td></tr>';
            });
            document.getElementById('expensesTbody').innerHTML = html || '<tr><td colspan="8" style="text-align:center;color:var(--muted);">Sin gastos.</td></tr>';
        })
        .catch(function(){ document.getElementById('expensesTbody').innerHTML = '<tr><td colspan="8" style="color:var(--off);text-align:center;">Error al cargar gastos.</td></tr>'; });
}
window.cargarGastos = cargarGastos;

function resolverGasto(id, status){
    fetch('api/expenses.php', {
        method:'PATCH',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ id:id, status:status })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cargarGastos(); toast(status==='approved' ? 'Gasto aprobado.' : 'Gasto rechazado.', status==='approved'?'ok':'err'); }
        else { toast('Error: '+(d.error||'No se pudo actualizar.'),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.resolverGasto = resolverGasto;

// =====================
// ===== CAMBIOS =====
// =====================
function cargarCambios(){
    fetch('api/shift_changes.php')
        .then(function(r){ return r.json(); })
        .then(function(data){
            var html = '';
            data.forEach(function(c){
                html += '<tr>';
                html += '<td style="color:var(--muted);font-size:12px;">#'+c.id+'</td>';
                html += '<td><strong>'+(c.caregiver_name||'—')+'</strong></td>';
                html += '<td>'+(c.shift_date||'—')+' '+((c.start_time||'')+'–'+(c.end_time||''))+'</td>';
                html += '<td>'+(c.requested_date||'—')+'</td>';
                html += '<td>'+(c.requested_time||'—')+'</td>';
                html += '<td style="max-width:160px;font-size:12px;">'+(c.reason||'—')+'</td>';
                html += '<td>'+badge(c.status||'pending', statusMap)+'</td>';
                html += '<td style="white-space:nowrap;">';
                if((c.status||'pending')==='pending'){
                    html += '<button class="btn2 success sm" onclick="resolverCambio('+c.id+',\'approved\')">✓ Aprobar</button> ';
                    html += '<button class="btn2 danger sm"  onclick="resolverCambio('+c.id+',\'rejected\')">✗ Rechazar</button>';
                }
                html += '</td></tr>';
            });
            document.getElementById('changesTbody').innerHTML = html || '<tr><td colspan="8" style="text-align:center;color:var(--muted);">Sin solicitudes.</td></tr>';
        })
        .catch(function(){ document.getElementById('changesTbody').innerHTML = '<tr><td colspan="8" style="color:var(--off);text-align:center;">Error.</td></tr>'; });
}
window.cargarCambios = cargarCambios;

function resolverCambio(id, status){
    fetch('api/shift_changes.php', {
        method:'PATCH',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ id:id, status:status })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ cargarCambios(); toast(status==='approved' ? 'Cambio aprobado.' : 'Cambio rechazado.', status==='approved'?'ok':'err'); }
        else { toast('Error: '+(d.error||''),'err'); }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.resolverCambio = resolverCambio;

// =====================
// ===== PERFIL =====
// =====================
function cargarPerfil(){
    var u = getUser ? getUser() : JSON.parse(localStorage.getItem('user')||'{}');
    if(!u) return;
    document.getElementById('pNombre').value   = u.name  || '';
    document.getElementById('pEmail').value    = u.email || '';
    document.getElementById('pTelefono').value = u.phone || '';
    document.getElementById('pRol').value      = u.role  || '';
}

function guardarPerfil(){
    var u = getUser ? getUser() : JSON.parse(localStorage.getItem('user')||'{}');
    var body = {
        id:    u.id,
        name:  document.getElementById('pNombre').value.trim(),
        email: document.getElementById('pEmail').value.trim(),
        phone: document.getElementById('pTelefono').value.trim()
    };
    if(!body.name || !body.email){ toast('Nombre y email son obligatorios.','err'); return; }
    fetch('api/users.php', {
        method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        var msg = document.getElementById('profileMsg');
        if(d.success){
            toast('Perfil actualizado.');
            msg.textContent = '✅ Cambios guardados.'; msg.style.color='var(--on)';
            // Actualizar localStorage
            u.name = body.name; u.email = body.email; u.phone = body.phone;
            localStorage.setItem('user', JSON.stringify(u));
        } else {
            msg.textContent = '❌ '+(d.error||'Error.'); msg.style.color='var(--off)';
        }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.guardarPerfil = guardarPerfil;

function cambiarPassword(){
    var u   = getUser ? getUser() : JSON.parse(localStorage.getItem('user')||'{}');
    var actual    = document.getElementById('pPassActual').value;
    var nueva     = document.getElementById('pPassNueva').value;
    var confirmar = document.getElementById('pPassConfirmar').value;
    var msg = document.getElementById('passMsg');
    if(!actual || !nueva || !confirmar){ toast('Completa todos los campos.','err'); return; }
    if(nueva.length < 6){ toast('Mínimo 6 caracteres.','err'); return; }
    if(nueva !== confirmar){ toast('Las contraseñas no coinciden.','err'); return; }
    fetch('api/change_password.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ user_id:u.id, current_password:actual, new_password:nueva })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){
            toast('Contraseña actualizada.');
            msg.textContent = '✅ Contraseña actualizada.'; msg.style.color='var(--on)';
            ['pPassActual','pPassNueva','pPassConfirmar'].forEach(function(id){ document.getElementById(id).value=''; });
        } else {
            msg.textContent = '❌ '+(d.error||'Error.'); msg.style.color='var(--off)';
        }
    })
    .catch(function(){ toast('Error de conexión.','err'); });
}
window.cambiarPassword = cambiarPassword;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function(){
    cargarResumen();
});

})();
</script>

<?php include 'footer.php'; ?>
