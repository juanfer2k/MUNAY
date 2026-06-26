/**
 * js/dashboard.js
 * ============================================================
 * Toda la lógica del Dashboard Admin (dashboard_admin.php).
 * Antes vivía como un <script> inline de ~500 líneas dentro del
 * propio .php, y coexistía con un archivo huérfano (js/dsshboard.js)
 * que nadie cargaba y que había divergido con funciones distintas.
 *
 * Fix aplicado en este archivo: las funciones aprobarCambio() y
 * rechazarCambio() llamaban a 'api/request.php' (singular, no existe).
 * El archivo real es 'api/requests.php' (plural), al que también
 * se le agregó soporte PATCH para poder aprobar/rechazar.
 * ============================================================
 */
(function () {
    'use strict';

    // Evita que texto libre (nombres, motivos, mensajes) rompa el HTML
    // de la tabla o abra una puerta a XSS si llega algún caracter
    // especial desde la base de datos.
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ============================================================
    //  ESTADÍSTICAS
    // ============================================================
    function cargarStats() {
        fetch('api/dashboard_stats.php')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('totalUsers').textContent = d.totalCaregivers || 0;
                document.getElementById('todayShifts').textContent = d.todayShifts || 0;
                document.getElementById('activeNow').textContent = d.activeNow || 0;
                document.getElementById('pendingAlerts').textContent = d.pendingAlerts || 0;
            })
            .catch(function (e) { console.error('Stats error:', e); });
    }

    // ============================================================
    //  USUARIOS
    // ============================================================
    function cargarUsuarios() {
        fetch('api/users.php')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var html = '';
                (d || []).forEach(function (u) {
                    var phoneDigits = (u.phone || '').replace(/[^0-9]/g, '');
                    var phoneLink = u.phone
                        ? '<a href="https://wa.me/57' + phoneDigits + '" target="_blank" class="whatsapp-link">W</a> ' + escapeHtml(u.phone)
                        : 'N/A';
                    html += '<tr>' +
                        '<td>' + u.id + '</td>' +
                        '<td><strong>' + escapeHtml(u.name) + '</strong></td>' +
                        '<td>' + escapeHtml(u.email) + '</td>' +
                        '<td>' + phoneLink + '</td>' +
                        '<td><span class="badge badge-' + u.role + '">' + escapeHtml(u.role) + '</span></td>' +
                        '<td>' + escapeHtml(u.group_type || 'N/A') + '</td>' +
                        '<td>' +
                        '<button class="btn btn-primary btn-sm" onclick="editarUsuario(' + u.id + ')">E</button> ' +
                        '<button class="btn btn-danger btn-sm" onclick="eliminarUsuario(' + u.id + ')">X</button>' +
                        '</td></tr>';
                });
                document.getElementById('usersList').innerHTML = html;
                refreshTable('#usersTable', 6);
            })
            .catch(function (e) {
                document.getElementById('usersList').innerHTML = '<tr><td colspan="7" style="text-align:center;color:red;">Error al cargar usuarios</td></tr>';
                console.error('Error usuarios:', e);
            });
    }

    function abrirModalUsuario(u) {
        document.getElementById('modalUsuarioTitulo').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('editUserId').value = (u && u.id) || '';
        document.getElementById('inputNombre').value = (u && u.name) || '';
        document.getElementById('inputEmail').value = (u && u.email) || '';
        document.getElementById('inputTelefono').value = (u && u.phone) || '';
        document.getElementById('inputPassword').value = '';
        document.getElementById('inputRol').value = (u && u.role) || 'caregiver';
        document.getElementById('inputGrupo').value = (u && u.group_type) || 'A';
        abrirModal('modalUsuario');
    }

    function editarUsuario(id) {
        fetch('api/user.php?id=' + id)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.error) abrirModalUsuario(d);
                else showAlert('Error: ' + d.error, 'error');
            })
            .catch(function () { showAlert('Error de conexión al cargar usuario.', 'error'); });
    }

    function guardarUsuario() {
        var id = document.getElementById('editUserId').value;
        var phone = document.getElementById('inputTelefono').value.trim();
        if (!phone) { showAlert('El teléfono es obligatorio.', 'warning'); return; }

        var data = {
            id: id || null,
            name: document.getElementById('inputNombre').value,
            email: document.getElementById('inputEmail').value,
            phone: phone,
            password: document.getElementById('inputPassword').value || null,
            role: document.getElementById('inputRol').value,
            group_type: document.getElementById('inputGrupo').value
        };

        fetch('api/user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    showAlert('Usuario guardado correctamente.', 'success');
                    cerrarModal('modalUsuario');
                    cargarUsuarios();
                    cargarStats();
                } else {
                    showAlert('Error al guardar: ' + (d.error || 'Desconocido'), 'error');
                }
            })
            .catch(function (e) {
                showAlert('Error de conexión. Verifica que el servidor esté accesible.', 'error');
                console.error('Error guardando usuario:', e);
            });
    }

    function eliminarUsuario(id) {
        if (!confirm('¿Eliminar este usuario?')) return;
        fetch('api/user.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) { cargarUsuarios(); cargarStats(); }
                else showAlert('Error al eliminar: ' + (d.error || 'Desconocido'), 'error');
            })
            .catch(function () { showAlert('Error de conexión.', 'error'); });
    }

    // ============================================================
    //  TURNOS
    // ============================================================
    function cargarTurnos() {
        fetch('api/shifts.php')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var html = '';
                (d || []).forEach(function (s) {
                    html += '<tr>' +
                        '<td>' + s.id + '</td>' +
                        '<td>' + escapeHtml(s.caregiver_name || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(s.shift_date) + '</td>' +
                        '<td>' + escapeHtml(s.start_time) + '-' + escapeHtml(s.end_time) + '</td>' +
                        '<td>' + escapeHtml(s.location || 'N/A') + '</td>' +
                        '<td><span class="badge badge-' + s.status + '">' + escapeHtml(s.status) + '</span></td>' +
                        '<td>' +
                        '<button class="btn btn-primary btn-sm" onclick="editarTurno(' + s.id + ')">E</button> ' +
                        '<button class="btn btn-danger btn-sm" onclick="eliminarTurno(' + s.id + ')">X</button>' +
                        '</td></tr>';
                });
                document.getElementById('shiftsList').innerHTML = html;
                refreshTable('#shiftsTable', 6);
            })
            .catch(function (e) {
                document.getElementById('shiftsList').innerHTML = '<tr><td colspan="7" style="text-align:center;color:red;">Error al cargar turnos</td></tr>';
                console.error('Error turnos:', e);
            });
    }

    function abrirModalTurno(t) {
        document.getElementById('modalTurnoTitulo').textContent = t ? 'Editar Turno' : 'Nuevo Turno';
        document.getElementById('editTurnoId').value = (t && t.id) || '';
        document.getElementById('inputFecha').value = (t && t.shift_date) || '';
        document.getElementById('inputInicio').value = (t && t.start_time) || '';
        document.getElementById('inputFin').value = (t && t.end_time) || '';
        document.getElementById('inputUbicacion').value = (t && t.location) || '';
        document.getElementById('inputMenor').value = (t && t.minor_id) || '';
        document.getElementById('inputTipo').value = (t && t.shift_type) || 'base';
        document.getElementById('inputEstado').value = (t && t.status) || 'pending';

        fetch('api/users.php?role=caregiver')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var sel = document.getElementById('selectCuidador');
                sel.innerHTML = '<option value="">Seleccionar</option>';
                (d || []).forEach(function (u) {
                    var selected = (t && u.id == t.caregiver_id) ? 'selected' : '';
                    sel.innerHTML += '<option value="' + u.id + '" ' + selected + '>' + u.name + '</option>';
                });
            });
        abrirModal('modalTurno');
    }

    function editarTurno(id) {
        fetch('api/shift.php?id=' + id)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.error) abrirModalTurno(d);
                else showAlert('Error: ' + d.error, 'error');
            })
            .catch(function () { showAlert('Error de conexión al cargar turno.', 'error'); });
    }

    function guardarTurno() {
        var data = {
            id: document.getElementById('editTurnoId').value || null,
            caregiver_id: document.getElementById('selectCuidador').value,
            shift_date: document.getElementById('inputFecha').value,
            start_time: document.getElementById('inputInicio').value,
            end_time: document.getElementById('inputFin').value,
            location: document.getElementById('inputUbicacion').value,
            minor_id: document.getElementById('inputMenor').value,
            shift_type: document.getElementById('inputTipo').value,
            status: document.getElementById('inputEstado').value
        };
        if (!data.caregiver_id || !data.shift_date || !data.start_time) {
            showAlert('Completa todos los campos obligatorios.', 'warning');
            return;
        }
        fetch('api/shift.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    showAlert('Turno guardado correctamente.', 'success');
                    cerrarModal('modalTurno');
                    cargarTurnos();
                    cargarStats();
                } else {
                    showAlert('Error al guardar turno: ' + (d.error || 'Desconocido'), 'error');
                }
            })
            .catch(function (e) {
                showAlert('Error de conexión.', 'error');
                console.error('Error guardando turno:', e);
            });
    }

    function eliminarTurno(id) {
        if (!confirm('¿Eliminar este turno?')) return;
        fetch('api/shift.php', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) { cargarTurnos(); cargarStats(); }
                else showAlert('Error al eliminar turno: ' + (d.error || 'Desconocido'), 'error');
            })
            .catch(function () { showAlert('Error de conexión.', 'error'); });
    }

    // ============================================================
    //  GASTOS
    // ============================================================
    function cargarGastos() {
        fetch('api/expenses.php')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var html = '';
                (d || []).forEach(function (e) {
                    html += '<tr>' +
                        '<td>' + e.id + '</td>' +
                        '<td>' + escapeHtml(e.caregiver_name || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(e.type) + '</td>' +
                        '<td>$' + e.amount + '</td>' +
                        '<td><span class="badge badge-' + e.status + '">' + escapeHtml(e.status) + '</span></td>' +
                        '<td>' +
                        '<button class="btn btn-success btn-sm" onclick="actualizarGasto(' + e.id + ', \'approved\')">Aprobar</button> ' +
                        '<button class="btn btn-danger btn-sm" onclick="actualizarGasto(' + e.id + ', \'rejected\')">Rechazar</button>' +
                        '</td></tr>';
                });
                document.getElementById('expensesList').innerHTML = html;
                refreshTable('#expensesTable', 5);
            })
            .catch(function (e) { console.error('Error gastos:', e); });
    }

    function actualizarGasto(id, estado) {
        fetch('api/expense.php', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: estado }) })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) { cargarGastos(); cargarStats(); }
                else showAlert('Error al actualizar gasto: ' + (d.error || 'Desconocido'), 'error');
            })
            .catch(function () { showAlert('Error de conexión.', 'error'); });
    }

    // ============================================================
    //  CAMBIOS (Solicitudes de cambio de turno)
    // ============================================================
    function cargarCambios() {
        fetch('api/requests.php')
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP error ' + r.status);
                return r.json();
            })
            .then(function (data) {
                var html = '';
                (data || []).forEach(function (r) {
                    html += '<tr>' +
                        '<td>' + r.id + '</td>' +
                        '<td>' + r.shift_id + '</td>' +
                        '<td>' + escapeHtml(r.caregiver_name || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(r.requested_date) + '</td>' +
                        '<td>' + escapeHtml(r.requested_time) + '</td>' +
                        '<td>' + escapeHtml(r.reason || 'Sin motivo') + '</td>' +
                        '<td><span class="badge badge-' + r.status + '">' + escapeHtml(r.status) + '</span></td>' +
                        '<td>' +
                        (r.status === 'pending'
                            ? '<button class="btn btn-success btn-sm" onclick="aprobarCambio(' + r.id + ')">Aprobar</button> ' +
                              '<button class="btn btn-danger btn-sm" onclick="rechazarCambio(' + r.id + ')">Rechazar</button>'
                            : '—') +
                        '</td></tr>';
                });
                document.getElementById('changesList').innerHTML = html;
                refreshTable('#changesTable', 7);
            })
            .catch(function (error) {
                console.error('Error cambios:', error);
                document.getElementById('changesList').innerHTML = '<tr><td colspan="8" style="text-align:center;color:red;">Error al cargar solicitudes: ' + error.message + '</td></tr>';
            });
    }

    function actualizarEstadoCambio(id, status, confirmMsg, okMsg) {
        if (!confirm(confirmMsg)) return;
        // FIX: antes apuntaba a 'api/request.php' (no existe). El endpoint
        // real es 'api/requests.php', que ahora también acepta PATCH.
        fetch('api/requests.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: status })
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    showAlert(okMsg, 'success');
                    cargarCambios();
                    cargarStats();
                } else {
                    showAlert('Error: ' + (d.error || 'Desconocido'), 'error');
                }
            })
            .catch(function () { showAlert('Error de conexión.', 'error'); });
    }

    function aprobarCambio(id) {
        actualizarEstadoCambio(id, 'approved', '¿Aprobar esta solicitud?', 'Solicitud aprobada.');
    }
    function rechazarCambio(id) {
        actualizarEstadoCambio(id, 'rejected', '¿Rechazar esta solicitud?', 'Solicitud rechazada.');
    }

    // ============================================================
    //  ALERTAS
    // ============================================================
    function cargarAlertas() {
        fetch('api/alerts.php')
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP error ' + r.status);
                return r.json();
            })
            .then(function (data) {
                var html = '';
                (data || []).forEach(function (a) {
                    html += '<tr>' +
                        '<td>' + a.id + '</td>' +
                        '<td>' + escapeHtml(a.message) + '</td>' +
                        '<td><span class="badge badge-' + a.type + '">' + escapeHtml(a.type) + '</span></td>' +
                        '<td>' + escapeHtml(a.origin || 'sistema') + '</td>' +
                        '<td>' + escapeHtml(a.created_at) + '</td>' +
                        '<td>' + (a.is_read ? 'Leída' : 'Pendiente') + '</td>' +
                        '</tr>';
                });
                document.getElementById('alertsList').innerHTML = html;
                refreshTable('#alertsTable', 5);
            })
            .catch(function (error) {
                console.error('Error alertas:', error);
                document.getElementById('alertsList').innerHTML = '<tr><td colspan="6" style="text-align:center;color:red;">Error al cargar alertas: ' + error.message + '</td></tr>';
            });
    }

    // ============================================================
    //  UTILIDAD: (re)inicializar una DataTable sin duplicarla
    // ============================================================
    function refreshTable(selector, lastColIndex) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }
        $(selector).DataTable({
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                emptyTable: 'No hay registros para mostrar.'
            },
            columnDefs: [{ orderable: false, targets: lastColIndex }]
        });
    }

    // ============================================================
    //  TABS
    // ============================================================
    var loaders = {
        users: cargarUsuarios,
        shifts: cargarTurnos,
        expenses: cargarGastos,
        changes: cargarCambios,
        alerts: cargarAlertas
    };

    document.querySelectorAll('.tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.style.display = 'none'; });
            this.classList.add('active');
            var tabId = this.dataset.tab;
            var el = document.getElementById('tab-' + tabId);
            if (el) el.style.display = 'block';
            if (loaders[tabId]) loaders[tabId]();
        });
    });

    // ============================================================
    //  INICIO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        cargarStats();
        // Solo se carga la pestaña visible por defecto ("Usuarios").
        // Las demás (Turnos, Gastos, Cambios, Alertas) se cargan al
        // hacer clic en su tab (ver el listener de .tab más abajo).
        // Antes se cargaban las 5 de una vez, inicializando DataTables
        // sobre tablas con display:none — causa conocida de warnings
        // de "Incorrect column count" en DataTables.
        cargarUsuarios();

        setInterval(function () {
            cargarStats();
            var activeTab = document.querySelector('.tab.active');
            if (activeTab && loaders[activeTab.dataset.tab]) loaders[activeTab.dataset.tab]();
        }, 60000);
    });

    // Exponer funciones globalmente para los onclick="" del HTML
    window.abrirModalUsuario = abrirModalUsuario;
    window.editarUsuario = editarUsuario;
    window.guardarUsuario = guardarUsuario;
    window.eliminarUsuario = eliminarUsuario;
    window.abrirModalTurno = abrirModalTurno;
    window.editarTurno = editarTurno;
    window.guardarTurno = guardarTurno;
    window.eliminarTurno = eliminarTurno;
    window.actualizarGasto = actualizarGasto;
    window.aprobarCambio = aprobarCambio;
    window.rechazarCambio = rechazarCambio;
})();
