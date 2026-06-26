// map.js - Funciones de mapa con modo noche

let map;
let userMarker;
let nightMode = false;

function initMap(containerId = 'map', center = [4.6097, -74.0817], zoom = 13) {
    // Capas base
    const dayLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    });

    const nightLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap, © CartoDB'
    });

    map = L.map(containerId, {
        center: center,
        zoom: zoom,
        layers: [dayLayer]
    });

    // Control de capas
    const baseMaps = {
        'Dia': dayLayer,
        'Noche': nightLayer
    };

    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);

    // Botón de toggle rápido
    const toggleBtn = document.createElement('button');
    toggleBtn.innerHTML = '🌙';
    toggleBtn.style.cssText = `
        position: absolute; bottom: 30px; right: 10px; z-index: 1000;
        background: #fff; border: 1px solid #ccc; border-radius: 4px;
        padding: 8px 12px; cursor: pointer; font-size: 18px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    `;
    toggleBtn.title = 'Alternar modo noche/día';
    toggleBtn.onclick = function() {
        const current = map.hasLayer(nightLayer);
        if (current) {
            map.removeLayer(nightLayer);
            map.addLayer(dayLayer);
            this.innerHTML = '🌙';
        } else {
            map.removeLayer(dayLayer);
            map.addLayer(nightLayer);
            this.innerHTML = '☀️';
        }
    };
    document.getElementById(containerId).appendChild(toggleBtn);

    return map;
}

function updateUserLocation(lat, lng, label = 'Tu ubicación') {
    if (!map) return;
    if (userMarker) map.removeLayer(userMarker);

    userMarker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:#2E86C1; width:20px; height:20px; border-radius:50%; border:3px solid white; box-shadow:0 2px 10px rgba(0,0,0,0.3);"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        })
    }).addTo(map).bindPopup(label);
}