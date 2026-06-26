// ===== DASHBOARD ADMIN - TODAS LAS FUNCIONES =====
(function() {
    'use strict';

    // ===== FUNCIONES BASICAS =====
    function cerrarModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }
    function abrirModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('active');
    }

    // ===== CERRAR MODALES CON CLICK AFUERA =====
    document.querySelectorAll('.modal-overlay').forEach(function(m) {
        m.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
    document.querySelectorAll('[data-close]').forEach(function(b) {
        b.addEventListener('click', function() {
            cerrarModal(this.dataset.close);
        });
    });

    // ===== TABS =====
    document.querySelectorAll('.tab').forEach(function(t) {
        t.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(function(x) { x.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function(x) { x.style.display = 'none'; });
            this.classList.add('active');
            var tabId = 'tab-' + this.dataset.tab;
            var el = document.getElementById(tabId);
            if (el) el.style.display = 'block';
            if (this.dataset.tab === 'users') cargarUsuarios();
            else if (this.dataset.tab === 'shifts') cargarTurnos();
            else if (this.dataset.tab === 'expenses') cargarGastos();
            else if (this.dataset.tab === 'alerts') cargarAlertas();
            else if (this.dataset.tab === 'payroll') cargarCuidadoresNomina();
        });
    });

    // ===== ESTADISTICAS =====
    function cargarStats() {
        fetch('api/dashboard_stats.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('totalUsers').textContent = d.totalCaregivers || 0;
            document.getElementById('todayShifts').textContent = d.todayShifts || 0;
            document.getElementById('activeNow').textContent = d.activeNow || 0;
            document.getElementById('pendingAlerts').textContent = d.pendingAlerts || 0;
        })
        .catch(function(e) { console.log('Stats error:', e); });
    }

    // ===== USUARIOS =====
    function cargarUsuarios() {
        fetch('api/users.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var html = '';
            if (!d || d.length === 0) {
                html = '<tr><td colspan="7" style="text-align:center;">No hay usuarios</td></tr>';
            } else {
                d.forEach(function(u) {
                    var phoneLink = u.phone ? '<a href="https://wa.me/57' + u.phone.replace(/[^0-9]/g, '') + '" target="_blank" class="whatsapp-link">W</a> ' + u.phone : 'N/A';
                    html += '<tr><td>' + u.id + '</td><td><strong>' + u.name + '</strong></td><td>' + u.email + '</td><td>' + phoneLink + '</td><td><span class="badge badge-' + u.role + '">' + u.role + '</span></td><td>' + (u.group_type || 'N/A') + '</td><td><button class="btn btn-primary btn-sm" data-editar="' + u.id + '">E</button> <button class="btn btn-danger btn-sm" data-eliminar="' + u.id + '">X</button></td></tr>';
                });
            }
            document.getElementById('usersList').innerHTML = html;
            document.querySelectorAll('[data-editar]').forEach(function(b) {
                b.addEventListener('click', function() { editarUsuario(this.dataset.editar); });
            });
            document.querySelectorAll('[data-eliminar]').forEach(function(b) {
                b.addEventListener('click', function() { eliminarUsuario(this.dataset.eliminar); });
            });
        })
        .catch(function(e) {
            document.getElementById('usersList').innerHTML = '<tr><td colspan="7" style="text-align:center;color:red;">Error al cargar usuarios</td></tr>';
        });
    }

    function abrirModalUsuario(u) {
        document.getElementById('modalUsuarioTitulo').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('editUserId').value = u?.id || '';
        document.getElementById('inputNombre').value = u?.name || '';
        document.getElementById('inputEmail').value = u?.email || '';
        document.getElementById('inputTelefono').value = u?.phone || '';
        document.getElementById('inputPassword').value = '';
        document.getElementById('inputRol').value = u?.role || 'caregiver';
        document.getElementById('inputGrupo').value = u?.group_type || 'A';
        abrirModal('modalUsuario');
    }

    function editarUsuario(id) {
        fetch('api/user.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(d) { if (!d.error) abrirModalUsuario(d); else alert('Error: ' + d.error); });
    }

    function guardarUsuario() {
        var id = document.getElementById('editUserId').value;
        var phone = document.getElementById('inputTelefono').value.trim();
        if (!phone) { alert('El telefono es OBLIGATORIO'); return; }
        var data = {
            id: id || null,
            name: document.getElementById('inputNombre').value,
            email: document.getElementById('inputEmail').value,
            phone: phone,
            password: document.getElementById('inputPassword').value || null,
            role: document.getElementById('inputRol').value,
            group_type: document.getElementById('inputGrupo').value
        };
        fetch('api/user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                alert('Usuario guardado');
                cerrarModal('modalUsuario');
                cargarUsuarios();
                cargarStats();
            } else {
                alert('Error: ' + d.error);
            }
        })
        .catch(function() { alert('Error de conexion'); });
    }

    function eliminarUsuario(id) {
        if (!confirm('Eliminar usuario?')) return;
        fetch('api/user.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(function() { cargarUsuarios(); cargarStats(); })
        .catch(function() {});
    }

    // ===== TURNOS =====
    function cargarTurnos() {
        fetch('api/shifts.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var html = '';
            if (!d || d.length === 0) {
                html = '<tr><td colspan="7" style="text-align:center;">No hay turnos</td></tr>';
            } else {
                d.forEach(function(s) {
                    html += '<tr><td>' + s.id + '</td><td>' + (s.caregiver_name || 'N/A') + '</td><td>' + s.shift_date + '</td><td>' + s.start_time + '-' + s.end_time + '</td><td>' + (s.location || 'N/A') + '</td><td><span class="badge badge-' + s.status + '">' + s.status + '</span></td><td><button class="btn btn-primary btn-sm" data-editar-turno="' + s.id + '">E</button> <button class="btn btn-danger btn-sm" data-eliminar-turno="' + s.id + '">X</button></td></tr>';
                });
            }
            document.getElementById('shiftsList').innerHTML = html;
            document.querySelectorAll('[data-editar-turno]').forEach(function(b) {
                b.addEventListener('click', function() { editarTurno(this.dataset.editarTurno); });
            });
            document.querySelectorAll('[data-eliminar-turno]').forEach(function(b) {
                b.addEventListener('click', function() { eliminarTurno(this.dataset.eliminarTurno); });
            });
        })
        .catch(function(e) {
            document.getElementById('shiftsList').innerHTML = '<tr><td colspan="7" style="text-align:center;color:red;">Error al cargar turnos</td></tr>';
        });
    }

    function abrirModalTurno(t) {
        document.getElementById('modalTurnoTitulo').textContent = t ? 'Editar Turno' : 'Nuevo Turno';
        document.getElementById('editTurnoId').value = t?.id || '';
        document.getElementById('inputFecha').value = t?.shift_date || '';
        document.getElementById('inputInicio').value = t?.start_time || '';
        document.getElementById('inputFin').value = t?.end_time || '';
        document.getElementById('inputUbicacion').value = t?.location || '';
        document.getElementById('inputMenor').value = t?.minor_id || '';
        document.getElementById('inputTipo').value = t?.shift_type || 'base';
        document.getElementById('inputEstado').value = t?.status || 'pending';
        fetch('api/users.php?role=caregiver')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var sel = document.getElementById('selectCuidador');
            sel.innerHTML = '<option value="">Seleccionar</option>';
            d.forEach(function(u) {
                sel.innerHTML += '<option value="' + u.id + '" ' + (u.id == t?.caregiver_id ? 'selected' : '') + '>' + u.name + '</option>';
            });
        });
        abrirModal('modalTurno');
    }

    function editarTurno(id) {
        fetch('api/shift.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(d) { if (!d.error) abrirModalTurno(d); else alert('Error: ' + d.error); });
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
            alert('Completa todos los campos');
            return;
        }
        fetch('api/shift.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                alert('Turno guardado');
                cerrarModal('modalTurno');
                cargarTurnos();
                cargarStats();
            } else {
                alert('Error: ' + d.error);
            }
        })
        .catch(function() { alert('Error de conexion'); });
    }

    function eliminarTurno(id) {
        if (!confirm('Eliminar turno?')) return;
        fetch('api/shift.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(function() { cargarTurnos(); cargarStats(); })
        .catch(function() {});
    }

    // ===== GASTOS =====
    function cargarGastos() {
        fetch('api/expenses.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var html = '';
            if (!d || d.length === 0) {
                html = '<tr><td colspan="6" style="text-align:center;">No hay gastos</td></tr>';
            } else {
                d.forEach(function(e) {
                    html += '<tr><td>' + e.id + '</td><td>' + (e.caregiver_name || 'N/A') + '</td><td>' + e.type + '</td><td>$' + e.amount + '</td><td><span class="badge badge-' + e.status + '">' + e.status + '</span></td><td><button class="btn btn-success btn-sm" data-aprobar="' + e.id + '">Aprobar</button> <button class="btn btn-danger btn-sm" data-rechazar="' + e.id + '">Rechazar</button></td></tr>';
                });
            }
            document.getElementById('expensesList').innerHTML = html;
            document.querySelectorAll('[data-aprobar]').forEach(function(b) {
                b.addEventListener('click', function() { actualizarGasto(this.dataset.aprobar, 'approved'); });
            });
            document.querySelectorAll('[data-rechazar]').forEach(function(b) {
                b.addEventListener('click', function() { actualizarGasto(this.dataset.rechazar, 'rejected'); });
            });
        })
        .catch(function(e) { console.log('Gastos error:', e); });
    }

    function actualizarGasto(id, estado) {
        fetch('api/expense.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: estado })
        })
        .then(function() { cargarGastos(); cargarStats(); })
        .catch(function() {});
    }

    // ===== ALERTAS =====
    function cargarAlertas() {
        fetch('api/alerts.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var html = '';
            if (!d || d.length === 0) {
                html = '<tr><td colspan="6" style="text-align:center;">No hay alertas</td></tr>';
            } else {
                d.forEach(function(a) {
                    html += '<tr><td>' + a.id + '</td><td>' + a.message + '</td><td><span class="badge badge-' + a.type + '">' + a.type + '</span></td><td>' + (a.origin || 'system') + '</td><td>' + a.created_at + '</td><td>' + (a.is_read ? 'Leida' : 'Pendiente') + '</td></tr>';
                });
            }
            document.getElementById('alertsList').innerHTML = html;
        })
        .catch(function(e) { console.log('Alertas error:', e); });
    }

    // ===== NOMINA =====
    function cargarCuidadoresNomina() {
        fetch('api/users.php?role=caregiver')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var sel = document.getElementById('payrollCaregiver');
            sel.innerHTML = '<option value="">Todos</option>';
            if (d) {
                d.forEach(function(u) {
                    sel.innerHTML += '<option value="' + u.id + '">' + u.name + '</option>';
                });
            }
        });
    }

    function calcularNomina() {
        var cid = document.getElementById('payrollCaregiver').value;
        var month = document.getElementById('payrollMonth').value;
        if (!month) return;
        fetch('api/calculate_payroll.php?month=' + month + (cid ? '&caregiver_id=' + cid : ''))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) {
                document.getElementById('payrollResult').innerHTML = '<p style="color:red;">' + d.error + '</p>';
            } else {
                document.getElementById('payrollResult').innerHTML = '<div style="background:#f8f9fa;padding:16px;border-radius:8px;"><h3>Resultado - ' + month + '</h3><p>Horas Ordinarias: ' + (d.ordinary_hours || 0) + '</p><p>Horas Extra 1.25: ' + (d.overtime_125 || 0) + '</p><p>Horas Extra 1.75: ' + (d.overtime_175 || 0) + '</p><p><strong>Total a Pagar: $' + (d.total_pagar || 0) + '</strong></p></div>';
            }
        })
        .catch(function() { document.getElementById('payrollResult').innerHTML = '<p style="color:red;">Error al calcular nómina</p>'; });
    }

    // ===== PERFIL =====
    function guardarContraseña() {
        var actual = document.getElementById('passActual').value;
        var nueva = document.getElementById('passNueva').value;
        var confirmar = document.getElementById('passConfirmar').value;
        var msg = document.getElementById('mensajePerfil');
        if (!actual || !nueva || !confirmar) {
            msg.textContent = 'Todos los campos son obligatorios';
            msg.style.color = '#922B21';
            return;
        }
        if (nueva.length < 6) {
            msg.textContent = 'Minimo 6 caracteres';
            msg.style.color = '#922B21';
            return;
        }
        if (nueva !== confirmar) {
            msg.textContent = 'No coinciden';
            msg.style.color = '#922B21';
            return;
        }
        var u = getUser();
        if (!u.id) {
            msg.textContent = 'No se pudo obtener tu ID';
            return;
        }
        fetch('api/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: u.id,
                current_password: actual,
                new_password: nueva
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                msg.textContent = 'Contraseña actualizada';
                msg.style.color = '#1E8449';
                setTimeout(function() { cerrarModal('modalPerfil'); }, 1500);
            } else {
                msg.textContent = 'Error: ' + (d.error || 'Desconocido');
                msg.style.color = '#922B21';
            }
        })
        .catch(function() {
            msg.textContent = 'Error de conexion';
            msg.style.color = '#922B21';
        });
    }

    // ===== EVENTOS PRINCIPALES =====
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btnNuevoUsuario').addEventListener('click', function() { abrirModalUsuario(); });
        document.getElementById('btnNuevoTurno').addEventListener('click', function() { abrirModalTurno(); });
        document.getElementById('btnGuardarUsuario').addEventListener('click', guardarUsuario);
        document.getElementById('btnGuardarTurno').addEventListener('click', guardarTurno);
        document.getElementById('btnCalcularNomina').addEventListener('click', calcularNomina);
        document.getElementById('btnGuardarPerfil').addEventListener('click', guardarContraseña);

        cargarStats();
        cargarUsuarios();
        cargarTurnos();
        cargarGastos();
        cargarAlertas();
        cargarCuidadoresNomina();

        setInterval(function() {
            cargarStats();
            var activeTab = document.querySelector('.tab.active');
            if (activeTab) {
                var tab = activeTab.dataset.tab;
                if (tab === 'users') cargarUsuarios();
                else if (tab === 'shifts') cargarTurnos();
                else if (tab === 'expenses') cargarGastos();
                else if (tab === 'alerts') cargarAlertas();
            }
        }, 60000);
    });

})();