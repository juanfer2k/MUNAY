<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
require_once 'api/config.php';
require_once 'includes/icons.php';
$page_title = 'Dashboard';
include 'header.php';
?>

<!-- ===== ESTADÍSTICAS ===== -->
<div class="grid-4" id="statsGrid">
    <div class="stat-card blue">
        <span class="icon"><?= munay_icon('users', 28, 'fill="#3f8dee"') ?></span>
        <div class="number" id="totalUsers">0</div>
        <div class="label">Usuarios</div>
    </div>
    <div class="stat-card green">
        <span class="icon"><?= munay_icon('shifts', 28, 'fill="#1E8449"') ?></span>
        <div class="number" id="todayShifts">0</div>
        <div class="label">Turnos Hoy</div>
    </div>
    <div class="stat-card orange">
        <span class="icon"><?= munay_icon('alerts', 28, 'fill="#f39c12"') ?></span>
        <div class="number" id="activeNow">0</div>
        <div class="label">Activos</div>
    </div>
    <div class="stat-card red">
        <span class="icon"><?= munay_icon('alerts', 28, 'fill="#e74c3c"') ?></span>
        <div class="number" id="pendingAlerts">0</div>
        <div class="label">Alertas</div>
    </div>
</div>

<!-- ===== TABS ===== -->
<div class="tabs">
    <button class="tab active" data-tab="users" title="Usuarios">
        <?= munay_icon('users') ?>
        <span class="tab-label">Usuarios</span>
    </button>
    <button class="tab" data-tab="shifts" title="Turnos">
        <?= munay_icon('shifts') ?>
        <span class="tab-label">Turnos</span>
    </button>
    <button class="tab" data-tab="expenses" title="Gastos">
        <?= munay_icon('expenses') ?>
        <span class="tab-label">Gastos</span>
    </button>
    <button class="tab" data-tab="changes" title="Cambios">
        <?= munay_icon('changes') ?>
        <span class="tab-label">Cambios</span>
    </button>
    <button class="tab" data-tab="alerts" title="Alertas">
        <?= munay_icon('alerts') ?>
        <span class="tab-label">Alertas</span>
    </button>
</div>

<!-- ===== CONTENIDO DE TABS ===== -->
<div id="tab-users" class="tab-content active">
    <div class="card"><div class="card-header"><span class="card-title">Gestión de Usuarios</span><button onclick="abrirModalUsuario()" class="btn btn-primary">+ Nuevo</button></div>
    <div class="table-container"><table id="usersTable"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Grupo</th><th>Acciones</th></tr></thead><tbody id="usersList"><tr><td colspan="7" style="text-align:center;">Cargando...</td></tr></tbody></table></div></div>
</div>

<div id="tab-shifts" class="tab-content" style="display:none;">
    <div class="card"><div class="card-header"><span class="card-title">Gestión de Turnos</span><button onclick="abrirModalTurno()" class="btn btn-primary">+ Nuevo</button></div>
    <div class="table-container"><table id="shiftsTable"><thead><tr><th>ID</th><th>Cuidador</th><th>Fecha</th><th>Horario</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="shiftsList"><tr><td colspan="7" style="text-align:center;">Cargando...</td></tr></tbody></table></div></div>
</div>

<div id="tab-expenses" class="tab-content" style="display:none;">
    <div class="card"><div class="card-header"><span class="card-title">Gastos y Viáticos</span></div>
    <div class="table-container"><table id="expensesTable"><thead><tr><th>ID</th><th>Cuidador</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="expensesList"><tr><td colspan="6" style="text-align:center;">Cargando...</td></tr></tbody></table></div></div>
</div>

<div id="tab-changes" class="tab-content" style="display:none;">
    <div class="card"><div class="card-header"><span class="card-title">Solicitudes de Cambio</span></div>
    <div class="table-container"><table id="changesTable"><thead><tr><th>ID</th><th>Turno</th><th>Cuidador</th><th>Fecha deseada</th><th>Hora deseada</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="changesList"><tr><td colspan="8" style="text-align:center;">Cargando...</td></tr></tbody></table></div></div>
</div>

<div id="tab-alerts" class="tab-content" style="display:none;">
    <div class="card"><div class="card-header"><span class="card-title">Historial de Alertas</span></div>
    <div class="table-container"><table id="alertsTable"><thead><tr><th>ID</th><th>Mensaje</th><th>Tipo</th><th>Origen</th><th>Fecha</th><th>Estado</th></tr></thead><tbody id="alertsList"><tr><td colspan="6" style="text-align:center;">Cargando...</td></tr></tbody></table></div></div>
