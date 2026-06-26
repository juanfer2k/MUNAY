<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'police') {
    header('Location: login.html');
    exit;
}
require_once 'api/config.php';
$page_title = 'Panel Policía';
include 'header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Mis Rutas del Dia</div></div>
        <div class="grid-4" style="margin-bottom:16px;">
            <div style="text-align:center;padding:8px;background:#F4F6F7;border-radius:8px;"><div style="font-size:24px;font-weight:bold;color:#2E86C1" id="totalRoutes">0</div><div style="font-size:11px;color:#5D6D7E;">Total</div></div>
            <div style="text-align:center;padding:8px;background:#F4F6F7;border-radius:8px;"><div style="font-size:24px;font-weight:bold;color:#B7950B" id="pendingRoutes">0</div><div style="font-size:11px;color:#5D6D7E;">Pendientes</div></div>
            <div style="text-align:center;padding:8px;background:#F4F6F7;border-radius:8px;"><div style="font-size:24px;font-weight:bold;color:#2E86C1" id="transitRoutes">0</div><div style="font-size:11px;color:#5D6D7E;">En Transito</div></div>
            <div style="text-align:center;padding:8px;background:#F4F6F7;border-radius:8px;"><div style="font-size:24px;font-weight:bold;color:#1E8449" id="completedRoutes">0</div><div style="font-size:11px;color:#5D6D7E;">Completadas</div></div>
        </div>
        <div id="routesList"><div class="empty-state">Cargando rutas...</div></div>
        <button onclick="loadRoutes()" class="btn btn-primary" style="margin-top:12px;">Actualizar</button>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Mapa de Ubicaciones</div></div>
        <div id="map" style="height:450px;border-radius:8px;overflow:hidden;border:1px solid #D5D8DC;"></div>
        <div style="font-size:11px;color:#5D6D7E;text-align:center;margin-top:8px;">Marcadores muestran ubicacion de cuidadores</div>
    </div>
</div>

