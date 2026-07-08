<?php
// ficha_visita.php - Ficha pública de la visita médica, accedida vía QR (sin login).
// Vence unas horas después de la hora de la cita, para no exponer datos indefinidamente.
require_once 'config.php';

// Horas de vigencia del enlace después de iniciada la cita.
const HORAS_VIGENCIA = 6;

header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';

function paginaError($mensaje) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha no disponible</title>
    <style>body{font-family:system-ui,sans-serif;background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;text-align:center;}
    .box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:400px;}
    h1{color:#e74c3c;font-size:20px;}p{color:#5d6d7e;}</style></head>
    <body><div class="box"><h1>⚠️ Ficha no disponible</h1><p>' . htmlspecialchars($mensaje) . '</p></div></body></html>';
    exit;
}

if (empty($token)) {
    paginaError('Enlace inválido.');
}

$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT v.visit_date, v.visit_time, v.location, v.motivo, v.status, v.attachment, v.tipo,
           m.documento, m.tipo_doc, m.nombre, m.apellido, m.fecha_nacimiento,
           cu.name AS custodio_name, cu.phone AS custodio_phone
    FROM visitas_medicas v
    LEFT JOIN menores m ON v.menor_id = m.id
    LEFT JOIN caregivers cu ON v.custodio_id = cu.id
    WHERE v.qr_token = ?
");
$stmt->execute([$token]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$v) {
    paginaError('No se encontró ninguna visita asociada a este código.');
}

if ($v['status'] === 'cancelled') {
    paginaError('Esta visita médica fue cancelada.');
}

// Vigencia: desde el inicio de la cita hasta HORAS_VIGENCIA después.
try {
    $tz = new DateTimeZone('America/Bogota');
    $inicioCita = new DateTime($v['visit_date'] . ' ' . $v['visit_time'], $tz);
    $vence = (clone $inicioCita)->modify('+' . HORAS_VIGENCIA . ' hours');
    $ahora = new DateTime('now', $tz);
    if ($ahora > $vence) {
        paginaError('Este enlace ya venció. Contacta a la coordinación si necesitas la información.');
    }
} catch (Exception $e) {
    paginaError('Error al validar la vigencia del enlace.');
}

$nombreCompleto = htmlspecialchars(trim($v['nombre'] . ' ' . $v['apellido']));
$documento = htmlspecialchars(($v['tipo_doc'] ?? '') . ' ' . ($v['documento'] ?? 'N/A'));
$fecha = htmlspecialchars($v['visit_date']);
$hora = htmlspecialchars(substr($v['visit_time'], 0, 5));
$lugar = htmlspecialchars($v['location'] ?: 'No especificado');
$motivo = htmlspecialchars($v['motivo'] ?: 'No especificado');
$custodioNombre = htmlspecialchars($v['custodio_name'] ?: 'No asignado');
$custodioTelefono = htmlspecialchars($v['custodio_phone'] ?: 'N/A');
$tipoLabels = ['cita' => 'Cita', 'ruta_emergencia' => 'Ruta (activación por emergencia)', 'juzgado' => 'Juzgado', 'cambio_centro' => 'Cambio de centro'];
$tipoTexto = htmlspecialchars($tipoLabels[$v['tipo']] ?? $v['tipo'] ?? 'N/A');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ficha de Acompañamiento</title>
<style>
    body { font-family: system-ui, -apple-system, sans-serif; background: #f0f2f5; margin: 0; padding: 16px; }
    .card { background: #fff; max-width: 440px; margin: 0 auto; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); overflow: hidden; }
    .header { background: #2E86C1; color: #fff; padding: 18px 20px; }
    .header h1 { margin: 0; font-size: 18px; }
    .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
    .section { padding: 16px 20px; border-bottom: 1px solid #eee; }
    .section:last-child { border-bottom: none; }
    .label { font-size: 11px; text-transform: uppercase; color: #8e9aa5; letter-spacing: 0.5px; margin-bottom: 2px; }
    .value { font-size: 16px; color: #2c3e50; font-weight: 600; margin-bottom: 10px; }
    .value:last-child { margin-bottom: 0; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #2E86C1; color: #fff; text-transform: uppercase; }
</style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>🏥 Ficha de Acompañamiento</h1>
            <p>Escaneada para verificación de escolta</p>
        </div>
        <div class="section">
            <div class="label">Tipo</div>
            <div class="value"><?= $tipoTexto ?></div>
            <div class="label">Paciente</div>
            <div class="value"><?= $nombreCompleto ?></div>
            <div class="label">Documento</div>
            <div class="value"><?= $documento ?></div>
        </div>
        <div class="section">
            <div class="label">Fecha y hora</div>
            <div class="value"><?= $fecha ?> · <?= $hora ?></div>
            <div class="label">Lugar de destino</div>
            <div class="value"><?= $lugar ?></div>
            <div class="label">Notas</div>
            <div class="value"><?= $motivo ?></div>
            <?php if (!empty($v['attachment'])): ?>
            <div class="label">Documento adjunto</div>
            <div class="value"><a href="<?= htmlspecialchars($v['attachment']) ?>" target="_blank" style="color:#2E86C1;">Ver documento</a></div>
            <?php endif; ?>
        </div>
        <div class="section">
            <div class="label">Custodio acompañante</div>
            <div class="value"><?= $custodioNombre ?></div>
            <div class="label">Teléfono de contacto</div>
            <div class="value"><?= $custodioTelefono ?></div>
        </div>
        <div class="section">
            <span class="badge"><?= htmlspecialchars($v['status']) ?></span>
        </div>
    </div>
</body>
</html>
