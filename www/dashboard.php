<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
require_once 'api/config.php';

$user_role = $_SESSION['user_role'] ?? 'custodio';
$page_title = match($user_role) {
    'admin' => 'Panel de Administración',
    'coordinator' => 'Panel de Coordinación',
    'nursing' => 'Panel de Enfermería',
    'custodio' => 'Mis Turnos',
    'police' => 'Panel de Policía',
    default => 'Dashboard'
};
include 'header.php';
?>

<style>
/* ===== ADMIN PANEL (bordes naranja) ===== */
.admin-panel .card {
    border-top: 4px solid #f39c12 !important;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
}
.admin-panel .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1c40f !important;
    margin-bottom: 16px;
}
.admin-panel .card-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}
.admin-panel .tabs .tab.active[data-tab="users"] { background: #3f8dee !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="shifts"] { background: #f39c12 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="expenses"] { background: #e67e22 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="changes"] { background: #c0392b !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="alerts"] { background: #e74c3c !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="myShifts"] { background: #1E8449 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="myExpenses"] { background: #8e44ad !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="myPayroll"] { background: #2980b9 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="myRoutes"] { background: #f39c12 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="map"] { background: #27ae60 !important; color: #fff; }
.admin-panel .tabs .tab.active[data-tab="sections"] { background: #8e44ad !important; color: #fff; }
.admin-panel table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.admin-panel table th {
    background: #f8f9fa;
    color: #2c3e50;
    padding: 12px;
    border-bottom: 2px solid #ddd;
    text-align: left;
    font-weight: 600;
}
.admin-panel table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-panel table tr:hover td {
    background: #f8f9fa;
}
.admin-panel .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.admin-panel .badge-admin { background: #3f8dee; color: #fff; }
.admin-panel .badge-custodio { background: #1E8449; color: #fff; }
.admin-panel .badge-police { background: #f39c12; color: #fff; }
.admin-panel .badge-pending { background: #f39c12; color: #fff; }
.admin-panel .badge-in_progress { background: #2E86C1; color: #fff; }
.admin-panel .badge-completed { background: #1E8449; color: #fff; }
.admin-panel .badge-cancelled { background: #e74c3c; color: #fff; }
.admin-panel .badge-approved { background: #1E8449; color: #fff; }
.admin-panel .badge-rejected { background: #e74c3c; color: #fff; }
.admin-panel .badge-confirmed { background: #2E86C1; color: #fff; }
.admin-panel .whatsapp-link {
    color: #25D366;
    text-decoration: none;
    font-size: 18px;
}
.admin-panel .btn-sm {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    margin: 0 2px;
}
.admin-panel .btn-primary-sm {
    background: #2E86C1;
    color: #fff;
}
.admin-panel .btn-primary-sm:hover {
    background: #1a5276;
}
.admin-panel .btn-danger-sm {
    background: #e74c3c;
    color: #fff;
}
.admin-panel .btn-danger-sm:hover {
    background: #922b21;
}
.admin-panel .btn-success-sm {
    background: #1E8449;
    color: #fff;
}
.admin-panel .btn-success-sm:hover {
    background: #145a32;
}
.admin-panel .dataTables_wrapper {
    padding: 10px 0;
}
.admin-panel .dataTables_filter input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 12px;
    margin-left: 6px;
}
.admin-panel .dataTables_paginate .paginate_button {
    padding: 4px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0 2px;
    cursor: pointer;
}
.admin-panel .dataTables_paginate .paginate_button.current {
    background: #2E86C1;
    color: #fff;
    border-color: #2E86C1;
}
.admin-panel .config-section {
    margin-top: 30px;
    border-top: 2px solid #f1c40f;
    padding-top: 20px;
}
.admin-panel .config-section .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 768px) {
    .admin-panel .config-section .grid-2 {
        grid-template-columns: 1fr;
    }
}
.admin-panel .config-section select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}
.admin-panel .config-section .hint {
    display: block;
    font-size: 12px;
    color: #5d6d7e;
    margin-top: 4px;
}
</style>

<div class="admin-panel">
    <!-- ===== DATATABLES ===== -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- ===== ESTADÍSTICAS ===== -->
    <div class="grid-4" id="statsGrid">
        <div onclick="irATab('users')" class="stat-card blue" style="cursor:pointer;">
            <span class="icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="#3f8dee"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></span>
            <div class="number" id="totalUsers">0</div>
            <div class="label">Formadores</div>
        </div>
        <div onclick="irATab('shifts')" class="stat-card green" style="cursor:pointer;">
            <span class="icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="#1E8449"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg></span>
            <div class="number" id="todayShifts">0</div>
            <div class="label">Turnos Hoy</div>
        </div>
        <div onclick="irATab('users')" class="stat-card orange" style="cursor:pointer;">
            <span class="icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="#f39c12"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg></span>
            <div class="number" id="activeNow">0</div>
            <div class="label">Activos</div>
        </div>
        <div onclick="irATab('alerts')" class="stat-card red" style="cursor:pointer;">
            <span class="icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="#e74c3c"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg></span>
            <div class="number" id="pendingAlerts">0</div>
            <div class="label">Alertas</div>
        </div>
    </div>

    <!-- ===== TABS (generados según rol) ===== -->
    <div class="tabs" id="mainTabs"></div>

    <!-- ===== CONTENEDOR DE CONTENIDO ===== -->
    <div id="tabContentContainer">
        <p>Cargando panel...</p>
    </div>

    <!-- ===== SECCIÓN CONFIG (solo admin) ===== -->
    <?php if ($user_role === 'admin'): ?>
    <div class="config-section">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Configuración de Funciones</span>
            </div>
            <div class="grid-2">
                <div>
                    <label>Generación de QR para pacientes</label>
                    <select id="func_qr" class="form-control">
                        <option value="1">Habilitado</option>
                        <option value="0" selected>Deshabilitado</option>
                    </select>
                    <small class="hint">Permite generar códigos QR con identificación del paciente</small>
                </div>
                <div>
                    <label>Agenda completa en un archivo</label>
                    <select id="func_agenda" class="form-control">
                        <option value="1">Habilitado</option>
                        <option value="0" selected>Deshabilitado</option>
                    </select>
                    <small class="hint">Exporta toda la agenda diaria en un único archivo PDF/CSV</small>
                </div>
                <div>
                    <label>Notificaciones push</label>
                    <select id="func_push" class="form-control">
                        <option value="1">Habilitado</option>
                        <option value="0" selected>Deshabilitado</option>
                    </select>
                    <small class="hint">Envía notificaciones push a los dispositivos</small>
                </div>
                <div>
                    <label>Modo mantenimiento</label>
                    <select id="func_maintenance" class="form-control">
                        <option value="1">Activo</option>
                        <option value="0" selected>Inactivo</option>
                    </select>
                    <small class="hint">Muestra mensaje de mantenimiento a todos los usuarios</small>
                </div>
            </div>
            <button onclick="guardarConfiguracion()" class="btn btn-primary">Guardar Configuración</button>
            <div id="configMsg" style="margin-top:10px;"></div>
        </div>
    </div>

    <!-- ===== GENERADOR DE TURNOS ===== -->
    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <span class="card-title">Generación de Turnos</span>
        </div>
        <div>
            <button onclick="generarTurnos()" class="btn btn-primary">Generar Turnos para la Semana Actual</button>
            <div id="generarMsg" style="margin-top:10px;"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== MODALES COMUNES ===== -->
    <div class="modal-overlay" id="modalUsuario">
        <div class="modal">
            <div class="modal-header"><h2 id="modalUsuarioTitulo">Nuevo Usuario</h2><button class="modal-close" onclick="cerrarModal('modalUsuario')">&times;</button></div>
            <input type="hidden" id="editUserId">
            <div class="form-group"><label>Nombre *</label><input type="text" id="inputNombre" placeholder="Nombre completo"></div>
            <div class="form-group"><label>Email *</label><input type="email" id="inputEmail" placeholder="correo@ejemplo.com"></div>
            <div class="form-group"><label>Teléfono (WhatsApp) *</label><input type="tel" id="inputTelefono" placeholder="3001234567"></div>
            <div class="form-group"><label>Contraseña</label><input type="password" id="inputPassword" placeholder="Dejar en blanco para no cambiar"></div>
            <div class="form-group"><label>Rol</label>
                <select id="inputRol" class="form-control">
                    <option value="admin">Administrador</option>
                    <option value="coordinator">Coordinador</option>
                    <option value="custodio">Formador</option>
                    <option value="nursing">Enfermería</option>
                    <option value="police">Policía</option>
                </select>
            </div>
            <div class="form-group"><label>Patrón de turno</label>
                <select id="inputPattern" class="form-control">
                    <option value="A">A (secuencia semanal)</option>
                    <option value="B">B (secuencia inversa)</option>
                    <option value="C">C (nocturno)</option>
                    <option value="CONV">Convivencia</option>
                    <option value="MED">Acompañamiento Médico</option>
                </select>
            </div>
            <div class="form-group"><label>Sección</label>
                <select id="inputSection" class="form-control">
                    <option value="">Sin sección</option>
                </select>
            </div>
            <div class="modal-actions"><button onclick="guardarUsuario()" class="btn btn-success">Guardar</button><button onclick="cerrarModal('modalUsuario')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <div class="modal-overlay" id="modalTurno">
        <div class="modal">
            <div class="modal-header"><h2 id="modalTurnoTitulo">Nuevo Turno</h2><button class="modal-close" onclick="cerrarModal('modalTurno')">&times;</button></div>
            <input type="hidden" id="editTurnoId">
            <div class="form-group"><label>Cuidador</label><select id="selectCuidador" class="form-control"></select></div>
            <div class="form-group"><label>Fecha</label><input type="date" id="inputFecha" class="form-control"></div>
            <div class="form-group"><label>Hora Inicio</label><input type="time" id="inputInicio" class="form-control"></div>
            <div class="form-group"><label>Hora Fin</label><input type="time" id="inputFin" class="form-control"></div>
            <div class="form-group"><label>Ubicación</label><input type="text" id="inputUbicacion" class="form-control" placeholder="Clínica, hospital, etc."></div>
            <div class="form-group"><label>Menor a cargo</label><input type="text" id="inputMenor" class="form-control" placeholder="ID o nombre"></div>
            <div class="form-group"><label>Patrón</label>
                <select id="inputTurnoPattern" class="form-control">
                    <option value="">Sin patrón</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="CONV">Convivencia</option>
                    <option value="MED">Médico</option>
                </select>
            </div>
            <div class="form-group"><label>Sección</label>
                <select id="inputTurnoSection" class="form-control">
                    <option value="">Sin sección</option>
                </select>
            </div>
            <div class="form-group"><label>Tipo</label>
                <select id="inputTipo" class="form-control">
                    <option value="regular">Regular</option>
                    <option value="extra">Extra</option>
                    <option value="cambio">Cambio</option>
                </select>
            </div>
            <div class="form-group"><label>Estado</label>
                <select id="inputEstado" class="form-control">
                    <option value="pending">Pendiente</option>
                    <option value="in_progress">En progreso</option>
                    <option value="completed">Completado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
            <div class="modal-actions"><button onclick="guardarTurno()" class="btn btn-success">Guardar</button><button onclick="cerrarModal('modalTurno')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <!-- ===== MODAL: SOLICITAR MI TURNO (custodio / coordinador convivencia) ===== -->
    <div class="modal-overlay" id="modalMiTurno">
        <div class="modal">
            <div class="modal-header"><h2>Solicitar Turno</h2><button class="modal-close" onclick="cerrarModal('modalMiTurno')">&times;</button></div>
            <div class="form-group"><label>Fecha</label><input type="date" id="miTurnoFecha" class="form-control"></div>
            <div class="form-group"><label>Hora Inicio</label><input type="time" id="miTurnoInicio" class="form-control"></div>
            <div class="form-group"><label>Hora Fin</label><input type="time" id="miTurnoFin" class="form-control"></div>
            <div class="form-group"><label>Ubicación</label><input type="text" id="miTurnoUbicacion" class="form-control" placeholder="Clínica, hospital, etc."></div>
            <div class="form-group"><label>Menor a cargo</label><input type="text" id="miTurnoMenor" class="form-control" placeholder="ID o nombre (opcional)"></div>
            <div class="form-group"><label>Motivo / Nota</label><input type="text" id="miTurnoMotivo" class="form-control" placeholder="Opcional"></div>
            <div id="miTurnoMsg" style="margin:8px 0;"></div>
            <div class="modal-actions"><button onclick="guardarMiTurno()" class="btn btn-success">Enviar solicitud</button><button onclick="cerrarModal('modalMiTurno')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <!-- ===== MODAL: REPORTAR MI GASTO (custodio / coordinador convivencia) ===== -->
    <div class="modal-overlay" id="modalMiGasto">
        <div class="modal">
            <div class="modal-header"><h2>Reportar Gasto</h2><button class="modal-close" onclick="cerrarModal('modalMiGasto')">&times;</button></div>
            <div class="form-group"><label>Tipo</label>
                <select id="miGastoTipo" class="form-control">
                    <option value="parking">Parqueadero</option>
                    <option value="transport">Transporte</option>
                    <option value="food">Alimentación</option>
                    <option value="toll">Peaje</option>
                    <option value="other">Otro</option>
                </select>
            </div>
            <div class="form-group"><label>Monto</label><input type="number" id="miGastoMonto" class="form-control" placeholder="0" min="0" step="0.01"></div>
            <div class="form-group"><label>Descripción</label><input type="text" id="miGastoDescripcion" class="form-control" placeholder="Opcional"></div>
            <div id="miGastoMsg" style="margin:8px 0;"></div>
            <div class="modal-actions"><button onclick="guardarMiGasto()" class="btn btn-success">Enviar reporte</button><button onclick="cerrarModal('modalMiGasto')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <!-- ===== MODAL: NUEVO ACOMPAÑAMIENTO (enfermería / admin) ===== -->
    <div class="modal-overlay" id="modalVisita">
        <div class="modal">
            <div class="modal-header"><h2>Nuevo Acompañamiento</h2><button class="modal-close" onclick="cerrarModal('modalVisita')">&times;</button></div>
            <div class="form-group"><label>Tipo</label>
                <select id="visitaTipo" class="form-control">
                    <option value="cita">Cita</option>
                    <option value="ruta_emergencia">Ruta (activación por emergencia)</option>
                    <option value="juzgado">Juzgado</option>
                    <option value="cambio_centro">Cambio de centro</option>
                </select>
            </div>
            <div class="form-group"><label>Paciente</label><select id="visitaPaciente" class="form-control"></select></div>
            <div class="form-group"><label>Fecha</label><input type="date" id="visitaFecha" class="form-control"></div>
            <div class="form-group"><label>Hora</label><input type="time" id="visitaHora" class="form-control"></div>
            <div class="form-group"><label>Lugar</label><input type="text" id="visitaLugar" class="form-control" placeholder="Clínica, hospital, etc."></div>
            <div class="form-group"><label>Notas</label><textarea id="visitaMotivo" class="form-control" rows="3" placeholder="Motivo de la cita, indicaciones, observaciones... (opcional)"></textarea></div>
            <div class="form-group"><label>Formador acompañante</label><select id="visitaCustodio" class="form-control"></select></div>
            <div class="form-group"><label>Adjunto</label><input type="file" id="visitaAdjunto" class="form-control"><small class="hint">Opcional: orden médica, remisión, etc.</small></div>
            <div id="visitaMsg" style="margin:8px 0;"></div>
            <div class="modal-actions"><button onclick="guardarVisita()" class="btn btn-success">Crear visita</button><button onclick="cerrarModal('modalVisita')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <!-- ===== MODAL: FICHA QR DE VISITA (para PONAL, sin login) ===== -->
    <div class="modal-overlay" id="modalQR">
        <div class="modal" style="text-align:center;max-width:320px;">
            <div class="modal-header"><h2>Ficha para escolta</h2><button class="modal-close" onclick="cerrarModal('modalQR')">&times;</button></div>
            <p style="font-size:13px;color:#5d6d7e;">El PONAL escanea este código para ver la ficha (paciente, destino y custodio). Vence unas horas después de la cita.</p>
            <img id="qrImage" src="" alt="Código QR" style="width:220px;height:220px;margin:10px auto;display:block;">
            <a id="qrLink" href="#" target="_blank" style="word-break:break-all;font-size:12px;">Abrir ficha</a>
            <div class="modal-actions"><button onclick="cerrarModal('modalQR')" class="btn btn-outline">Cerrar</button></div>
        </div>
    </div>

    <!-- ===== MODAL: IMPORTAR PACIENTES CSV (admin) ===== -->
    <div class="modal-overlay" id="modalImportarCSV">
        <div class="modal">
            <div class="modal-header"><h2>Importar Pacientes (CSV)</h2><button class="modal-close" onclick="cerrarModal('modalImportarCSV')">&times;</button></div>
            <p style="font-size:13px;color:#5d6d7e;">El archivo debe tener encabezados en la primera fila, separados por coma:<br>
            <code>tipo_doc,documento,nombre,apellido,fecha_nacimiento,genero,formador_responsable,telefono_contacto,observaciones</code><br>
            Solo <code>documento</code> y <code>nombre</code> son obligatorios. Fechas en formato AAAA-MM-DD.</p>
            <div class="form-group"><input type="file" id="csvPacientes" accept=".csv" class="form-control"></div>
            <div id="csvMsg" style="margin:8px 0;"></div>
            <div class="modal-actions"><button onclick="importarCSV()" class="btn btn-success">Importar</button><button onclick="cerrarModal('modalImportarCSV')" class="btn btn-outline">Cancelar</button></div>
        </div>
    </div>

    <!-- ===== SCRIPTS ===== -->
    <script>
    // ============================================================
    //  CONFIGURACIÓN POR ROL
    // ============================================================
    const currentRole = '<?php echo $user_role; ?>';
    document.body.classList.add('role-' + currentRole);
    const BASE_URL = window.location.origin + window.location.pathname.split('/dashboard.php')[0] + '/';

    const tabsConfig = {
        admin: [
            { id: 'users', label: 'Usuarios', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>' },
            { id: 'shifts', label: 'Turnos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
            { id: 'expenses', label: 'Gastos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>' },
            { id: 'changes', label: 'Cambios', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6v2h-4V6h4zm0 4v2H8v-2h4zm0 4v2H8v-2h4zm-4 4h8v-2H8v2zm10-12h-3V4h-2v2H8V4H6v2H5c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1z"/></svg>' },
            { id: 'alerts', label: 'Alertas', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>' },
            { id: 'sections', label: 'Secciones', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>' },
            { id: 'pacientes', label: 'Pacientes', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>' },
            { id: 'visitas', label: 'Acompañamientos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' }
        ],
        coordinator: [
            { id: 'dashboard', label: 'Dashboard', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM13 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z"/></svg>' },
            { id: 'shifts', label: 'Turnos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
            { id: 'sections', label: 'Secciones', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>' },
            { id: 'myShifts', label: 'Mis Turnos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
            { id: 'myExpenses', label: 'Mis Gastos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>' }
        ],
        nursing: [
            { id: 'visitas', label: 'Acompañamientos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
            { id: 'pacientes', label: 'Pacientes', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>' },
            { id: 'shifts', label: 'Turnos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
            { id: 'users', label: 'Formadores', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>' }
        ],
custodio: [
    { id: 'myShifts', label: 'Mis Turnos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' },
    { id: 'myExpenses', label: 'Mis Gastos', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>' },
    { id: 'myPayroll', label: 'Mi Nómina', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>' }
],
        police: [
            { id: 'myRoutes', label: 'Mis Rutas', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>' },
            { id: 'map', label: 'Mapa', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.1V5l6 2.1V19z"/></svg>' }
        ]
    };

    // ============================================================
    //  FUNCIONES GLOBALES
    // ============================================================
    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    function irATab(tabId) {
    console.log("irATab llamada con: " + tabId);
        const btn = document.querySelector('.tab[data-tab="' + tabId + '"]');
        if (btn) {
            btn.click();
            btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function abrirModal(id) {
        document.getElementById(id).classList.add('active');
    }

    // ============================================================
    //  ESTADISTICAS
    // ============================================================
    function cargarStats() {
        fetch(BASE_URL + 'api/dashboard_stats.php')
            .then(r => r.json())
            .then(d => {
                document.getElementById('totalUsers').textContent = d.totalCaregivers || 0;
                document.getElementById('todayShifts').textContent = d.todayShifts || 0;
                document.getElementById('activeNow').textContent = d.activeNow || 0;
                document.getElementById('pendingAlerts').textContent = d.pendingAlerts || 0;
            })
            .catch(e => console.error('Stats error:', e));
    }

    // ============================================================
    //  USUARIOS
    // ============================================================
    function cargarUsuarios() {
        const tableId = '#usersTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/users.php')
            .then(r => r.json())
            .then(d => {
                let html = '';
                if (!d || d.error || d.length === 0) {
                    html = '';
                } else {
                    d.forEach(u => {
                        let phoneLink = 'N/A';
                        if (u.phone) {
                            let cleanPhone = u.phone.replace(/[\s\-\(\)\+]/g, '');
                            if (cleanPhone.length >= 10) {
                                if (!cleanPhone.startsWith('57')) {
                                    cleanPhone = '57' + cleanPhone;
                                }
                                phoneLink = '<a href="https://wa.me/' + cleanPhone + '" target="_blank" class="whatsapp-link" title="WhatsApp">📱</a> ' + u.phone;
                            }
                        }
                        let roleDisplay = u.role;
                        if (u.role === 'custodio') roleDisplay = 'Formador';
                        else if (u.role === 'coordinator') roleDisplay = 'coordinador';
                        else if (u.role === 'nursing') roleDisplay = 'enfermería';
                        else if (u.role === 'admin') roleDisplay = 'administrador';
                        else if (u.role === 'police') roleDisplay = 'policía';

                        html += `<tr>
                            <td>${u.id}</td>
                            <td><strong>${u.name}</strong></td>
                            <td>${u.email}</td>
                            <td>${phoneLink}</td>
                            <td><span class="badge badge-${u.role}">${roleDisplay}</span></td>
                            <td>${u.pattern_code || 'N/A'}</td>
                            <td>${u.section_name || 'N/A'}</td>
                            <td>${u.group_type || 'N/A'}</td>
                            <td>
                                ${currentRole === 'admin' ? `
                                <button class="btn btn-primary-sm btn-editar-usuario" data-id="${u.id}">E</button>
                                <button class="btn btn-danger-sm btn-eliminar-usuario" data-id="${u.id}">X</button>` : ''}
                            </td>
                        </tr>`;
                    });
                }
                document.getElementById('usersList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 8 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                document.getElementById('usersList').innerHTML = '<tr><td colspan="9" style="text-align:center;color:red;">Error al cargar usuarios</td></tr>';
                console.error('Error usuarios:', e);
            });
    }

    function abrirModalUsuario(u) {
        document.getElementById('modalUsuarioTitulo').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('editUserId').value = u?.id || '';
        document.getElementById('inputNombre').value = u?.name || '';
        document.getElementById('inputEmail').value = u?.email || '';
        document.getElementById('inputTelefono').value = u?.phone || '';
        document.getElementById('inputPassword').value = '';
        document.getElementById('inputRol').value = u?.role || 'custodio';
        document.getElementById('inputPattern').value = u?.pattern_code || 'A';
        // Cargar secciones
        fetch(BASE_URL + 'api/sections.php')
            .then(r => r.json())
            .then(d => {
                let sel = document.getElementById('inputSection');
                sel.innerHTML = '<option value="">Sin sección</option>';
                d.forEach(s => {
                    sel.innerHTML += `<option value="${s.id}" ${s.id == u?.section_id ? 'selected' : ''}>${s.name}</option>`;
                });
            })
            .catch(e => console.error('Error cargando secciones:', e));
        abrirModal('modalUsuario');
    }

    function editarUsuario(id) {
        fetch(BASE_URL + 'api/user.php?id=' + id)
            .then(r => r.json())
            .then(d => {
                if (d.error) {
                    alert('Error: ' + d.error);
                } else {
                    abrirModalUsuario(d);
                }
            })
            .catch(e => {
                alert('Error de conexion al cargar usuario.');
                console.error('Error:', e);
            });
    }

    function guardarUsuario() {
        const id = document.getElementById('editUserId').value;
        const phone = document.getElementById('inputTelefono').value.trim();
        if (!phone) {
            alert('El teléfono es OBLIGATORIO');
            return;
        }
        const data = {
            id: id || null,
            name: document.getElementById('inputNombre').value,
            email: document.getElementById('inputEmail').value,
            phone: phone,
            password: document.getElementById('inputPassword').value || null,
            role: document.getElementById('inputRol').value,
            pattern_code: document.getElementById('inputPattern').value,
            section_id: document.getElementById('inputSection').value || null,
            group_type: document.getElementById('inputGrupo')?.value || 'A'
        };
        fetch(BASE_URL + 'api/user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Usuario guardado correctamente.');
                cerrarModal('modalUsuario');
                cargarUsuarios();
                cargarStats();
            } else {
                alert('Error al guardar: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => {
            alert('Error de conexion.');
            console.error('Error guardando usuario:', e);
        });
    }

    function eliminarUsuario(id) {
        if (!confirm('¿Eliminar este usuario?')) return;
        fetch(BASE_URL + 'api/user.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cargarUsuarios();
                cargarStats();
            } else {
                alert('Error al eliminar: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    // ============================================================
    //  TURNOS
    // ============================================================
    function cargarTurnos() {
        const tableId = '#shiftsTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/shifts.php')
            .then(r => r.json())
            .then(d => {
                let html = '';
                if (!d || d.error || d.length === 0) {
                    html = '';
                } else {
                    d.forEach(s => {
                        html += `<tr>
                            <td>${s.id}</td>
                            <td>${s.caregiver_name || 'N/A'}</td>
                            <td>${s.shift_date}</td>
                            <td>${s.start_time}-${s.end_time}</td>
                            <td>${s.pattern_code || 'N/A'}</td>
                            <td>${s.section_name || 'N/A'}</td>
                            <td>${s.assigned_by_name || 'N/A'}</td>
                            <td>${s.location || 'N/A'}</td>
                            <td><span class="badge badge-${s.status}">${s.status}</span></td>
                            <td>
                                ${currentRole === 'admin' ? `
                                <button class="btn btn-primary-sm btn-editar-turno" data-id="${s.id}">E</button>
                                <button class="btn btn-danger-sm btn-eliminar-turno" data-id="${s.id}">X</button>` : ''}
                            </td>
                        </tr>`;
                    });
                }
                document.getElementById('shiftsList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 9 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                document.getElementById('shiftsList').innerHTML = '<tr><td colspan="11" style="text-align:center;color:red;">Error al cargar turnos</td></tr>';
                console.error('Error turnos:', e);
            });
    }

    function autocompletarHorarioTurno() {
        const cuidadorId = document.getElementById('selectCuidador').value;
        const fecha = document.getElementById('inputFecha').value;
        if (!cuidadorId || !fecha) return;

        fetch(BASE_URL + `api/calcular_horario.php?caregiver_id=${cuidadorId}&fecha=${fecha}`)
            .then(r => r.json())
            .then(d => {
                if (d.found) {
                    document.getElementById('inputInicio').value = d.start_time;
                    document.getElementById('inputFin').value = d.end_time;
                    document.getElementById('inputTurnoPattern').value = d.pattern_code || '';
                }
                // Si no se encuentra (sin patrón asignado, etc.) se deja que el admin
                // complete la hora manualmente; no se muestra error, es un caso normal.
            })
            .catch(e => console.error('Error calculando horario automático:', e));
    }
    document.getElementById('selectCuidador').addEventListener('change', autocompletarHorarioTurno);
    document.getElementById('inputFecha').addEventListener('change', autocompletarHorarioTurno);

    function abrirModalTurno(t) {
        document.getElementById('modalTurnoTitulo').textContent = t ? 'Editar Turno' : 'Nuevo Turno';
        document.getElementById('editTurnoId').value = t?.id || '';
        document.getElementById('inputFecha').value = t?.shift_date || '';
        document.getElementById('inputInicio').value = t?.start_time || '';
        document.getElementById('inputFin').value = t?.end_time || '';
        document.getElementById('inputUbicacion').value = t?.location || '';
        document.getElementById('inputMenor').value = t?.minor_id || '';
        document.getElementById('inputTipo').value = t?.shift_type || 'regular';
        document.getElementById('inputEstado').value = t?.status || 'pending';
        document.getElementById('inputTurnoPattern').value = t?.pattern_code || '';
        document.getElementById('inputTurnoSection').value = t?.section_id || '';

        // Cargar cuidadores
        fetch(BASE_URL + 'api/users.php?role=custodio')
            .then(r => r.json())
            .then(d => {
                let sel = document.getElementById('selectCuidador');
                sel.innerHTML = '<option value="">Seleccionar</option>';
                d.forEach(u => {
                    sel.innerHTML += `<option value="${u.id}" ${u.id == t?.caregiver_id ? 'selected' : ''}>${u.name}</option>`;
                });
            })
            .catch(e => console.error('Error cargando cuidadores:', e));

        // Cargar secciones
        fetch(BASE_URL + 'api/sections.php')
            .then(r => r.json())
            .then(d => {
                let sel = document.getElementById('inputTurnoSection');
                sel.innerHTML = '<option value="">Sin sección</option>';
                d.forEach(s => {
                    sel.innerHTML += `<option value="${s.id}" ${s.id == t?.section_id ? 'selected' : ''}>${s.name}</option>`;
                });
            })
            .catch(e => console.error('Error cargando secciones:', e));

        abrirModal('modalTurno');
    }

    function editarTurno(id) {
        fetch(BASE_URL + 'api/shift.php?id=' + id)
            .then(r => r.json())
            .then(d => {
                if (d.error) {
                    alert('Error: ' + d.error);
                } else {
                    abrirModalTurno(d);
                }
            })
            .catch(e => {
                alert('Error de conexion al cargar turno.');
                console.error('Error:', e);
            });
    }

    function guardarTurno() {
        const data = {
            id: document.getElementById('editTurnoId').value || null,
            caregiver_id: document.getElementById('selectCuidador').value,
            shift_date: document.getElementById('inputFecha').value,
            start_time: document.getElementById('inputInicio').value,
            end_time: document.getElementById('inputFin').value,
            location: document.getElementById('inputUbicacion').value,
            minor_id: document.getElementById('inputMenor').value,
            shift_type: document.getElementById('inputTipo').value,
            pattern_code: document.getElementById('inputTurnoPattern').value || null,
            section_id: document.getElementById('inputTurnoSection').value || null,
            status: document.getElementById('inputEstado').value
        };
        if (!data.caregiver_id || !data.shift_date || !data.start_time) {
            alert('Completa todos los campos obligatorios.');
            return;
        }
        fetch(BASE_URL + 'api/shift.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Turno guardado correctamente.');
                cerrarModal('modalTurno');
                cargarTurnos();
                cargarStats();
            } else {
                alert('Error al guardar turno: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => {
            alert('Error de conexion.');
            console.error('Error guardando turno:', e);
        });
    }

    function eliminarTurno(id) {
        if (!confirm('¿Eliminar este turno?')) return;
        fetch(BASE_URL + 'api/shift.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cargarTurnos();
                cargarStats();
            } else {
                alert('Error al eliminar turno: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    // ============================================================
    //  GASTOS
    // ============================================================
    function cargarGastos() {
        const tableId = '#expensesTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/expenses.php')
            .then(r => r.json())
            .then(d => {
                let html = '';
                if (!d || d.length === 0) {
                    html = '<tr><td colspan="6" style="text-align:center;">No hay gastos</td></tr>';
                } else {
                    d.forEach(e => {
                        html += `<tr>
                            <td>${e.id}</td>
                            <td>${e.caregiver_name || 'N/A'}</td>
                            <td>${e.type}</td>
                            <td>$${e.amount}</td>
                            <td><span class="badge badge-${e.status}">${e.status}</span></td>
                            <td>
                                <button class="btn btn-success-sm btn-aprobar-gasto" data-id="${e.id}">Aprobar</button>
                                <button class="btn btn-danger-sm btn-rechazar-gasto" data-id="${e.id}">Rechazar</button>
                            </td>
                        </tr>`;
                    });
                }
                document.getElementById('expensesList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 5 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => console.error('Error gastos:', e));
    }

    function actualizarGasto(id, estado) {
        fetch(BASE_URL + 'api/expense.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: estado })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cargarGastos();
                cargarStats();
            } else {
                alert('Error al actualizar gasto: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    // ============================================================
    //  CAMBIOS
    // ============================================================
    function cargarCambios() {
        const tableId = '#changesTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/requests.php')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(d => {
                let html = '';
                if (!d || d.length === 0) {
                    html = '';
                } else {
                    d.forEach(r => {
                        html += `<tr>
                            <td>${r.id}</td>
                            <td>${r.shift_id}</td>
                            <td>${r.caregiver_name || 'N/A'}</td>
                            <td>${r.requested_date}</td>
                            <td>${r.requested_time}</td>
                            <td>${r.reason || 'Sin motivo'}</td>
                            <td><span class="badge badge-${r.status}">${r.status}</span></td>
                            <td>
                                <button class="btn btn-success-sm btn-aprobar-cambio" data-id="${r.id}">Aprobar</button>
                                <button class="btn btn-danger-sm btn-rechazar-cambio" data-id="${r.id}">Rechazar</button>
                            </td>
                        </tr>`;
                    });
                }
                document.getElementById('changesList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 7 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                console.error('Error cambios:', e);
                document.getElementById('changesList').innerHTML = '<tr><td colspan="8" style="text-align:center;color:red;">Error al cargar solicitudes de cambio</td></tr>';
            });
    }

    function aprobarCambio(id) {
        if (!confirm('¿Aprobar esta solicitud?')) return;
        fetch(BASE_URL + 'api/request.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: 'approved' })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Solicitud aprobada.');
                cargarCambios();
                cargarStats();
            } else {
                alert('Error: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    function rechazarCambio(id) {
        if (!confirm('¿Rechazar esta solicitud?')) return;
        fetch(BASE_URL + 'api/request.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: 'rejected' })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Solicitud rechazada.');
                cargarCambios();
                cargarStats();
            } else {
                alert('Error: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    // ============================================================
    //  ALERTAS
    // ============================================================
    function cargarAlertas() {
        const tableId = '#alertsTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/alerts.php')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                let html = '';
                if (!data || data.length === 0) {
                    html = '';
                } else {
                    data.forEach(a => {
                        html += `<tr>
                            <td>${a.id}</td>
                            <td>${a.message}</td>
                            <td><span class="badge badge-${a.type}">${a.type}</span></td>
                            <td>${a.origin || 'sistema'}</td>
                            <td>${a.created_at}</td>
                            <td>${a.is_read ? 'Leida' : 'Pendiente'}</td>
                        </tr>`;
                    });
                }
                document.getElementById('alertsList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 5 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                console.error('Error alertas:', e);
                document.getElementById('alertsList').innerHTML = '';
            });
    }

    // ============================================================
    //  SECCIONES
    // ============================================================
    function cargarSections() {
        const tableId = '#sectionsTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/sections.php')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                let html = '';
                if (!data || data.error || data.length === 0) {
                    html = '';
                } else {
                    data.forEach(s => {
                        html += `<tr>
                            <td>${s.id}</td>
                            <td><strong>${s.name}</strong></td>
                            <td>${s.description || 'N/A'}</td>
                            <td>${s.active ? 'Activa' : 'Inactiva'}</td>
                            <td>
                                <button class="btn btn-primary-sm btn-editar-seccion" data-id="${s.id}">E</button>
                                <button class="btn btn-danger-sm btn-eliminar-seccion" data-id="${s.id}">X</button>
                            </td>
                        </tr>`;
                    });
                }
                document.getElementById('sectionsList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: 4 }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                document.getElementById('sectionsList').innerHTML = '<tr><td colspan="5" style="text-align:center;color:red;">Error al cargar secciones</td></tr>';
                console.error('Error secciones:', e);
            });
    }

    // ============================================================
    //  FUNCIONES PARA CUIDADOR
    // ============================================================
    function cargarMisTurnos() {
        const container = document.getElementById('myShiftsList');
        container.innerHTML = '<p>Cargando tus turnos...</p>';
        fetch(BASE_URL + 'api/my_shifts.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = '<p style="color:red;">' + data.error + '</p>';
                    return;
                }
                if (!data.shifts || data.shifts.length === 0) {
                    container.innerHTML = '<p>No tienes turnos asignados.</p>';
                    return;
                }
                let html = '<div class="table-container"><table class="table"><thead><tr><th>Fecha</th><th>Horario</th><th>Ubicación</th><th>Estado</th></tr></thead><tbody>';
                data.shifts.forEach(s => {
                    html += `<tr><td>${s.shift_date}</td><td>${s.start_time} - ${s.end_time}</td><td>${s.location || 'Pendiente'}</td><td><span class="badge badge-${s.status}">${s.status}</span></td></tr>`;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            })
            .catch(e => {
                container.innerHTML = '<p style="color:red;">Error al cargar turnos.</p>';
                console.error('Error mis turnos:', e);
            });
    }

    function cargarMisGastos() {
        const container = document.getElementById('myExpensesList');
        container.innerHTML = '<p>Cargando tus gastos...</p>';
        fetch(BASE_URL + 'api/mis_gastos.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = '<p style="color:red;">' + data.error + '</p>';
                    return;
                }
                if (!data || data.length === 0) {
                    container.innerHTML = '<p>No has reportado gastos.</p>';
                    return;
                }
                let html = '<div class="table-container"><table class="table"><thead><tr><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>';
                data.forEach(g => {
                    html += `<tr><td>${g.type}</td><td>$${g.amount}</td><td><span class="badge badge-${g.status}">${g.status}</span></td><td>${g.created_at}</td></tr>`;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            })
            .catch(e => {
                container.innerHTML = '<p style="color:red;">Error al cargar gastos.</p>';
                console.error('Error mis gastos:', e);
            });
    }

    function guardarMiTurno() {
        const msg = document.getElementById('miTurnoMsg');
        const payload = {
            shift_date: document.getElementById('miTurnoFecha').value,
            start_time: document.getElementById('miTurnoInicio').value,
            end_time: document.getElementById('miTurnoFin').value,
            location: document.getElementById('miTurnoUbicacion').value,
            minor_id: document.getElementById('miTurnoMenor').value,
            reason: document.getElementById('miTurnoMotivo').value
        };
        if (!payload.shift_date || !payload.start_time) {
            msg.innerHTML = '<span style="color:red;">Fecha y hora de inicio son obligatorias.</span>';
            return;
        }
        fetch(BASE_URL + 'api/mi_turno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModal('modalMiTurno');
                document.getElementById('miTurnoFecha').value = '';
                document.getElementById('miTurnoInicio').value = '';
                document.getElementById('miTurnoFin').value = '';
                document.getElementById('miTurnoUbicacion').value = '';
                document.getElementById('miTurnoMenor').value = '';
                document.getElementById('miTurnoMotivo').value = '';
                msg.innerHTML = '';
                cargarMisTurnos();
            } else {
                msg.innerHTML = '<span style="color:red;">' + (d.error || 'Error al enviar la solicitud.') + '</span>';
            }
        })
        .catch(e => {
            msg.innerHTML = '<span style="color:red;">Error de conexión.</span>';
            console.error('Error solicitar turno:', e);
        });
    }

    function guardarMiGasto() {
        const msg = document.getElementById('miGastoMsg');
        const payload = {
            type: document.getElementById('miGastoTipo').value,
            amount: document.getElementById('miGastoMonto').value,
            description: document.getElementById('miGastoDescripcion').value
        };
        if (!payload.amount || parseFloat(payload.amount) <= 0) {
            msg.innerHTML = '<span style="color:red;">Ingresa un monto válido.</span>';
            return;
        }
        fetch(BASE_URL + 'api/mi_gasto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModal('modalMiGasto');
                document.getElementById('miGastoMonto').value = '';
                document.getElementById('miGastoDescripcion').value = '';
                msg.innerHTML = '';
                cargarMisGastos();
            } else {
                msg.innerHTML = '<span style="color:red;">' + (d.error || 'Error al enviar el reporte.') + '</span>';
            }
        })
        .catch(e => {
            msg.innerHTML = '<span style="color:red;">Error de conexión.</span>';
            console.error('Error reportar gasto:', e);
        });
    }

    function cargarMiNomina() {
        const container = document.getElementById('myPayrollList');
        const user = getUser();
        if (!user.id) {
            container.innerHTML = '<p style="color:red;">No se pudo identificar tu usuario.</p>';
            return;
        }
        container.innerHTML = '<p>Cargando tu nómina...</p>';
        const mes = new Date().toISOString().slice(0, 7);
        fetch(BASE_URL + 'api/calculate_payroll.php?caregiver_id=' + user.id + '&month=' + mes)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = '<p style="color:red;">' + data.error + '</p>';
                    return;
                }
                let html = '<div style="background:#f8f9fa;padding:16px;border-radius:8px;">';
                html += '<h4>Nómina - ' + mes + '</h4>';
                html += '<p>Horas Ordinarias: ' + (data.ordinary_hours || 0) + '</p>';
                html += '<p>Horas Extra 1.25: ' + (data.overtime_125 || 0) + '</p>';
                html += '<p>Horas Extra 1.75: ' + (data.overtime_175 || 0) + '</p>';
                html += '<p><strong>Total a Pagar: $' + (data.total_pagar || 0) + '</strong></p>';
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(e => {
                container.innerHTML = '<p style="color:red;">Error al calcular nómina.</p>';
                console.error('Error nómina:', e);
            });
    }

    // ============================================================
    //  FUNCIONES PARA POLICÍA
    // ============================================================
    function cargarMisRutas() {
        document.getElementById('myRoutesList').innerHTML = '<p>Cargando tus rutas...</p>';
    }

    function cargarMapaPolicia() {
        document.getElementById('mapList').innerHTML = '<p>Cargando mapa...</p>';
    }

    function importarCSV() {
        const msg = document.getElementById('csvMsg');
        const archivo = document.getElementById('csvPacientes').files[0];
        if (!archivo) {
            msg.innerHTML = '<span style="color:red;">Selecciona un archivo CSV.</span>';
            return;
        }
        const formData = new FormData();
        formData.append('csv', archivo);
        msg.innerHTML = 'Importando...';
        fetch(BASE_URL + 'api/menores_import.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                msg.innerHTML = `<span style="color:green;">Importados: ${d.insertados}. Omitidos (duplicados/incompletos): ${d.omitidos}.</span>`;
                document.getElementById('csvPacientes').value = '';
                cargarPacientes();
            } else {
                msg.innerHTML = '<span style="color:red;">' + (d.error || 'Error al importar.') + '</span>';
            }
        })
        .catch(e => {
            msg.innerHTML = '<span style="color:red;">Error de conexión.</span>';
            console.error('Error importar CSV:', e);
        });
    }

    function cargarPacientes() {
        const tableId = '#pacientesTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/menores.php')
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (!data || data.error || data.length === 0) {
                    html = '';
                } else {
                    data.forEach(m => {
                        html += `<tr>
                            <td>${m.id}</td>
                            <td>${m.documento || ''}</td>
                            <td>${m.nombre} ${m.apellido || ''}</td>
                            <td>${m.fecha_nacimiento || 'N/A'}</td>
                            <td>${m.formador_responsable || 'N/A'}</td>
                            <td>${m.activo == 1 ? 'Activo' : 'Inactivo'}</td>
                        </tr>`;
                    });
                }
                document.getElementById('pacientesList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                document.getElementById('pacientesList').innerHTML = '<tr><td colspan="6" style="text-align:center;color:red;">Error al cargar pacientes</td></tr>';
                console.error('Error pacientes:', e);
            });
    }

    function cargarVisitas() {
        const tableId = '#visitasTable';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        fetch(BASE_URL + 'api/visitas.php')
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (!data || data.error || data.length === 0) {
                    html = '';
                } else {
                    data.forEach(v => {
                        const acciones = (currentRole === 'admin' && v.status === 'confirmed')
                            ? `<button class="btn btn-success-sm" onclick="actualizarVisita(${v.id}, 'completed')">Completar</button>
                               <button class="btn btn-danger-sm" onclick="actualizarVisita(${v.id}, 'cancelled')">Cancelar</button>`
                            : '';
                        const tipoLabels = { cita: 'Cita', ruta_emergencia: 'Ruta (emergencia)', juzgado: 'Juzgado', cambio_centro: 'Cambio de centro' };
                        const tipoTexto = tipoLabels[v.tipo] || v.tipo || 'N/A';
                        html += `<tr>
                            <td>${v.id}</td>
                            <td>${tipoTexto}</td>
                            <td>${v.paciente_nombre || 'N/A'}</td>
                            <td>${v.visit_date}</td>
                            <td>${v.visit_time}</td>
                            <td>${v.location || 'N/A'}</td>
                            <td>${v.formador_name || 'N/A'}</td>
                            <td><span class="badge badge-${v.status}">${v.status}</span></td>
                            <td><button class="btn btn-primary-sm" onclick="verQR('${v.qr_token}')">Ver QR</button></td>
                            <td>${acciones}</td>
                        </tr>`;
                    });
                }
                document.getElementById('visitasList').innerHTML = html;
                $(tableId).DataTable({
                    pageLength: 10,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                    columnDefs: [{ orderable: false, targets: [8, 9] }],
                    destroy: true,
                    autoWidth: false
                });
            })
            .catch(e => {
                document.getElementById('visitasList').innerHTML = '<tr><td colspan="9" style="text-align:center;color:red;">Error al cargar visitas</td></tr>';
                console.error('Error visitas:', e);
            });
    }

    function abrirModalVisita() {
        const selPaciente = document.getElementById('visitaPaciente');
        const selCustodio = document.getElementById('visitaCustodio');
        selPaciente.innerHTML = '<option value="">Cargando...</option>';
        selCustodio.innerHTML = '<option value="">Cargando...</option>';

        fetch(BASE_URL + 'api/menores.php')
            .then(r => r.json())
            .then(data => {
                selPaciente.innerHTML = '<option value="">Selecciona un paciente</option>';
                (data || []).forEach(m => {
                    selPaciente.innerHTML += `<option value="${m.id}">${m.nombre} ${m.apellido || ''} (${m.documento || 'sin doc.'})</option>`;
                });
            });

        fetch(BASE_URL + 'api/users.php?role=custodio')
            .then(r => r.json())
            .then(data => {
                selCustodio.innerHTML = '<option value="">Selecciona un custodio</option>';
                (data || []).forEach(u => {
                    selCustodio.innerHTML += `<option value="${u.id}">${u.name}</option>`;
                });
            });

        abrirModal('modalVisita');
    }

    function guardarVisita() {
        const msg = document.getElementById('visitaMsg');
        const menorId = document.getElementById('visitaPaciente').value;
        const fecha = document.getElementById('visitaFecha').value;
        const hora = document.getElementById('visitaHora').value;
        const custodioId = document.getElementById('visitaCustodio').value;
        if (!menorId || !fecha || !hora || !custodioId) {
            msg.innerHTML = '<span style="color:red;">Paciente, fecha, hora y custodio son obligatorios.</span>';
            return;
        }
        const formData = new FormData();
        formData.append('tipo', document.getElementById('visitaTipo').value);
        formData.append('menor_id', menorId);
        formData.append('visit_date', fecha);
        formData.append('visit_time', hora);
        formData.append('location', document.getElementById('visitaLugar').value);
        formData.append('motivo', document.getElementById('visitaMotivo').value);
        formData.append('custodio_id', custodioId);
        const archivo = document.getElementById('visitaAdjunto').files[0];
        if (archivo) formData.append('attachment', archivo);

        fetch(BASE_URL + 'api/visitas.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModal('modalVisita');
                document.getElementById('visitaFecha').value = '';
                document.getElementById('visitaTipo').value = 'cita';
                document.getElementById('visitaHora').value = '';
                document.getElementById('visitaLugar').value = '';
                document.getElementById('visitaMotivo').value = '';
                document.getElementById('visitaAdjunto').value = '';
                msg.innerHTML = '';
                cargarVisitas();
                if (d.qr_token) verQR(d.qr_token);
            } else {
                msg.innerHTML = '<span style="color:red;">' + (d.error || 'Error al crear la visita.') + '</span>';
            }
        })
        .catch(e => {
            msg.innerHTML = '<span style="color:red;">Error de conexión.</span>';
            console.error('Error crear visita:', e);
        });
    }

    function verQR(qrToken) {
        const fichaUrl = BASE_URL + 'api/ficha_visita.php?token=' + qrToken;
        const qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(fichaUrl);
        document.getElementById('qrImage').src = qrImgUrl;
        document.getElementById('qrLink').href = fichaUrl;
        document.getElementById('qrLink').textContent = 'Abrir ficha';
        abrirModal('modalQR');
    }

    function actualizarVisita(id, status) {
        fetch(BASE_URL + 'api/visitas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cargarVisitas();
            } else {
                alert(d.error || 'Error al actualizar la visita.');
            }
        })
        .catch(e => console.error('Error actualizar visita:', e));
    }

    // ============================================================
    //  CONFIGURACIÓN
    // ============================================================
    function cargarConfiguracionInicial() {
        fetch(BASE_URL + 'api/guardar_config.php')
            .then(r => r.json())
            .then(d => {
                if (!d || d.error) return;
                const elQr = document.getElementById('func_qr');
                const elAgenda = document.getElementById('func_agenda');
                const elPush = document.getElementById('func_push');
                const elMaint = document.getElementById('func_maintenance');
                if (elQr) elQr.value = (d.qr == '1') ? '1' : '0';
                if (elAgenda) elAgenda.value = (d.agenda == '1') ? '1' : '0';
                if (elPush) elPush.value = (d.push == '1') ? '1' : '0';
                if (elMaint) elMaint.value = (d.maintenance == '1') ? '1' : '0';
            })
            .catch(e => console.error('Error cargando configuración:', e));
    }

    function guardarConfiguracion() {
        const qr = document.getElementById('func_qr').value;
        const agenda = document.getElementById('func_agenda').value;
        const push = document.getElementById('func_push').value;
        const maintenance = document.getElementById('func_maintenance').value;

        fetch(BASE_URL + 'api/guardar_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr, agenda, push, maintenance })
        })
        .then(r => r.json())
        .then(d => {
            document.getElementById('configMsg').innerHTML = d.success ? '[OK] Configuración guardada.' : '[X] Error: ' + d.error;
        })
        .catch(() => {
            document.getElementById('configMsg').innerHTML = '[X] Error de conexión.';
        });
    }

    // ============================================================
    //  GENERAR TURNOS
    // ============================================================
    function generarTurnos() {
        const msg = document.getElementById('generarMsg');
        msg.innerHTML = 'Generando...';
        fetch(BASE_URL + 'api/generate_shifts.php?week_start=' + getMonday())
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msg.innerHTML = '✅ Turnos generados: ' + data.created + (data.errors && data.errors.length ? ' (errores: ' + data.errors.join(', ') + ')' : '');
                } else {
                    msg.innerHTML = '❌ Error: ' + data.error;
                }
            })
            .catch(e => {
                msg.innerHTML = '❌ Error de conexión.';
                console.error('Error generando turnos:', e);
            });
    }

    function getMonday() {
        const d = new Date();
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff)).toISOString().slice(0,10);
    }

    // ============================================================
    //  FUNCIÓN loadTabData
    // ============================================================
    function loadTabData(tabId) {
        switch(tabId) {
            case 'users': cargarUsuarios(); break;
            case 'shifts': cargarTurnos(); break;
            case 'expenses': cargarGastos(); break;
            case 'changes': cargarCambios(); break;
            case 'alerts': cargarAlertas(); break;
            case 'sections': cargarSections(); break;
            case 'myShifts': cargarMisTurnos(); break;
            case 'myExpenses': cargarMisGastos(); break;
            case 'myPayroll': cargarMiNomina(); break;
            case 'myRoutes': cargarMisRutas(); break;
            case 'map': cargarMapaPolicia(); break;
            case 'visitas': cargarVisitas(); break;
            case 'pacientes': cargarPacientes(); break;
            default: break;
        }
    }

    // ============================================================
    //  RENDERIZADO DE TABS
    // ============================================================
    function renderDashboard() {
        const tabsContainer = document.getElementById('mainTabs');
        const contentContainer = document.getElementById('tabContentContainer');
        const tabs = tabsConfig[currentRole] || [];

        if (!tabs.length) {
            contentContainer.innerHTML = '<p>No tienes permisos para ver este panel.</p>';
            return;
        }

        tabsContainer.innerHTML = tabs.map((tab, index) => `
            <button class="tab ${index === 0 ? 'active' : ''}" data-tab="${tab.id}" title="${tab.label}">
                ${tab.icon}
                <span class="tab-label">${tab.label}</span>
            </button>
        `).join('');

        contentContainer.innerHTML = tabs.map((tab, index) => {
            let columns = [];
            let tableId = '';
            let listId = '';
            let addButton = '';
            switch(tab.id) {
                case 'users':
                    columns = ['ID', 'Nombre', 'Email', 'Teléfono', 'Rol', 'Patrón', 'Sección', 'Grupo', 'Acciones'];
                    tableId = 'usersTable';
                    listId = 'usersList';
                    addButton = currentRole === 'admin' ? '<button onclick="abrirModalUsuario()" class="btn btn-primary">+ Nuevo</button>' : '';
                    break;
                case 'shifts':
                    columns = ['ID', 'Cuidador', 'Fecha', 'Horario', 'Patrón', 'Sección', 'Asignado por', 'Ubicación', 'Estado', 'Acciones'];
                    tableId = 'shiftsTable';
                    listId = 'shiftsList';
                    addButton = '<button onclick="abrirModalTurno()" class="btn btn-primary">+ Nuevo</button>';
                    break;
                case 'expenses':
                    columns = ['ID', 'Cuidador', 'Tipo', 'Monto', 'Estado', 'Acciones'];
                    tableId = 'expensesTable';
                    listId = 'expensesList';
                    addButton = '';
                    break;
                case 'changes':
                    columns = ['ID', 'Turno', 'Cuidador', 'Fecha deseada', 'Hora deseada', 'Motivo', 'Estado', 'Acciones'];
                    tableId = 'changesTable';
                    listId = 'changesList';
                    addButton = '';
                    break;
                case 'alerts':
                    columns = ['ID', 'Mensaje', 'Tipo', 'Origen', 'Fecha', 'Estado'];
                    tableId = 'alertsTable';
                    listId = 'alertsList';
                    addButton = '';
                    break;
                case 'sections':
                    columns = ['ID', 'Nombre', 'Descripción', 'Estado', 'Acciones'];
                    tableId = 'sectionsTable';
                    listId = 'sectionsList';
                    addButton = '<button onclick="abrirModalSeccion()" class="btn btn-primary">+ Nueva</button>';
                    break;
                case 'myShifts':
                    addButton = '<button onclick="abrirModal(\'modalMiTurno\')" class="btn btn-primary">+ Solicitar Turno</button>';
                    break;
                case 'myExpenses':
                    addButton = '<button onclick="abrirModal(\'modalMiGasto\')" class="btn btn-primary">+ Reportar Gasto</button>';
                    break;
                case 'visitas':
                    columns = ['ID', 'Tipo', 'Paciente', 'Fecha', 'Hora', 'Lugar', 'Custodio', 'Estado', 'Ficha', 'Acciones'];
                    tableId = 'visitasTable';
                    listId = 'visitasList';
                    addButton = '<button onclick="abrirModalVisita()" class="btn btn-primary">+ Nuevo Acompañamiento</button>';
                    break;
                case 'pacientes':
                    columns = ['ID', 'Documento', 'Nombre', 'Fecha Nacimiento', 'Custodio Responsable', 'Estado'];
                    tableId = 'pacientesTable';
                    listId = 'pacientesList';
                    addButton = currentRole === 'admin' ? '<button onclick="abrirModal(\'modalImportarCSV\')" class="btn btn-primary">+ Importar CSV</button>' : '';
                    break;
                default:
                    // Para pestañas de cuidador/policía (sin DataTables)
                    break;
            }

            if (!columns.length) {
                return `<div id="tab-${tab.id}" class="tab-content ${index === 0 ? 'active' : ''}" style="${index !== 0 ? 'display:none;' : ''}">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">${tab.label}</span>
                            ${addButton}
                        </div>
                        <div id="${tab.id}List" class="table-container">
                            <p>Cargando datos...</p>
                        </div>
                    </div>
                </div>`;
            }

            const thead = `<thead><tr>${columns.map(col => `<th>${col}</th>`).join('')}</tr></thead>`;
            const tbody = `<tbody id="${listId}"><tr><td colspan="${columns.length}" style="text-align:center;">Cargando...</td></tr></tbody>`;

            return `<div id="tab-${tab.id}" class="tab-content ${index === 0 ? 'active' : ''}" style="${index !== 0 ? 'display:none;' : ''}">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">${tab.label}</span>
                        ${addButton}
                    </div>
                    <div class="table-container">
                        <table id="${tableId}">
                            ${thead}
                            ${tbody}
                        </table>
                    </div>
                </div>
            </div>`;
        }).join('');

        // Eventos de tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                this.classList.add('active');
                const tabId = this.dataset.tab;
                const el = document.getElementById('tab-' + tabId);
                if (el) el.style.display = 'block';
                loadTabData(tabId);
            });
        });

        // Cargar datos del primer tab
        const firstTab = tabs[0];
        if (firstTab) {
            setTimeout(() => {
                loadTabData(firstTab.id);
            }, 200);
        }
    }

    // ============================================================
    //  EVENT DELEGATION PARA BOTONES DINÁMICOS
    // ============================================================
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('btn-editar-usuario')) {
            const id = target.dataset.id;
            if (id) editarUsuario(id);
        }
        if (target.classList.contains('btn-eliminar-usuario')) {
            const id = target.dataset.id;
            if (id) eliminarUsuario(id);
        }
        if (target.classList.contains('btn-editar-turno')) {
            const id = target.dataset.id;
            if (id) editarTurno(id);
        }
        if (target.classList.contains('btn-eliminar-turno')) {
            const id = target.dataset.id;
            if (id) eliminarTurno(id);
        }
        if (target.classList.contains('btn-aprobar-gasto')) {
            const id = target.dataset.id;
            if (id) actualizarGasto(id, 'approved');
        }
        if (target.classList.contains('btn-rechazar-gasto')) {
            const id = target.dataset.id;
            if (id) actualizarGasto(id, 'rejected');
        }
        if (target.classList.contains('btn-aprobar-cambio')) {
            const id = target.dataset.id;
            if (id) aprobarCambio(id);
        }
        if (target.classList.contains('btn-rechazar-cambio')) {
            const id = target.dataset.id;
            if (id) rechazarCambio(id);
        }
        if (target.classList.contains('btn-editar-seccion')) {
            const id = target.dataset.id;
            if (id) editarSeccion(id);
        }
        if (target.classList.contains('btn-eliminar-seccion')) {
            const id = target.dataset.id;
            if (id) eliminarSeccion(id);
        }
    });

    // ============================================================
    //  SECCIONES CRUD (básico)
    // ============================================================
    function abrirModalSeccion(s) {
        // Implementar modal para secciones si es necesario
        alert('Función en desarrollo: editar sección');
    }

    function editarSeccion(id) {
        alert('Función en desarrollo: editar sección ' + id);
    }

    function eliminarSeccion(id) {
        if (!confirm('¿Eliminar esta sección?')) return;
        fetch(BASE_URL + 'api/delete_section.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cargarSections();
            } else {
                alert('Error: ' + (d.error || 'Desconocido'));
            }
        })
        .catch(e => alert('Error de conexion.'));
    }

    // ============================================================
    //  EXPONER FUNCIONES GLOBALMENTE
    // ============================================================
    window.cerrarModal = cerrarModal;
    window.abrirModal = abrirModal;
    window.cargarUsuarios = cargarUsuarios;
    window.cargarTurnos = cargarTurnos;
    window.cargarGastos = cargarGastos;
    window.cargarCambios = cargarCambios;
    window.cargarAlertas = cargarAlertas;
    window.cargarSections = cargarSections;
    window.cargarMisTurnos = cargarMisTurnos;
    window.cargarMisGastos = cargarMisGastos;
    window.cargarMiNomina = cargarMiNomina;
    window.abrirModalUsuario = abrirModalUsuario;
    window.editarUsuario = editarUsuario;
    window.eliminarUsuario = eliminarUsuario;
    window.guardarUsuario = guardarUsuario;
    window.abrirModalTurno = abrirModalTurno;
    window.editarTurno = editarTurno;
    window.eliminarTurno = eliminarTurno;
    window.guardarTurno = guardarTurno;
    window.actualizarGasto = actualizarGasto;
    window.aprobarCambio = aprobarCambio;
    window.rechazarCambio = rechazarCambio;
    window.guardarConfiguracion = guardarConfiguracion;
    window.generarTurnos = generarTurnos;
    window.getMonday = getMonday;
    window.loadTabData = loadTabData;

    // ============================================================
    //  INICIO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('loaded');
        cargarStats();
        renderDashboard();
        if (currentRole === 'admin') {
            cargarConfiguracionInicial();
        }

        setInterval(function() {
            cargarStats();
            const activeTab = document.querySelector('.tab.active');
            if (activeTab) {
                loadTabData(activeTab.dataset.tab);
            }
        }, 60000);
    });
    </script>
</div><!-- fin admin-panel -->

<?php include 'footer.php'; ?>