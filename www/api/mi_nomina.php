<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caregiver') {
    header('Location: login.html');
    exit;
}
require_once 'api/config.php';
$page_title = 'Mi Nómina';
include 'header.php';
?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Mi Nómina</span>
    </div>
    <div class="form-group">
        <label for="nominaMes">Mes</label>
        <input type="month" id="nominaMes" value="<?php echo date('Y-m'); ?>" style="width:100%;padding:10px;border:1px solid #D5D8DC;border-radius:8px;">
    </div>
    <button onclick="calcularMiNomina()" class="btn btn-primary">Calcular</button>
    <div id="resultadoNomina" style="margin-top:20px;"></div>
</div>

<script>
function calcularMiNomina() {
    const mes = document.getElementById('nominaMes').value;
    if (!mes) return;
    const user = getUser();
    const userId = user.id;
    if (!userId) {
        alert('No se pudo identificar tu usuario. Vuelve a iniciar sesión.');
        return;
    }
    document.getElementById('resultadoNomina').innerHTML = '<p>Cargando...</p>';
    fetch('api/calculate_payroll.php?caregiver_id=' + userId + '&month=' + mes)
        .then(r => r.json())
        .then(d => {
            let html = '';
            if (d.error) {
                html = '<p style="color:red;">' + d.error + '</p>';
            } else {
                html = '<div style="background:#f8f9fa;padding:16px;border-radius:8px;">' +
                    '<h3>Resultado - ' + mes + '</h3>' +
                    '<p>Horas Ordinarias: ' + (d.ordinary_hours || 0) + '</p>' +
                    '<p>Horas Extra 1.25: ' + (d.overtime_125 || 0) + '</p>' +
                    '<p>Horas Extra 1.75: ' + (d.overtime_175 || 0) + '</p>' +
                    '<p><strong>Total a Pagar: $' + (d.total_pagar || 0) + '</strong></p>' +
                    '</div>';
            }
            document.getElementById('resultadoNomina').innerHTML = html;
        })
        .catch(e => {
            document.getElementById('resultadoNomina').innerHTML = '<p style="color:red;">Error al calcular nómina. Verifica tu conexión.</p>';
            console.error(e);
        });
}
</script>
<?php include 'footer.php'; ?>