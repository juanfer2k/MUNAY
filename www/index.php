<?php
session_start();
require_once 'api/config.php';
$page_title = 'Mis Turnos';
include 'header.php';
?>

<!-- ===== ESTILOS ADICIONALES PARA INDEX ===== -->
<style>
    /* ===== GRID RESPONSIVO ===== */
    .caregiver-dashboard {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    @media (max-width: 768px) {
        .caregiver-dashboard {
            grid-template-columns: 1fr;
        }
    }

    /* ===== TARJETA DE TURNO ===== */
    .shift-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border-left: 5px solid #2E86C1;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .shift-card .time {
        font-weight: 600;
        font-size: 16px;
        color: #0b2a3e;
    }
    .shift-card .meta {
        font-size: 14px;
        color: #5d6d7e;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .shift-card .actions {
        margin-top: 8px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .shift-card .actions .btn {
        font-size: 13px;
        padding: 6px 16px;
    }

    /* ===== MAPA ===== */
    .map-wrapper {
        height: 380px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e0e6ed;
        position: relative;
        background: #e8ecf1;
    }
    #map {
        height: 100%;
        width: 100%;
    }

    /* ===== BOTONES DE ACCIÓN ===== */
    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
    }
    .action-buttons .btn {
        flex: 1;
        min-width: 140px;
        justify-content: center;
    }

    /* ===== ALERTAS ===== */
    .alert-banner {
        background: #fef9e7;
        border-left: 5px solid #f1c40f;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
        color: #7d6608;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .alert-banner .close-alert {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #7d6608;
    }

    /* ===== ESTADO VACÍO ===== */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #95a5a6;
    }
    .empty-state svg {
        width: 48px;
        height: 48px;
        fill: #d5d8dc;
        margin-bottom: 12px;
    }
</style>

<!-- ===== CONTENIDO PRINCIPAL ===== -->
<div class="caregiver-dashboard">

    <!-- COLUMNA IZQUIERDA: TURNOS -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Mis Turnos de Hoy</span>
            <button onclick="cargarTurnos()" class="btn btn-primary btn-sm" style="padding:4px 12px;font-size:12px;">
                Actualizar
            </button>
        </div>

        <!-- Contenedor de alertas -->
        <div id="alertContainer"></div>

        <!-- Lista de turnos -->
        <div id="shiftsList">
            <div class="empty-state">
                <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
                <p>Cargando turnos...</p>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="action-buttons">
            <button onclick="abrirModalGasto()" class="btn btn-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
                Agregar Gasto
            </button>
            <button onclick="abrirModalCambio()" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;"><path d="M12 6v2h-4V6h4zm0 4v2H8v-2h4zm0 4v2H8v-2h4zm-4 4h8v-2H8v2zm10-12h-3V4h-2v2H8V4H6v2H5c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V7c0-.55-.45-1-1-1z"/></svg>
                Solicitar Cambio
            </button>
        </div>
    </div>

    <!-- COLUMNA DERECHA: MAPA -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Mi Ubicación</span>
            <button onclick="actualizarUbicacion()" class="btn btn-primary btn-sm" style="padding:4px 12px;font-size:12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Actualizar
            </button>
        </div>
        <div class="map-wrapper" id="mapContainer">
            <div id="map"></div>
        </div>
        <div style="font-size:12px;color:#95a5a6;text-align:center;margin-top:10px;">
            Tu ubicación se actualiza automáticamente cada 30 segundos.
        </div>
    </div>
</div>

<!-- ===== MODAL: AGREGAR GASTO ===== -->
<div class="modal-overlay" id="modalGasto">
    <div class="modal">
        <div class="modal-header">
            <h2>Registrar Gasto / Viático</h2>
            <button class="modal-close" data-close="modalGasto">&times;</button>
        </div>
        <form id="formGasto">
            <div class="form-group">
                <label>Tipo de gasto</label>
                <select id="gastoTipo" required>
                    <option value="parking">Parqueadero</option>
                    <option value="transport">Transporte</option>
                    <option value="food">Alimentación</option>
                    <option value="toll">Peaje</option>
                    <option value="other">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto ($)</label>
                <input type="number" id="gastoMonto" placeholder="0.00" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Descripción (opcional)</label>
                <textarea id="gastoDescripcion" rows="2" placeholder="Ej: Taxi a la clínica"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-close="modalGasto">Cancelar</button>
                <button type="submit" class="btn btn-success">Guardar Gasto</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL: SOLICITAR CAMBIO DE TURNO ===== -->
