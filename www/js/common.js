/**
 * js/common.js
 * ============================================================
 * Funciones compartidas por toda la app: sesión, modales,
 * menú de navegación, alertas globales y el modal de perfil.
 *
 * Antes esto vivía como un <script> inline dentro de footer.php,
 * y dashboard_admin.php definía SU PROPIA copia de cerrarModal()/
 * abrirModal() por separado (silenciosamente sobreescrita por esta
 * misma versión al incluirse footer.php después). Ahora hay una
 * sola definición, cargada una sola vez desde header.php.
 * ============================================================
 */

// DataTables, por defecto, muestra sus errores/warnings con un alert()
// nativo del navegador (bloquea toda la página hasta que el usuario
// presiona "Aceptar"). Lo reemplazamos por el toast no intrusivo que
// ya usa el resto de la app.
if (window.jQuery && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.errMode = 'none';
    $(document).on('error.dt', function (e, settings, techNote, message) {
        console.error('DataTables:', message);
    });
}

// ===== SESIÓN =====
function getUser() {
    try { return JSON.parse(localStorage.getItem('user') || '{}'); }
    catch (e) { return {}; }
}

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// ===== MODALES =====
function cerrarModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('active');
}
function abrirModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('active');
}

// ===== MENÚ DE NAVEGACIÓN (generado según el rol, sin íconos duplicados) =====
function renderNav(role) {
    var nav = document.getElementById('mainNav');
    if (!nav) return;

    var links;
    if (role === 'admin') {
        links = [
            { href: 'admin_panel.php',     label: 'Panel de Control', icon: 'dashboard' },
            { href: 'dashboard_admin.php', label: 'Dashboard',        icon: 'shifts'    },
            { href: 'index.php',           label: 'Cuidadores',       icon: 'users'     },
            { href: 'police_view.php',     label: 'Policía',          icon: 'police'    }
        ];
    } else if (role === 'police') {
        links = [
            { href: 'police_view.php', label: 'Mis Rutas', icon: 'police' }
        ];
    } else {
        // caregiver
        links = [
            { href: 'index.php', label: 'Mis Turnos', icon: 'shifts' }
        ];
    }

    var current = window.location.pathname.split('/').pop();
    nav.innerHTML = links.map(function (link) {
        var active = (link.href === current) ? 'active' : '';
        return '<a href="' + link.href + '" class="' + active + '">' +
               MunayIcon(link.icon, 18) +
               '<span class="label">' + link.label + '</span>' +
               '</a>';
    }).join('');
}