</div>

<!-- ===== MODALES ===== -->
<div class="modal-overlay" id="modalUsuario">
    <div class="modal">
        <div class="modal-header"><h2 id="modalUsuarioTitulo">Nuevo Usuario</h2><button class="modal-close" onclick="cerrarModal('modalUsuario')">&times;</button></div>
        <input type="hidden" id="editUserId">
        <div class="form-group"><label>Nombre *</label><input type="text" id="inputNombre" placeholder="Nombre completo"></div>
        <div class="form-group"><label>Email *</label><input type="email" id="inputEmail" placeholder="correo@ejemplo.com"></div>
        <div class="form-group"><label>Teléfono (WhatsApp) *</label><input type="tel" id="inputTelefono" placeholder="3001234567"></div>
        <div class="form-group"><label>Contraseña</label><input type="password" id="inputPassword" placeholder="Dejar en blanco para no cambiar"></div>
        <div class="form-group"><label>Rol</label><select id="inputRol"><option value="caregiver">Cuidador</option><option value="police">Policía</option><option value="admin">Administrador</option></select></div>
        <div class="form-group"><label>Grupo</label><select id="inputGrupo"><option value="A">A (Diurno)</option><option value="B">B (Tarde/Noche)</option><option value="C">C (Médico)</option></select></div>
        <div class="modal-actions"><button onclick="guardarUsuario()" class="btn btn-success">Guardar</button><button onclick="cerrarModal('modalUsuario')" class="btn btn-outline">Cancelar</button></div>
    </div>
</div>

<div class="modal-overlay" id="modalTurno">
    <div class="modal">
        <div class="modal-header"><h2 id="modalTurnoTitulo">Nuevo Turno</h2><button class="modal-close" onclick="cerrarModal('modalTurno')">&times;</button></div>
        <input type="hidden" id="editTurnoId">
        <div class="form-group"><label>Cuidador</label><select id="selectCuidador"></select></div>
        <div class="form-group"><label>Fecha</label><input type="date" id="inputFecha"></div>
        <div class="form-group"><label>Hora Inicio</label><input type="time" id="inputInicio"></div>
        <div class="form-group"><label>Hora Fin</label><input type="time" id="inputFin"></div>
        <div class="form-group"><label>Ubicación</label><input type="text" id="inputUbicacion" placeholder="Clínica, hospital, etc."></div>
        <div class="form-group"><label>Menor a cargo</label><input type="text" id="inputMenor" placeholder="ID o nombre"></div>
        <div class="form-group"><label>Tipo</label><select id="inputTipo"><option value="base">Base</option><option value="extra">Extra</option><option value="medical">Médico</option><option value="weekend_day">Fin de semana - Día</option><option value="weekend_night">Fin de semana - Noche</option></select></div>
        <div class="form-group"><label>Estado</label><select id="inputEstado"><option value="pending">Pendiente</option><option value="in_progress">En progreso</option><option value="completed">Completado</option><option value="cancelled">Cancelado</option></select></div>
        <div class="modal-actions"><button onclick="guardarTurno()" class="btn btn-success">Guardar</button><button onclick="cerrarModal('modalTurno')" class="btn btn-outline">Cancelar</button></div>
    </div>
</div><div class="card" style="margin-top:20px;">
    <div class="card-header">
        <span class="card-title">Generación de Turnos</span>
    </div>
    <div>
        <button onclick="generarTurnos()" class="btn btn-primary">Generar Turnos para la Semana Actual</button>
        <div id="generarMsg" style="margin-top:10px;"></div>
    </div>
</div>

<script>
function generarTurnos() {
    const msg = document.getElementById('generarMsg');
    msg.innerHTML = 'Generando...';
    fetch('api/generate_shifts.php?week_start=' + getMonday())
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                msg.innerHTML = '✅ Turnos generados: ' + data.created + (data.errors.length ? ' (errores: ' + data.errors.join(', ') + ')' : '');
            } else {
                msg.innerHTML = '❌ Error: ' + data.error;
            }
        })
        .catch(e => {
            msg.innerHTML = '❌ Error de conexión.';
        });
}

function getMonday() {
    const d = new Date();
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff)).toISOString().slice(0,10);
}
</script>

<script src="js/dashboard.js"></script>

<?php include 'footer.php'; ?>