<div class="modal-overlay" id="modalCambio">
    <div class="modal">
        <div class="modal-header">
            <h2>Solicitar Cambio de Turno</h2>
            <button class="modal-close" data-close="modalCambio">&times;</button>
        </div>
        <p style="font-size:13px;color:#5d6d7e;margin-bottom:16px;">Selecciona la nueva fecha y hora deseada. El administrador revisará tu solicitud.</p>
        <form id="formCambio">
            <input type="hidden" id="cambioShiftId" value="">
            <div class="form-group">
                <label>Turno actual</label>
                <input type="text" id="cambioTurnoActual" readonly style="background:#ecf0f1;">
            </div>
            <div class="form-group">
                <label>Fecha deseada</label>
                <input type="date" id="cambioFecha" required>
            </div>
            <div class="form-group">
                <label>Hora deseada</label>
                <input type="time" id="cambioHora" required>
            </div>
            <div class="form-group">
                <label>Motivo</label>
                <textarea id="cambioMotivo" rows="2" placeholder="Explica por qué necesitas el cambio..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" data-close="modalCambio">Cancelar</button>
                <button type="submit" class="btn btn-primary">Enviar Solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function() {
    'use strict';

    // ===== VARIABLES GLOBALES =====
    var mapInstance = null;
    var userMarker = null;
    var watchId = null;
    var currentUser = getUser();

    // ===== FUNCIONES DE UTILIDAD (definidas localmente) =====
    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    function abrirModal(id) {
        document.getElementById(id).classList.add('active');
    }

    // ===== MAPA =====
    function initMap() {
        var container = document.getElementById('map');
        if (!container) return;

        var dayLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        });
        var nightLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap, © CartoDB'
        });

        mapInstance = L.map('map', {
            center: [4.6097, -74.0817],
            zoom: 14,
            layers: [dayLayer]
        });

        var baseMaps = { 'Día': dayLayer, 'Noche': nightLayer };
        L.control.layers(baseMaps, null, { position: 'topright' }).addTo(mapInstance);

        // Botón de toggle rápido
        var toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '🌙';
        toggleBtn.style.cssText = 'position:absolute;bottom:20px;right:10px;z-index:1000;background:#fff;border:1px solid #ccc;border-radius:4px;padding:6px 10px;cursor:pointer;font-size:16px;box-shadow:0 2px 6px rgba(0,0,0,0.2)';
        toggleBtn.title = 'Alternar modo noche/día';
        toggleBtn.onclick = function() {
            var current = mapInstance.hasLayer(nightLayer);
            if (current) {
                mapInstance.removeLayer(nightLayer);
                mapInstance.addLayer(dayLayer);
                this.innerHTML = '🌙';
            } else {
                mapInstance.removeLayer(dayLayer);
                mapInstance.addLayer(nightLayer);
                this.innerHTML = '☀️';
            }
        };
        document.getElementById('mapContainer').appendChild(toggleBtn);

        setTimeout(function() {
            if (mapInstance) mapInstance.invalidateSize();
        }, 300);

        // Geolocalización
        if (navigator.geolocation) {
            watchId = navigator.geolocation.watchPosition(
                function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    mapInstance.setView([lat, lng], 15);
                    actualizarMarcador(lat, lng);
                    enviarUbicacion(lat, lng);
                },
                function(err) { console.warn('GPS error:', err.message); },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }

        window.addEventListener('resize', function() {
            if (mapInstance) {
                setTimeout(function() { mapInstance.invalidateSize(); }, 200);
            }
        });
    }

    function actualizarMarcador(lat, lng) {
        if (!mapInstance) return;
        if (userMarker) mapInstance.removeLayer(userMarker);

        userMarker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color:#2E86C1;width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            })
        }).addTo(mapInstance).bindPopup('Tu ubicación actual');
    }

    function enviarUbicacion(lat, lng) {
        if (!currentUser || !currentUser.id) return;
        fetch('api/update_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                caregiver_id: currentUser.id,
                lat: lat,
                lng: lng
            })
        }).catch(function(e) { console.warn('Error enviando ubicación:', e); });
    }

    function actualizarUbicacion() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 15);
                    actualizarMarcador(pos.coords.latitude, pos.coords.longitude);
                    enviarUbicacion(pos.coords.latitude, pos.coords.longitude);
                },
                function() { alert('No se pudo obtener la ubicación. Verifica permisos.'); },
                { enableHighAccuracy: true }
            );
        }
    }

    // ===== CARGAR TURNOS =====
    function cargarTurnos() {
        var container = document.getElementById('shiftsList');
        var alertContainer = document.getElementById('alertContainer');

        // Limpiar alertas anteriores
        alertContainer.innerHTML = '';
        container.innerHTML = '<div class="empty-state"><p>Cargando...</p></div>';

        fetch('api/my_shifts.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                // Mostrar alertas con botón de cierre
                if (data.alerts && data.alerts.length) {
                    alertContainer.innerHTML = data.alerts.map(function(a) {
                        return '<div class="alert-banner">' +
                            '⚠️ ' + a.message +
                            '<button class="close-alert" onclick="this.parentElement.remove()">×</button>' +
                            '</div>';
                    }).join('');
                }

                // Mostrar turnos
                if (!data.shifts || data.shifts.length === 0) {
                    container.innerHTML = '<div class="empty-state">' +
                        '<svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>' +
                        '<p>No tienes turnos asignados para hoy.</p>' +
                        '<p style="font-size:12px;color:#95a5a6;">Disfruta tu descanso</p>' +
                        '</div>';
                    return;
                }

                var html = '';
                data.shifts.forEach(function(s) {
                    var statusClass = s.status || 'pending';
                    var statusLabel = statusClass.replace('_', ' ').toUpperCase();
                    html += '<div class="shift-card">';
                    html += '<div class="time">' + s.start_time + ' - ' + s.end_time + '</div>';
                    html += '<div class="meta">';
                    html += '<span>' + s.shift_date + ' · <span class="badge badge-' + statusClass + '">' + statusLabel + '</span></span>';
                    html += '<span>' + (s.location || 'Pendiente') + '</span>';
                    if (s.minor_id) html += '<span>👤 Menor: ' + s.minor_id + '</span>';
                    html += '</div>';
                    html += '<div class="actions">';
                    if (s.status === 'pending') {
                        html += '<button class="btn btn-primary btn-sm" onclick="abrirModalCambio(' + s.id + ', \'' + s.start_time + ' - ' + s.end_time + '\')">Solicitar Cambio</button>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                container.innerHTML = html;

            })
            .catch(function(error) {
                container.innerHTML = '<div class="empty-state"><p style="color:#e74c3c;">Error al cargar turnos. Reintenta.</p></div>';
                console.error('Error cargando turnos:', error);
            });
    }

    // ===== GASTOS =====
    function abrirModalGasto() {
        document.getElementById('gastoMonto').value = '';
        document.getElementById('gastoDescripcion').value = '';
        document.getElementById('gastoTipo').value = 'parking';
        abrirModal('modalGasto');
    }

    document.getElementById('formGasto').addEventListener('submit', function(e) {
        e.preventDefault();
        var tipo = document.getElementById('gastoTipo').value;
        var monto = parseFloat(document.getElementById('gastoMonto').value);
        var descripcion = document.getElementById('gastoDescripcion').value.trim() || tipo;

        if (!monto || monto <= 0) {
            alert('Ingresa un monto válido.');
            return;
        }

        fetch('api/report_expense.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: tipo,
                amount: monto,
                description: descripcion
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                alert('✅ Gasto reportado correctamente.');
                cerrarModal('modalGasto');
            } else {
                alert('❌ Error: ' + (d.error || 'No se pudo guardar el gasto.'));
            }
        })
        .catch(function() {
            alert('❌ Error de conexión.');
        });
    });

    // ===== SOLICITAR CAMBIO =====
    function abrirModalCambio(shiftId, turnoActual) {
        document.getElementById('cambioShiftId').value = shiftId || '';
        document.getElementById('cambioTurnoActual').value = turnoActual || 'Turno actual';
        document.getElementById('cambioFecha').value = '';
        document.getElementById('cambioHora').value = '';
        document.getElementById('cambioMotivo').value = '';
        abrirModal('modalCambio');
    }

    document.getElementById('formCambio').addEventListener('submit', function(e) {
        e.preventDefault();
        var shiftId = document.getElementById('cambioShiftId').value;
        var fecha = document.getElementById('cambioFecha').value;
        var hora = document.getElementById('cambioHora').value;
        var motivo = document.getElementById('cambioMotivo').value.trim() || 'Sin especificar';

        if (!shiftId || !fecha || !hora) {
            alert('Selecciona una fecha y hora deseada.');
            return;
        }

        fetch('api/request_shift_change.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                shift_id: shiftId,
                requested_date: fecha,
                requested_time: hora,
                reason: motivo
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                alert('✅ Solicitud enviada correctamente. Espera la aprobación del administrador.');
                cerrarModal('modalCambio');
                cargarTurnos(); // refrescar
            } else {
                alert('❌ Error: ' + (d.error || 'No se pudo enviar la solicitud.'));
            }
        })
        .catch(function() {
            alert('❌ Error de conexión.');
        });
    });

    // ===== INICIALIZACIÓN =====
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar nombre del usuario en el header
        if (currentUser && currentUser.name) {
            document.getElementById('userName').textContent = currentUser.name;
            document.getElementById('userRole').textContent = currentUser.role || 'cuidador';
        }

        initMap();
        cargarTurnos();

        // Refrescar turnos cada 60 segundos
        setInterval(cargarTurnos, 60000);
    });

    // Limpiar watch de GPS al salir
    window.addEventListener('beforeunload', function() {
        if (watchId) navigator.geolocation.clearWatch(watchId);
    });

    // Exponer funciones globalmente para los botones HTML
    window.cargarTurnos = cargarTurnos;
    window.actualizarUbicacion = actualizarUbicacion;
    window.abrirModalGasto = abrirModalGasto;
    window.abrirModalCambio = abrirModalCambio;
    window.cerrarModal = cerrarModal;
    window.abrirModal = abrirModal;

})();
</script>

<?php include 'footer.php'; ?>