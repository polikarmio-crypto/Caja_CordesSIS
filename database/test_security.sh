#!/bin/bash
# =========================================================================
# SCRIPT DE PRUEBA PRÁCTICA DE PERMISOS - CAJA CORDES SIS
# Valida la separación de funciones y el principio de menor privilegio.
# =========================================================================

echo "====================================================================="
echo "   INICIANDO PRUEBAS DE SEGURIDAD Y PERMISOS EN POSTGRESQL           "
echo "====================================================================="

DB_NAME="caja_cordes"
DB_HOST="127.0.0.1"

# Función auxiliar para ejecutar una consulta y atrapar el resultado de permiso
ejecutar_prueba() {
    local usuario=$1
    local password=$2
    local descripcion=$3
    local query=$4
    local espera_exito=$5 # true o false

    echo -n "[Prueba] $descripcion: "

    # Ejecutar psql con la contraseña del usuario en la variable de entorno
    output=$(PGPASSWORD="$password" psql -h "$DB_HOST" -U "$usuario" -d "$DB_NAME" -c "$query" 2>&1)
    status=$?

    if [ $status -eq 0 ]; then
        if [ "$espera_exito" = "true" ]; then
            echo -e "\e[32m[PERMITIDO - ÉXITO ESPERADO]\e[0m"
            echo "   Resultado: Ejecutado correctamente."
        else
            echo -e "\e[31m[PERMITIDO - FALLO: Se esperaba denegación!]\e[0m"
            echo "   Detalle: $output"
        fi
    else
        if [ "$espera_exito" = "false" ]; then
            echo -e "\e[32m[DENEGADO - ÉXITO ESPERADO]\e[0m"
            # Extraer el mensaje de error de postgres de forma amigable
            clean_err=$(echo "$output" | grep -i "error" | head -n 1)
            if [ -z "$clean_err" ]; then
                clean_err=$(echo "$output" | head -n 1)
            fi
            echo "   Mensaje de error: $clean_err"
        else
            echo -e "\e[31m[DENEGADO - FALLO: Se esperaba permiso de acceso!]\e[0m"
            echo "   Detalle: $output"
        fi
    fi
    echo "---------------------------------------------------------------------"
}

# =========================================================================
# FASE 1: PRUEBAS DE ACCESO PARA usr_admin_1 (PERSONAL ADMINISTRATIVO)
# =========================================================================
echo -e "\n>>> EJECUTANDO PRUEBAS PARA ROL: ADMINISTRATIVO (usr_admin_1) <<<"

# A1. Lectura de pacientes (Permitido)
ejecutar_prueba "usr_admin_1" "AdminPass123_#" \
    "Administrativo consulta pacientes" \
    "SELECT id, ci, nombres, apellidos FROM pacientes LIMIT 2;" "true"

# A2. Inserción de una cita médica de prueba (Permitido)
# Usamos IDs de médicos y pacientes de la base de datos.
paciente_id=$(psql -U postgres -d "$DB_NAME" -t -A -c "SELECT id FROM pacientes LIMIT 1;")
medico_id=$(psql -U postgres -d "$DB_NAME" -t -A -c "SELECT id FROM medicos LIMIT 1;")

if [ -n "$paciente_id" ] && [ -n "$medico_id" ]; then
    ejecutar_prueba "usr_admin_1" "AdminPass123_#" \
        "Administrativo registra nueva cita médica (Prueba de Trigger con SECURITY DEFINER)" \
        "INSERT INTO citas (paciente_id, medico_id, fecha_hora, motivo, estado) VALUES ($paciente_id, $medico_id, '2026-06-15 10:00:00', 'Consulta general rutinaria', 'pendiente');" "true"
else
    echo "   [!] Omitiendo A2: No se encontraron pacientes o médicos en la base de datos para la FK."
fi

# A3. Intento de lectura de Historias Clínicas (DENEGACIÓN CRÍTICA POR PRIVACIDAD HIPAA/ISO 27001)
ejecutar_prueba "usr_admin_1" "AdminPass123_#" \
    "Administrativo intenta leer Historias Clínicas (Médicas)" \
    "SELECT * FROM historia_clinica;" "false"

# A4. Intento de lectura de Diagnósticos Médicos (DENEGACIÓN CRÍTICA POR PRIVACIDAD HIPAA)
ejecutar_prueba "usr_admin_1" "AdminPass123_#" \
    "Administrativo intenta leer diagnósticos médicos confidenciales" \
    "SELECT * FROM hc_diagnosticos;" "false"


# =========================================================================
# FASE 2: PRUEBAS DE ACCESO PARA usr_medico_1 (PERSONAL MÉDICO)
# =========================================================================
echo -e "\n>>> EJECUTANDO PRUEBAS PARA ROL: MÉDICO (usr_medico_1) <<<"

