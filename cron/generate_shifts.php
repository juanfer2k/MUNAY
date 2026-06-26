#!/usr/bin/php
<?php
// generate_shifts.php - Generador automático de turnos
// Ejecutar: php /home2/elcerrit/elcerritovalle.org/MUNAY/cron/generate_shifts.php

// Ruta correcta al config.php
require_once __DIR__ . '/../www/api/config.php';
function generateShiftsForWeek($startDate = null) {
    $pdo = getDB();
    
    if (!$startDate) {
        $startDate = date('Y-m-d', strtotime('monday this week'));
    }
    
    echo "📅 Generando turnos para semana del: $startDate\n";
    
    $stmt = $pdo->query("SELECT id, name, group_type FROM caregivers WHERE role != 'admin'");
    $caregivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($caregivers)) {
        echo "❌ No hay cuidadores registrados\n";
        return false;
    }
    
    $weekDays = [];
    for ($i = 0; $i < 5; $i++) {
        $weekDays[] = date('Y-m-d', strtotime($startDate . " +$i days"));
    }
    
    $shiftMap = [
        'A' => ['start' => '06:00', 'end' => '14:00', 'type' => 'base'],
        'B' => ['start' => '14:00', 'end' => '22:00', 'type' => 'base'],
        'C' => ['start' => '22:00', 'end' => '06:00', 'type' => 'base']
    ];
    
    $totalCreated = 0;
    
    foreach ($caregivers as $caregiver) {
        $groupId = $caregiver['group_type'];
        
        if (!isset($shiftMap[$groupId])) {
            echo "⚠️ Grupo {$groupId} no tiene turnos definidos para {$caregiver['name']}\n";
            continue;
        }
        
        foreach ($weekDays as $date) {
            $check = $pdo->prepare("SELECT id FROM shifts WHERE caregiver_id = ? AND shift_date = ? AND 
shift_type = 'base'");
            $check->execute([$caregiver['id'], $date]);
            if ($check->fetch()) {
                continue;
            }
            
            $shift = $shiftMap[$groupId];
            
            $stmt = $pdo->prepare("
                INSERT INTO shifts (caregiver_id, shift_date, start_time, end_time, shift_type, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$caregiver['id'], $date, $shift['start'], $shift['end'], $shift['type']]);
            $totalCreated++;
            echo "  ✅ {$caregiver['name']}: {$date} {$shift['start']}-{$shift['end']}\n";
        }
    }
    
    echo "📊 Total de turnos generados: $totalCreated\n";
    return true;
}

// ============ EJECUCIÓN ============
if (php_sapi_name() === 'cli') {
    $startDate = $argv[1] ?? null;
    generateShiftsForWeek($startDate);
} else {
    header('Content-Type: text/plain');
    echo "Este script debe ejecutarse desde cron o terminal\n";
    echo "Uso: php generate_shifts.php [YYYY-MM-DD]\n";
}
?>
