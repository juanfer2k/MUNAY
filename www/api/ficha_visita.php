<?php
// api/ficha_visita.php - Ficha de ruta (solo para usuarios autenticados)
session_start();

// ============================================================
// 1. VERIFICAR AUTENTICACIÓN
// ============================================================
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, redirige al login
    header('Location: login.html');
    exit;
}

$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token inválido');
}

// Decodificar token
$decoded = base64_decode($token);
if (!$decoded) {
    die('Token inválido o corrupto');
}

$datos = null;
$json_data = json_decode($decoded, true);

if ($json_data && isset($json_data['visita_id'])) {
    $datos = $json_data;
} else {
    $parts = explode('|', $decoded);
    if (count($parts) >= 1) {
        $visita_id = $parts[0];
        require_once 'config.php';
        $pdo = getDB();

        $stmt = $pdo->prepare("
            SELECT vm.id, vm.visit_date, vm.visit_time, vm.location, vm.motivo, vm.status,
                   m.nombre as paciente_nombre, m.apellido as paciente_apellido, m.documento,
                   c.name as custodio_nombre
            FROM visitas_medicas vm
            LEFT JOIN menores m ON vm.menor_id = m.id
            LEFT JOIN caregivers c ON vm.custodio_id = c.id
            WHERE vm.id = ?
        ");
        $stmt->execute([$visita_id]);
        $visita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$visita) {
            die('Visita no encontrada');
        }

        $datos = [
            'visita_id' => $visita['id'],
            'paciente' => trim($visita['paciente_nombre'] . ' ' . $visita['paciente_apellido']),
            'destino' => $visita['location'] ?? 'No especificado',
            'formador' => $visita['custodio_nombre'] ?? 'No asignado',
            'fecha' => date('Y-m-d H:i', strtotime($visita['visit_date'] . ' ' . $visita['visit_time'])),
            'valida_hasta' => date('Y-m-d H:i', strtotime($visita['visit_date'] . ' ' . $visita['visit_time'] . ' +4 hours')),
            'motivo' => $visita['motivo'] ?? '',
            'status' => $visita['status'] ?? 'confirmed'
        ];
    }
}

if (!$datos) {
    die('Token inválido o expirado');
}

$page_title = 'Ficha de Ruta - MUNAY';
include __DIR__ . '/../header.php';
?>

<style>
/* ===== FICHA COMPACTA SIN VERDE ===== */
.ficha-container {
    max-width: 500px;
    margin: 20px auto;
    padding: 16px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border-top: 3px solid #2c3e50; /* gris oscuro en lugar de verde */
}
.ficha-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 16px;
}
.ficha-header .icono {
    width: 36px;
    height: 36px;
    background: #2c3e50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    flex-shrink: 0;
}
.ficha-header h2 {
    margin: 0;
    font-size: 18px;
    color: #1a2634;
}
.ficha-header .subtitle {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}
.ficha-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 6px 0;
}
.ficha-item {
    padding: 6px 0;
    border-bottom: 1px solid #f1f3f5;
}
.ficha-item:last-child {
    border-bottom: none;
}
.ficha-item .label {
    font-size: 11px;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 600;
    letter-spacing: 0.3px;
    display: block;
    margin-bottom: 2px;
}
.ficha-item .value {
    font-size: 15px;
    font-weight: 500;
    color: #1a2634;
}
.ficha-item .value .badge-valid {
    background: #28a745;
    color: #fff;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.ficha-item .value .badge-status {
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.badge-confirmed { background: #007bff; color: #fff; }
.badge-completed { background: #28a745; color: #fff; }
.badge-cancelled { background: #dc3545; color: #fff; }
.ficha-footer {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #e9ecef;
    text-align: center;
    font-size: 11px;
    color: #6c757d;
}
.ficha-footer .qr-hint {
    display: inline-block;
    background: #f8f9fa;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    color: #495057;
}
.ficha-footer .qr-hint strong {
    color: #1a2634;
}
@media (max-width: 480px) {
    .ficha-container { margin: 10px; padding: 12px; }
    .ficha-header h2 { font-size: 16px; }
    .ficha-item .value { font-size: 14px; }
}
</style>

<div class="ficha-container">
    <div class="ficha-header">
        <div class="icono">🛡️</div>
        <div>
            <h2>Ficha de Ruta</h2>
            <p class="subtitle">Documento de acompañamiento (solo para personal autorizado)</p>
        </div>
    </div>
    <div class="ficha-grid">
        <div class="ficha-item">
            <span class="label">Paciente</span>
            <span class="value"><?php echo htmlspecialchars($datos['paciente'] ?? 'N/A'); ?></span>
        </div>
        <div class="ficha-item">
            <span class="label">Destino</span>
            <span class="value"><?php echo htmlspecialchars($datos['destino'] ?? 'N/A'); ?></span>
        </div>
        <div class="ficha-item">
            <span class="label">Formador acompañante</span>
            <span class="value"><?php echo htmlspecialchars($datos['formador'] ?? 'N/A'); ?></span>
        </div>
        <div class="ficha-item">
            <span class="label">Fecha y Hora</span>
            <span class="value"><?php echo htmlspecialchars($datos['fecha'] ?? 'N/A'); ?></span>
        </div>
        <div class="ficha-item">
            <span class="label">Válida hasta</span>
            <span class="value"><span class="badge-valid"><?php echo htmlspecialchars($datos['valida_hasta'] ?? 'N/A'); ?></span></span>
        </div>
        <?php if (!empty($datos['motivo'])): ?>
        <div class="ficha-item">
            <span class="label">Motivo</span>
            <span class="value"><?php echo htmlspecialchars($datos['motivo']); ?></span>
        </div>
        <?php endif; ?>
        <div class="ficha-item">
            <span class="label">Estado</span>
            <span class="value"><span class="badge-status badge-<?php echo $datos['status'] ?? 'confirmed'; ?>"><?php echo $datos['status'] ?? 'Confirmada'; ?></span></span>
        </div>
    </div>
    <div class="ficha-footer">
        <span class="qr-hint">
            📱 Documento válido con el código QR original.<br>
            <strong>MUNAY</strong> · Sistema de acompañamiento
        </span>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>