# M1. Lectura de Historias Clínicas (Permitido)
ejecutar_prueba "usr_medico_1" "MedicoPass123_#" \
    "Médico consulta Historias Clínicas" \
    "SELECT id, paciente_id, fecha_registro FROM historia_clinica LIMIT 2;" "true"

# M2. Registro de una nueva Historia Clínica (Permitido)
if [ -n "$paciente_id" ] && [ -n "$medico_id" ]; then
    ejecutar_prueba "usr_medico_1" "MedicoPass123_#" \
        "Médico registra diagnóstico en Historia Clínica" \
        "INSERT INTO historia_clinica (paciente_id, medico_id, fecha_registro) VALUES ($paciente_id, $medico_id, CURRENT_TIMESTAMP);" "true"
else
    echo "   [!] Omitiendo M2: No se encontraron registros clínicos base."
fi

# M3. Intento de lectura de datos de Facturación (DENEGACIÓN - SEPARACIÓN DE FUNCIONES)
ejecutar_prueba "usr_medico_1" "MedicoPass123_#" \
    "Médico intenta consultar información financiera (Facturas)" \
    "SELECT * FROM facturas;" "false"

# M4. Intento de alteración de inventario o datos administrativos
ejecutar_prueba "usr_medico_1" "MedicoPass123_#" \
    "Médico intenta borrar un rol del sistema" \
    "DELETE FROM roles WHERE nombre = 'Paciente';" "false"


# =========================================================================
# FASE 3: PRUEBAS DE ACCESO PARA usr_directivo_1 (ROL DIRECTIVO / AUDITOR)
# =========================================================================
echo -e "\n>>> EJECUTANDO PRUEBAS PARA ROL: DIRECTIVO/AUDITOR (usr_directivo_1) <<<"

# D1. Lectura de logs de Auditoría (Permitido)
ejecutar_prueba "usr_directivo_1" "DirectivoPass123_#" \
    "Director financiero/Auditor consulta log de auditoría" \
    "SELECT id, usuario_id, accion, tabla, fecha FROM auditoria LIMIT 3;" "true"

# D2. Consulta analítica general (Permitido en todo el esquema como Read-Only)
ejecutar_prueba "usr_directivo_1" "DirectivoPass123_#" \
    "Director realiza lectura analítica de la tabla especialidades" \
    "SELECT * FROM especialidades;" "true"

# D3. Intento de alteración de datos de citas (DENEGACIÓN - SOLO LECTURA)
ejecutar_prueba "usr_directivo_1" "DirectivoPass123_#" \
    "Director intenta cancelar una cita directamente" \
    "UPDATE citas SET estado = 'cancelada' WHERE id = 1;" "false"

# D4. Intento de alteración del log de auditoría (DENEGACIÓN CRÍTICA PARA INTEGRIDAD DE LOGS)
ejecutar_prueba "usr_directivo_1" "DirectivoPass123_#" \
    "Director intenta borrar registros de la bitácora de auditoría" \
    "DELETE FROM auditoria;" "false"


# =========================================================================
# FASE 4: PRUEBAS DE ACCESO PARA usr_paciente_1 (PACIENTE BAJO RLS)
# =========================================================================
echo -e "\n>>> EJECUTANDO PRUEBAS PARA ROL: PACIENTE (usr_paciente_1) <<<"

# P1. Consulta de catálogo de especialidades (Permitido lectura general)
ejecutar_prueba "usr_paciente_1" "PacientePass123_#" \
    "Paciente consulta catálogo de especialidades" \
    "SELECT * FROM especialidades LIMIT 2;" "true"

# P2. Intento de acceso a historias clínicas confidenciales
ejecutar_prueba "usr_paciente_1" "PacientePass123_#" \
    "Paciente intenta ver historias clínicas del hospital" \
    "SELECT * FROM historia_clinica;" "false"

# P3. Demostración de Row-Level Security (RLS) en la tabla 'pacientes'
# SELECT * FROM pacientes; -> Debe devolver solo el paciente con el email = current_user ('usr_paciente_1')
# o cero si no está mapeado, pero NUNCA dar un error de permiso denegado, y NUNCA mostrar registros de otros pacientes.
ejecutar_prueba "usr_paciente_1" "PacientePass123_#" \
    "Paciente consulta información personal (Demostración de RLS)" \
    "SELECT ci, nombres, apellidos FROM pacientes;" "true"


echo -e "\n====================================================================="
echo "   FIN DE LAS PRUEBAS DE SEGURIDAD. EVALUACIÓN DE ROLES COMPLETADA.  "
echo "====================================================================="
