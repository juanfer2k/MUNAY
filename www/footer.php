</main>
    <footer style="background:#1a2634;color:#fff;text-align:center;padding:16px;font-size:14px;margin-top:auto;display:flex;flex-direction:column;align-items:center;gap:8px;">
        <img src="img/MUNAY-removebg-preview.png" alt="MUNAY" style="height:30px; width:auto; filter:brightness(0) invert(1); display:block;">
        <span>&copy; <?php echo date('Y'); ?> Fundación MUNAY <br> Todos los derechos reservados.</span>
    </footer>

    <!-- MODAL DE PERFIL (compartido por todas las páginas) -->
    <div class="modal-overlay" id="modalPerfil">
        <div class="modal">
            <div class="modal-header">
                <h2>Mi perfil</h2>
                <button class="modal-close" data-close="modalPerfil">&times;</button>
            </div>
            <div style="margin-bottom:14px;">
                <p><strong>Nombre:</strong> <span id="perfilNombre"></span></p>
                <p><strong>Correo:</strong> <span id="perfilEmail"></span></p>
                <p><strong>Rol:</strong> <span id="perfilRol"></span></p>
            </div>
            <hr style="margin:14px 0;border-color:var(--border);">
            <h3 style="font-size:15px;margin-bottom:10px;">Cambiar contraseña</h3>
            <div class="form-group">
                <label>Contraseña actual</label>
                <input type="password" id="passActual" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" id="passNueva" placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form-group">
                <label>Confirmar nueva</label>
                <input type="password" id="passConfirmar" placeholder="Repite la contraseña">
            </div>
            <div id="mensajePerfil" style="margin-top:10px;font-size:13px;"></div>
            <div class="modal-actions">
                <button class="btn btn-outline" data-close="modalPerfil">Cancelar</button>
                <button id="btnGuardarPerfil" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SCRIPTS GLOBALES                                             -->
    <!-- ============================================================ -->
    
    <!-- PRIMERO: common.js -->
<script src="js/common.js"></script>

