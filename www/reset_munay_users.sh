#!/bin/bash
# reset_munay_users.sh - Desbloquear usuarios y resetear intentos fallidos

set -e

DB_USER="elcerrit_user"
DB_NAME="elcerrit_MUNAY"
DB_HOST="localhost"

echo "======================================"
echo "MUNAY - Reset de Usuarios Bloqueados"
echo "======================================"
echo ""

# Pedir contraseña
echo "Ingresa contraseña de BD (elcerrit_user):"
read -s DB_PASS
echo ""

# Verificar conexión
if ! mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e "SELECT 1;" > /dev/null 2>&1; then
    echo "❌ Error: No se puede conectar a la BD"
    echo "Verifica:"
    echo "  - DB_USER: $DB_USER"
    echo "  - DB_NAME: $DB_NAME"
    echo "  - Contraseña correcta"
    exit 1
fi

echo "✅ Conexión BD OK"
echo ""

# Opción de qué hacer
echo "¿Qué deseas hacer?"
echo "1) Desbloquear todos los usuarios"
echo "2) Desbloquear un usuario específico"
echo "3) Ver estado de todos los usuarios"
echo ""
read -p "Selecciona (1-3): " option

case $option in
    1)
        echo ""
        echo "Desbloqueando todos los usuarios..."
        mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
          "UPDATE usuarios_login SET intentos_fallidos = 0, bloqueado_hasta = NULL;"
        echo "✅ Todos los usuarios desbloqueados"
        echo ""
        echo "Estado actual:"
        mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
          "SELECT id_usuario, nombre, rol, intentos_fallidos, bloqueado_hasta FROM usuarios_login;"
        ;;
    2)
        echo ""
        read -p "Ingresa ID del usuario a desbloquear: " user_id
        echo ""
        echo "Desbloqueando usuario $user_id..."
        mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
          "UPDATE usuarios_login SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = '$user_id';"
        echo "✅ Usuario $user_id desbloqueado"
        echo ""
        echo "Estado del usuario:"
        mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
          "SELECT id_usuario, nombre, rol, intentos_fallidos, bloqueado_hasta FROM usuarios_login WHERE id_usuario = '$user_id';"
        ;;
    3)
        echo ""
        echo "Estado de todos los usuarios:"
        echo ""
        mysql -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
          "SELECT id_usuario, nombre, rol, estado, intentos_fallidos, bloqueado_hasta FROM usuarios_login ORDER BY id_usuario;"
        ;;
    *)
        echo "❌ Opción inválida"
        exit 1
        ;;
esac

echo ""
echo "======================================"
echo "✅ Operación completada"
echo "======================================"