<!-- Modal Perfil -->
<div class="modal-overlay" id="modalPerfil"><div class="modal"><div class="modal-header"><h2>Mi perfil</h2><button onclick="cerrarModal('modalPerfil')" class="modal-close">&times;</button></div><div style="margin-bottom:14px;"><p><strong>Nombre:</strong> <span id="perfilNombre"></span></p><p><strong>Correo:</strong> <span id="perfilEmail"></span></p><p><strong>Rol:</strong> <span id="perfilRol"></span></p></div><hr style="margin:14px 0;border-color:#D5D8DC"><h3 style="font-size:15px;margin-bottom:10px;">Cambiar contraseña</h3><div class="form-group"><label>Contraseña actual</label><input type="password" id="passActual" placeholder="••••••••"></div><div class="form-group"><label>Nueva contraseña</label><input type="password" id="passNueva" placeholder="Minimo 6 caracteres"></div><div class="form-group"><label>Confirmar nueva</label><input type="password" id="passConfirmar" placeholder="Repite la contraseña"></div><div id="mensajePerfil" style="margin-top:10px;font-size:13px;"></div><div class="modal-actions"><button onclick="cerrarModal('modalPerfil')" class="btn btn-outline">Cancelar</button><button onclick="guardarContraseña()" class="btn btn-primary">Guardar cambios</button></div></div></div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var mapInstance=null;
function initMap(){
    mapInstance=L.map('map').setView([4.6097,-74.0817],13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(mapInstance);
    loadLocations();setInterval(loadLocations,30000);
}
async function loadLocations(){try{const r=await fetch('api/police_locations.php');const d=await r.json();if(d.error)return;updateMarkers(d);}catch(e){}}
function updateMarkers(caregivers){if(!mapInstance)return;Object.keys(window._markers||{}).forEach(k=>mapInstance.removeLayer(window._markers[k]));window._markers={};caregivers.forEach(c=>{if(!c.lat||!c.lng)return;const color=c.group_type==='A'?'#2E86C1':c.group_type==='B'?'#B7950B':'#922B21';const icon=L.divIcon({className:'custom-div-icon',html:'<div style="background-color:'+color+';width:22px;height:22px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;font-size:9px;color:white;font-weight:bold;">'+c.group_type+'</div>',iconSize:[22,22],iconAnchor:[11,11]});const m=L.marker([c.lat,c.lng],{icon}).addTo(mapInstance).bindPopup('<div style="font-size:13px;"><strong>'+c.name+'</strong><br>'+(c.location||'Sin ubicacion')+'<br><small>'+(c.last_update?new Date(c.last_update).toLocaleTimeString():'')+'</small></div>');window._markers[c.caregiver_id]=m;});}
async function loadRoutes(){try{const r=await fetch('api/police_routes.php');const d=await r.json();if(d.error){document.getElementById('routesList').innerHTML='<div class="empty-state">Error al cargar</div>';return;}const total=d.length,pending=d.filter(function(x){return x.status==='pending';}).length,transit=d.filter(function(x){return x.status==='in_transit';}).length,completed=d.filter(function(x){return x.status==='completed';}).length;document.getElementById('totalRoutes').textContent=total;document.getElementById('pendingRoutes').textContent=pending;document.getElementById('transitRoutes').textContent=transit;document.getElementById('completedRoutes').textContent=completed;if(total===0){document.getElementById('routesList').innerHTML='<div class="empty-state">No hay rutas asignadas para hoy</div>';return;}document.getElementById('routesList').innerHTML=d.map(function(r){return '<div style="background:#F4F6F7;border-radius:8px;padding:14px;margin-bottom:10px;border-left:5px solid '+(r.status==='pending'?'#B7950B':r.status==='in_transit'?'#2E86C1':'#1E8449')+'"><div style="font-weight:bold;">'+r.caregiver_name+' <span class="badge badge-'+r.status+'">'+r.status+'</span></div><div style="font-size:13px;color:#5D6D7E;margin:4px 0;">Recoger: '+r.pickup_location+' - Dejar: '+(r.dropoff_location||'Pendiente')+'</div><div style="font-size:12px;color:#5D6D7E;">'+r.pickup_time+'</div><div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">'+(r.status==='pending'?'<button onclick="startRoute('+r.id+')" class="btn btn-primary btn-sm">Iniciar</button>':'')+(r.status==='in_transit'?'<button onclick="completeRoute('+r.id+')" class="btn btn-success btn-sm">Completar</button>':'')+(r.status!=='completed'&&r.status!=='cancelled'?'<button onclick="cancelRoute('+r.id+')" class="btn btn-danger btn-sm">Cancelar</button>':'')+'</div></div>';}).join('');}catch(e){}}
async function startRoute(id){if(!confirm('Iniciar ruta?'))return;try{await fetch('api/police_route.php',{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id,status:'in_transit'})});loadRoutes();}catch(e){}}
async function completeRoute(id){if(!confirm('Completar ruta?'))return;try{await fetch('api/police_route.php',{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id,status:'completed'})});loadRoutes();}catch(e){}}
async function cancelRoute(id){if(!confirm('Cancelar ruta?'))return;try{await fetch('api/police_route.php',{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id,status:'cancelled'})});loadRoutes();}catch(e){}}
function guardarContraseña(){var actual=document.getElementById('passActual').value,nueva=document.getElementById('passNueva').value,confirmar=document.getElementById('passConfirmar').value,msg=document.getElementById('mensajePerfil');if(!actual||!nueva||!confirmar){msg.textContent='Todos los campos son obligatorios';msg.style.color='#922B21';return;}if(nueva.length<6){msg.textContent='Minimo 6 caracteres';msg.style.color='#922B21';return;}if(nueva!==confirmar){msg.textContent='No coinciden';msg.style.color='#922B21';return;}var u=getUser();if(!u.id){msg.textContent='No se pudo obtener tu ID';return;}fetch('api/change_password.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:u.id,current_password:actual,new_password:nueva})}).then(function(r){return r.json();}).then(function(d){if(d.success){msg.textContent='Contraseña actualizada';msg.style.color='#1E8449';setTimeout(function(){cerrarModal('modalPerfil');},1500);}else{msg.textContent='Error: '+(d.error||'Desconocido');msg.style.color='#922B21';}}).catch(function(){msg.textContent='Error de conexion';msg.style.color='#922B21';});}
document.addEventListener('DOMContentLoaded',function(){initMap();loadRoutes();setInterval(loadRoutes,60000);});
</script>
<?php include 'footer.php'; ?>