<!-- DESPUÉS: dashboard.js y otros -->
<script src="js/dashboard.js"></script>

    <script>
    // ================================================================
    //  FUNCIONES GLOBALES
    // ================================================================

    function getUser() {
        try { return JSON.parse(localStorage.getItem('user') || '{}'); } catch { return {}; }
    }

    function cerrarModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    function abrirModal(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('active');
    }

    function logout() {
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }

    // ================================================================
    //  RENDERIZADO DEL MENÚ DE NAVEGACIÓN (renderNav)
    // ================================================================
    function renderNav(role) {
        var navContainer = document.getElementById('navLinks');
        if (!navContainer) return;

        var links = [];
        var iconMap = {
            dashboard: '<svg viewBox="0 0 24 24"><path d="M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM13 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z"/></svg>',
            shifts: '<svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>',
            expenses: '<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>',
            police: '<svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>',
            payroll: '<svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>'
        };

        if (role === 'admin') {
            links = [
                { href: 'dashboard.php', label: 'Dashboard', icon: iconMap.dashboard },
                { href: 'dashboard.php', label: 'Custodios', icon: iconMap.shifts },
                { href: 'police_view.php', label: 'Policía', icon: iconMap.police }
            ];
        } else if (role === 'police') {
            links = [
                { href: 'police_view.php', label: 'Mis Rutas', icon: iconMap.police },
                { href: 'dashboard.php', label: 'Custodios', icon: iconMap.shifts }
            ];
        } else if (role === 'coordinator') {
            links = [
                { href: 'dashboard.php', label: 'Dashboard', icon: iconMap.dashboard },
                { href: 'dashboard.php', label: 'Turnos', icon: iconMap.shifts },
                { href: 'dashboard.php', label: 'Custodios', icon: iconMap.police }
            ];
        } else if (role === 'nursing') {
            links = [
                { href: 'dashboard.php', label: 'Dashboard', icon: iconMap.dashboard },
                { href: 'dashboard.php', label: 'Visitas Médicas', icon: iconMap.expenses }
            ];
        } else {
            links = [
                { href: 'dashboard.php', label: 'Inicio', icon: iconMap.shifts }
            ];
        }

        var current = window.location.pathname.split('/').pop();
        navContainer.innerHTML = links.map(function(link) {
            var active = (link.href === current) ? 'active' : '';
            return '<a href="' + link.href + '" class="' + active + '">' +
                   link.icon +
                   '<span class="label">' + link.label + '</span>' +
                   '</a>';
        }).join('');

        var toggle = document.getElementById('navToggle');
        if (toggle) {
            toggle.replaceWith(toggle.cloneNode(true));
            var newToggle = document.getElementById('navToggle');
            newToggle.addEventListener('click', function() {
                navContainer.classList.toggle('open');
                this.classList.toggle('active');
            });
        }
    }

    // ================================================================
    //  SISTEMA DE ALERTAS
    // ================================================================
    function showAlert(message, type, duration) {
        type = type || 'info';
        duration = duration || 4000;
        var oldAlerts = document.querySelectorAll('.global-alert');
        oldAlerts.forEach(function(el) { el.remove(); });

        var container = document.getElementById('alert-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alert-container';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;width:100%;pointer-events:none;';
            document.body.appendChild(container);
        }

        var colors = {
            info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460', label: 'INFO' },
            success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724', label: 'OK' },
            warning: { bg: '#fff3cd', border: '#ffeeba', text: '#856404', label: 'AVISO' },
            error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24', label: 'ERROR' }
        };
        var style = colors[type] || colors.info;

        var alert = document.createElement('div');
        alert.className = 'global-alert';
        alert.style.cssText = 'background:' + style.bg + ';border-left:5px solid ' + style.border + ';color:' + style.text + ';padding:14px 18px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.15);font-size:14px;font-family:Inter,sans-serif;display:flex;align-items:center;gap:12px;pointer-events:auto;animation:slideInRight 0.4s ease;transition:opacity 0.3s ease;width:100%;';
        alert.innerHTML = '<span style="font-weight:700;min-width:40px;background:' + style.border + ';padding:2px 8px;border-radius:4px;font-size:11px;">' + style.label + '</span><span style="flex:1;">' + message + '</span><button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:18px;cursor:pointer;color:' + style.text + ';opacity:0.6;padding:0 4px;">×</button>';
        container.appendChild(alert);

        setTimeout(function() {
            if (alert.parentElement) {
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 300);
            }
        }, duration);
    }

    // ================================================================
    //  PERFIL DE USUARIO
    // ================================================================
    function openProfileModal() {
        var u = getUser();
        document.getElementById('perfilNombre').textContent = u.name || 'N/A';
        document.getElementById('perfilEmail').textContent = u.email || 'N/A';
        document.getElementById('perfilRol').textContent = u.role || 'N/A';
        document.getElementById('passActual').value = '';
        document.getElementById('passNueva').value = '';
        document.getElementById('passConfirmar').value = '';
        document.getElementById('mensajePerfil').textContent = '';
        abrirModal('modalPerfil');
    }

    function saveProfilePassword() {
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
            msg.textContent = 'Mínimo 6 caracteres';
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
            msg.textContent = 'Error de conexión';
            msg.style.color = '#922B21';
        });
    }

    // ================================================================
    //  MODO NOCHE
    // ================================================================
    function initNightMode() {
        var toggle = document.getElementById('nightModeToggle');
        if (!toggle) return;

        if (localStorage.getItem('nightMode') === 'enabled') {
            document.body.classList.add('night-mode');
            toggle.innerHTML = '<span class="icon">☀️</span> <span class="label">Día</span>';
        }

        toggle.addEventListener('click', function() {
            document.body.classList.toggle('night-mode');
            var isNight = document.body.classList.contains('night-mode');
            localStorage.setItem('nightMode', isNight ? 'enabled' : 'disabled');
            this.innerHTML = isNight ? '<span class="icon">☀️</span> <span class="label">Día</span>' : '<span class="icon">🌙</span> <span class="label">Noche</span>';
            if (typeof window.toggleMapLayer === 'function') {
                window.toggleMapLayer();
            }
        });
    }

    // ================================================================
    //  INICIALIZACIÓN
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        var user = getUser();
        document.body.classList.add('loaded');

        if (user.name) {
            var nameEl = document.getElementById('userName');
            var roleEl = document.getElementById('userRole');
            if (nameEl) nameEl.textContent = user.name;
            if (roleEl) roleEl.textContent = user.role || 'usuario';
            renderNav(user.role);
        } else {
            window.location.href = 'login.html';
        }

        var logoutBtn = document.getElementById('btnLogout');
        if (logoutBtn) logoutBtn.addEventListener('click', logout);

        var perfilBtn = document.getElementById('btnPerfil');
        if (perfilBtn) perfilBtn.addEventListener('click', openProfileModal);

        document.querySelectorAll('[data-close]').forEach(function(b) {
            b.addEventListener('click', function() {
                cerrarModal(this.dataset.close);
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(function(m) {
            m.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        var guardarPerfilBtn = document.getElementById('btnGuardarPerfil');
        if (guardarPerfilBtn) guardarPerfilBtn.addEventListener('click', saveProfilePassword);

        initNightMode();

        setInterval(function() {
            fetch('api/alerts.php?unread=true')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(a) {
                            showAlert(a.message, a.type || 'info');
                        });
                    }
                })
                .catch(function() {});
        }, 30000);
    });

    if (!document.getElementById('alert-styles')) {
        var styleSheet = document.createElement('style');
        styleSheet.id = 'alert-styles';
        styleSheet.textContent = '@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
        document.head.appendChild(styleSheet);
    }

    window.cerrarModal = cerrarModal;
    window.abrirModal = abrirModal;
    window.logout = logout;
    window.showAlert = showAlert;
    window.openProfileModal = openProfileModal;
    window.saveProfilePassword = saveProfilePassword;
    window.getUser = getUser;
    window.renderNav = renderNav;
    </script>
</body>
</html>