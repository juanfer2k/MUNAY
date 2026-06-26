// auth.js - Lógica de autenticación y perfil

// ===== LOGIN =====
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('errorMsg');
            const successDiv = document.getElementById('successMsg');

            errorDiv.classList.remove('visible');
            successDiv.classList.remove('visible');

            try {
                const res = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();

                if (data.success) {
                    // Guardar datos del usuario
                    localStorage.setItem('user', JSON.stringify({
                        id: data.id,
                        name: data.name,
                        email: email,
                        role: data.role
                    }));

                    // Registrar token FCM si existe
                    if (typeof messaging !== 'undefined') {
                        try {
                            const token = await messaging.getToken({ vapidKey: "TU_VAPID_KEY" });
                            await fetch('api/register_device.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ token })
                            });
                        } catch(e) { console.warn('FCM no disponible'); }
                    }

                    // Redirigir según rol
                    if (data.role === 'admin') window.location.href = 'dashboard_admin.html';
                    else if (data.role === 'police') window.location.href = 'police_view.html';
                    else window.location.href = 'index.html';
                } else {
                    errorDiv.textContent = data.error || 'Credenciales inválidas';
                    errorDiv.classList.add('visible');
                }
            } catch (err) {
                errorDiv.textContent = 'Error de conexión. Verifica tu internet.';
                errorDiv.classList.add('visible');
            }
        });
    }

    // ===== RECUPERACIÓN DE CONTRASEÑA =====
    const showRecovery = document.getElementById('showRecovery');
    if (showRecovery) {
        showRecovery.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('recoveryForm').style.display = 'block';
        });
    }
});

// ===== FUNCIONES GLOBALES =====
function hideRecovery() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('recoveryForm').style.display = 'none';
    document.getElementById('recoveryMsg').classList.remove('visible');
}

async function sendRecovery() {
    const email = document.getElementById('recoveryEmail').value;
    const msgDiv = document.getElementById('recoveryMsg');

    if (!email) {
        msgDiv.textContent = 'Ingresa tu correo electrónico';
        msgDiv.classList.add('visible');
        return;
    }

    try {
        const res = await fetch('api/recover_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const data = await res.json();

        if (data.success) {
            msgDiv.className = 'success-msg visible';
            msgDiv.textContent = '✅ Se envió un enlace de recuperación a tu correo.';
        } else {
            msgDiv.className = 'error-msg visible';
            msgDiv.textContent = data.error || 'Error al enviar el enlace.';
        }
    } catch (err) {
        msgDiv.className = 'error-msg visible';
        msgDiv.textContent = 'Error de conexión.';
    }
}

// ===== PERFIL DE USUARIO =====
function getUser() {
    try {
        return JSON.parse(localStorage.getItem('user') || '{}');
    } catch { return {}; }
}

function updateUserInfo() {
    const user = getUser();
    const nameEl = document.getElementById('userName');
    const roleEl = document.getElementById('userRole');
    if (nameEl) nameEl.textContent = user.name || 'Usuario';
    if (roleEl) roleEl.textContent = user.role || 'sin rol';
}

// ===== CAMBIAR CONTRASEÑA =====
async function changePassword(currentPassword, newPassword) {
    const user = getUser();
    if (!user.id) { alert('Debes iniciar sesión'); return false; }

    try {
        const res = await fetch('api/change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: user.id,
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Contraseña actualizada correctamente');
            return true;
        } else {
            alert('❌ Error: ' + (data.error || 'No se pudo cambiar la contraseña'));
            return false;
        }
    } catch (err) {
        alert('❌ Error de conexión');
        return false;
    }
}

// ===== CERRAR SESIÓN =====
function logout() {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// Actualizar información del usuario al cargar
document.addEventListener('DOMContentLoaded', updateUserInfo);