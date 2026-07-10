<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('No autorizado');
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Aquí deberías consultar los turnos de la fecha desde la base de datos
// Por ahora, generamos un CSV de ejemplo
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="agenda_' . $fecha . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Cuidador', 'Fecha', 'Hora Inicio', 'Hora Fin', 'Ubicación', 'Estado']);

// Ejemplo de datos (reemplazar con consulta real)
fputcsv($output, [1, 'Juan Pérez', $fecha, '08:00', '12:00', 'Clínica A', 'pendiente']);
fputcsv($output, [2, 'María Gómez', $fecha, '13:00', '17:00', 'Hospital B', 'completado']);

fclose($output);
?>