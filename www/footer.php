    </main>
    <footer style="background:var(--primary-dark);color:#fff;text-align:center;padding:16px;font-size:14px;margin-top:32px;display:flex;flex-direction:column;align-items:center;gap:8px;">
        <img src="img/MUNAY-removebg-preview.png" alt="MUNAY" style="height:30px; width:auto; filter:brightness(0) invert(1); display:block;">
        <span>&copy; <?php echo date('Y'); ?> Fundación MUNAY - Todos los derechos reservados.</span>
    </footer>

    <!-- MODAL DE PERFIL (compartido por todas las páginas - lógica en js/common.js) -->
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
</body>
</html>
