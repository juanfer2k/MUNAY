#!/bin/bash
# weekly_generator.sh - Ejecutar generacion de turnos semanalmente

# Ruta al proyecto
PROJECT_DIR="/home2/elcerrit/elcerritovalle.org/MUNAY"
LOG_FILE="$PROJECT_DIR/logs/shift_generator.log"

# Crear directorio de logs si no existe
mkdir -p "$PROJECT_DIR/logs"

# Fecha actual
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Iniciando generacion de turnos..." >> "$LOG_FILE"

# Ejecutar generador
php "$PROJECT_DIR/cron/generate_shifts.php" >> "$LOG_FILE" 2>&1

echo "[$DATE] Generación completada" >> "$LOG_FILE"
echo "----------------------------------------" >> "$LOG_FILE"