// ===== ALERTAS GLOBALES (toasts) =====
function showAlert(message, type, duration) {
    type = type || 'info';
    duration = duration || 4000;

    var container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;width:100%;pointer-events:none;';
        document.body.appendChild(container);
    }

    var styles = {
        info:    { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460', label: 'INFO' },
        success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724', label: 'OK' },
        warning: { bg: '#fff3cd', border: '#ffeeba', text: '#856404', label: 'AVISO' },
        error:   { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24', label: 'ERROR' }
    };
    var s = styles[type] || styles.info;

    var alertEl = document.createElement('div');
    alertEl.className = 'global-alert';
    alertEl.style.cssText =
        'background:' + s.bg + ';border-left:5px solid ' + s.border + ';color:' + s.text + ';' +
        'padding:14px 18px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.15);font-size:14px;' +
        "font-family:'Inter',sans-serif;display:flex;align-items:center;gap:12px;pointer-events:auto;" +
        'animation:slideInRight 0.4s ease;transition:opacity 0.3s ease;width:100%;';
    alertEl.innerHTML =
        '<span style="font-weight:700;min-width:40px;background:' + s.border + ';padding:2px 8px;border-radius:4px;font-size:11px;">' + s.label + '</span>' +
        '<span style="flex:1;">' + message + '</span>' +
        '<button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:18px;cursor:pointer;color:' + s.text + ';opacity:0.6;padding:0 4px;">&times;</button>';

    container.appendChild(alertEl);

    setTimeout(function () {
        if (alertEl.parentElement) {
            alertEl.style.opacity = '0';
            setTimeout(function () { alertEl.remove(); }, 300);
        }
    }, duration);
}

if (!document.getElementById('alert-styles')) {
    var styleSheet = document.createElement('style');
    styleSheet.id = 'alert-styles';
    styleSheet.textContent = '@keyframes slideInRight{from{transform:translateX(100%);opacity:0;}to{transform:translateX(0);opacity:1;}}';
    document.head.appendChild(styleSheet);
}

// ===== PERFIL DE USUARIO (modal compartido, definido una sola vez en footer.php) =====
function openProfileModal() {
    var u = getUser();
    var elNombre = document.getElementById('perfilNombre');
    var elEmail = document.getElementById('perfilEmail');
    var elRol = document.getElementById('perfilRol');
    if (elNombre) elNombre.textContent = u.name || 'N/A';
    if (elEmail) elEmail.textContent = u.email || 'N/A';
    if (elRol) elRol.textContent = u.role || 'N/A';
    ['passActual', 'passNueva', 'passConfirmar'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    var msg = document.getElementById('mensajePerfil');
    if (msg) msg.textContent = '';
    abrirModal('modalPerfil');
}

function saveProfilePassword() {
    var actual = document.getElementById('passActual').value;
    var nueva = document.getElementById('passNueva').value;
    var confirmar = document.getElementById('passConfirmar').value;
    var msg = document.getElementById('mensajePerfil');

    function setMsg(text, ok) {
        msg.textContent = text;
        msg.style.color = ok ? '#1E8449' : '#922B21';
    }

    if (!actual || !nueva || !confirmar) { setMsg('Todos los campos son obligatorios', false); return; }
    if (nueva.length < 6) { setMsg('Mínimo 6 caracteres', false); return; }
    if (nueva !== confirmar) { setMsg('No coinciden', false); return; }

    var u = getUser();
    if (!u.id) { setMsg('No se pudo obtener tu ID', false); return; }

    var btn = document.getElementById('btnGuardarPerfil');
    if (btn) btn.disabled = true;

    fetch('api/change_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: u.id, current_password: actual, new_password: nueva })
    })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                setMsg('Contraseña actualizada', true);
                showAlert('Contraseña actualizada correctamente.', 'success');
                setTimeout(function () { cerrarModal('modalPerfil'); }, 1200);
            } else {
                setMsg(d.error || 'Error desconocido', false);
            }
        })
        .catch(function () { setMsg('Error de conexión', false); })
        .finally(function () { if (btn) btn.disabled = false; });
}

// ===== EVENTOS GLOBALES =====
document.addEventListener('DOMContentLoaded', function () {
    var user = getUser();

    if (user.name) {
        var nameEl = document.getElementById('userName');
        var roleEl = document.getElementById('userRole');
        if (nameEl) nameEl.textContent = user.name;
        if (roleEl) roleEl.textContent = user.role || 'usuario';
        renderNav(user.role);
    } else {
        window.location.href = 'login.html';
        return;
    }

    var logoutBtn = document.getElementById('btnLogout');
    if (logoutBtn) logoutBtn.addEventListener('click', logout);

    var perfilBtn = document.getElementById('btnPerfil');
    if (perfilBtn) perfilBtn.addEventListener('click', openProfileModal);

    document.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () { cerrarModal(this.dataset.close); });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (m) {
        m.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    var guardarPerfilBtn = document.getElementById('btnGuardarPerfil');
    if (guardarPerfilBtn) guardarPerfilBtn.addEventListener('click', saveProfilePassword);

    // Sondeo de alertas push no leídas
    setInterval(function () {
        fetch('api/alerts.php?unread=true')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.length > 0) {
                    data.forEach(function (a) { showAlert(a.message, a.type || 'info'); });
                }
            })
            .catch(function () {});
    }, 30000);
});

// Exponer funciones globalmente para los onclick="" en el HTML
window.getUser = getUser;
window.logout = logout;
window.cerrarModal = cerrarModal;
window.abrirModal = abrirModal;
window.renderNav = renderNav;
window.showAlert = showAlert;
window.openProfileModal = openProfileModal;
window.saveProfilePassword = saveProfilePassword;