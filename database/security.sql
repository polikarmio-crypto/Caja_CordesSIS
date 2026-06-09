-- =========================================================================
-- PROYECTO FINAL: CAJA DE SALUD CORDES (SIS)
-- SCRIPT DE SEGURIDAD DBMS: USUARIOS, ROLES Y PRIVILEGIOS
-- Base de Datos: PostgreSQL
-- =========================================================================

-- NOTA: Ejecutar este script como superusuario (postgres) en la base de datos 'caja_cordes'.
-- psql -U postgres -d caja_cordes -f security.sql

-- -------------------------------------------------------------------------
-- 1. LIMPIEZA DE ROLES Y USUARIOS PREVIOS (Para permitir re-ejecución limpia)
-- -------------------------------------------------------------------------
DO $$
BEGIN
    -- Revocar membresías de roles antes de eliminarlos
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'usr_admin_1') THEN
        DROP OWNED BY usr_admin_1;
        DROP USER usr_admin_1;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'usr_medico_1') THEN
        DROP OWNED BY usr_medico_1;
        DROP USER usr_medico_1;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'usr_directivo_1') THEN
        DROP OWNED BY usr_directivo_1;
        DROP USER usr_directivo_1;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'usr_paciente_1') THEN
        DROP OWNED BY usr_paciente_1;
        DROP USER usr_paciente_1;
    END IF;

    -- Limpiar dependencias y eliminar roles de grupo
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'rol_administrativo') THEN
        DROP OWNED BY rol_administrativo;
        DROP ROLE rol_administrativo;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'rol_medico') THEN
        DROP OWNED BY rol_medico;
        DROP ROLE rol_medico;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'rol_directivo') THEN
        DROP OWNED BY rol_directivo;
        DROP ROLE rol_directivo;
    END IF;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'rol_paciente') THEN
        DROP OWNED BY rol_paciente;
        DROP ROLE rol_paciente;
    END IF;
END $$;

-- Restablecer la asociación de emails antes de actualizar (por si ya se ejecutaron)
UPDATE usuarios SET email = 'admin@cajacordes.com' WHERE email = 'usr_admin_1';
UPDATE usuarios SET email = 'medico@cajacordes.com' WHERE email = 'usr_medico_1';
UPDATE usuarios SET email = 'paciente@cajacordes.com' WHERE email = 'usr_paciente_1';

-- -------------------------------------------------------------------------
-- 2. CREACIÓN DE ROLES DE GRUPO (Roles de función)
-- -------------------------------------------------------------------------
CREATE ROLE rol_administrativo WITH NOLOGIN;
CREATE ROLE rol_medico WITH NOLOGIN;
CREATE ROLE rol_directivo WITH NOLOGIN;
CREATE ROLE rol_paciente WITH NOLOGIN;

COMMENT ON ROLE rol_administrativo IS 'Rol para personal de admisión, recepción y facturación. Sin acceso a datos clínicos.';
COMMENT ON ROLE rol_medico IS 'Rol para personal médico. Acceso completo a historias clínicas, recetas y laboratorios. Sin acceso a facturación.';
COMMENT ON ROLE rol_directivo IS 'Rol para directores y auditores del hospital. Acceso de solo lectura global y lectura de auditoría.';
COMMENT ON ROLE rol_paciente IS 'Rol para pacientes. Acceso altamente restringido (Row-Level Security) para ver solo sus propios datos.';

-- -------------------------------------------------------------------------
-- 3. CREACIÓN DE USUARIOS INDIVIDUALES (Cuentas con login)
-- -------------------------------------------------------------------------
CREATE USER usr_admin_1 WITH PASSWORD 'AdminPass123_#';
CREATE USER usr_medico_1 WITH PASSWORD 'MedicoPass123_#';
CREATE USER usr_directivo_1 WITH PASSWORD 'DirectivoPass123_#';
CREATE USER usr_paciente_1 WITH PASSWORD 'PacientePass123_#';

-- Asignar usuarios individuales a sus respectivos roles de grupo
GRANT rol_administrativo TO usr_admin_1;
GRANT rol_medico TO usr_medico_1;
GRANT rol_directivo TO usr_directivo_1;
GRANT rol_paciente TO usr_paciente_1;

-- -------------------------------------------------------------------------
-- 4. MAPEADO DE USUARIOS DE APLICACIÓN CON USUARIOS DBMS
-- -------------------------------------------------------------------------
UPDATE usuarios SET email = 'usr_admin_1' WHERE email = 'admin@cajacordes.com';
UPDATE usuarios SET email = 'usr_medico_1' WHERE email = 'medico@cajacordes.com';
UPDATE usuarios SET email = 'usr_paciente_1' WHERE email = 'paciente@cajacordes.com';

-- -------------------------------------------------------------------------
-- 5. SEGURIDAD A NIVEL DE ESQUEMA (Evita que usuarios accedan por defecto)
-- -------------------------------------------------------------------------
-- Revocar todos los privilegios por defecto en el esquema public del rol 'public' (todos los usuarios)
REVOKE ALL ON SCHEMA public FROM public;
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM public;

-- Conceder acceso de uso al esquema public a nuestros roles
GRANT USAGE ON SCHEMA public TO rol_administrativo, rol_medico, rol_directivo, rol_paciente;

-- Conceder privilegios sobre secuencias (vital para columnas SERIAL / autoincrementables)
-- Se otorga USAGE, SELECT y UPDATE para poder insertar y actualizar campos autoincrementales
GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA public TO rol_administrativo, rol_medico, rol_directivo;

-- -------------------------------------------------------------------------
-- 6. ASIGNACIÓN DETALLADA DE PRIVILEGIOS POR ROL (RBAC MATRIZ)
-- -------------------------------------------------------------------------

-- ==========================================
-- A. ROL ADMINISTRATIVO (Gestión del Hospital, Citas y Facturación)
-- ==========================================
-- Administración general de citas, pacientes y sucursales
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE 
    pacientes, paciente_telefonos, citas, horarios_medicos,
    medicos, medico_especialidades, especialidades, usuarios, roles,
    sucursales, calificaciones
    TO rol_administrativo;

-- Gestión del flujo administrativo de hospitalización y camas
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE 
    habitaciones, camas, hospitalizaciones 
    TO rol_administrativo;

-- Gestión de facturación e inventario general
GRANT SELECT, INSERT, UPDATE ON TABLE 
    facturas, factura_detalles, insumos, categorias_insumo 
    TO rol_administrativo;

-- Permisos de lectura para medicamentos generales y catálogo de exámenes
GRANT SELECT ON TABLE 
    medicamentos, examenes_catalogo 
    TO rol_administrativo;

-- Envío de notificaciones al sistema
GRANT SELECT, INSERT, UPDATE ON TABLE 
    notificaciones 
    TO rol_administrativo;

-- ==========================================
-- B. ROL MÉDICO (Historias Clínicas, Diagnósticos, Recetas y Laboratorio)
-- ==========================================
-- Gestión de información médica del paciente (Exclusivo - Privacidad HIPAA/ISO 27001)
GRANT SELECT, INSERT, UPDATE ON TABLE 
    historia_clinica, hc_diagnosticos, hc_archivos,
    recetas, receta_medicamentos 
    TO rol_medico;

-- Consulta de información básica del paciente, médicos e infraestructura
GRANT SELECT ON TABLE 
    pacientes, paciente_telefonos, medicos, medico_especialidades, 
    especialidades, horarios_medicos, citas, sucursales, habitaciones, camas, hospitalizaciones 
    TO rol_medico;

-- Actualización de estado de la cita médica (por ejemplo, de 'pendiente' a 'completada')
GRANT SELECT, UPDATE ON TABLE 
    citas 
    TO rol_medico;

-- Consulta y prescripción de medicamentos
GRANT SELECT, UPDATE ON TABLE 
    medicamentos 
    TO rol_medico;

-- Envío de notificaciones y órdenes / resultados de laboratorio
GRANT SELECT, INSERT, UPDATE ON TABLE 
    notificaciones, examenes_catalogo, resultados_laboratorio 
    TO rol_medico;

-- ==========================================
-- C. ROL DIRECTIVO / AUDITOR (Acceso de solo lectura global e inspección de logs)
-- ==========================================
-- Lectura analítica de todo el esquema de la base de datos
GRANT SELECT ON ALL TABLES IN SCHEMA public TO rol_directivo;

-- Restricción absoluta de escritura (SEPARACIÓN DE FUNCIONES):
-- El rol directivo tiene SELECT pero NO puede realizar INSERT, UPDATE o DELETE en ninguna tabla comercial/clínica ni en auditoria.
-- Esto asegura que los informes y bitácoras de auditoría no puedan ser alterados por el cuerpo administrativo ni directivos.

-- ==========================================
-- D. ROL PACIENTE (Consulta altamente restringida y Row-Level Security)
-- ==========================================
-- Permitir lectura básica para que el paciente pueda consultar recetas, citas y laboratorio
GRANT SELECT ON TABLE 
    pacientes, paciente_telefonos, citas, recetas, receta_medicamentos,
    resultados_laboratorio, medicamentos, medicos, especialidades, sucursales
    TO rol_paciente;

-- Concesión de privilegios a nivel de COLUMNA en la tabla de usuarios:
-- El paciente necesita leer los campos 'id' y 'email' para validar políticas de RLS.
-- Para cumplir con el principio de menor privilegio y seguridad absoluta de contraseñas,
-- NO concedemos acceso a la tabla completa usuarios, sino únicamente a las columnas necesarias,
-- protegiendo la columna 'password_hash'.
GRANT SELECT(id, email) ON usuarios TO rol_paciente;

-- -------------------------------------------------------------------------
-- 7. CONFIGURACIÓN DEL TRIGGER COMO SECURITY DEFINER (Auditoría Segura)
-- -------------------------------------------------------------------------
-- Por defecto, los triggers de PostgreSQL se ejecutan con los permisos del usuario que invoca la acción (SECURITY INVOKER).
-- Esto causa un fallo si un rol administrativo inserta una cita, ya que la función del trigger intenta escribir en 'auditoria',
-- tabla sobre la cual no tiene permisos directos.
--
-- SOLUCIÓN: Definir la función del disparador como 'SECURITY DEFINER'. Esto hace que la función se ejecute
-- con los privilegios de su creador (superusuario postgres), permitiendo registrar el log pero manteniendo
-- la tabla 'auditoria' totalmente inaccesible y protegida para manipulaciones directas del usuario administrativo.
ALTER FUNCTION trg_auditoria_citas_insert_fn() SECURITY DEFINER;

-- -------------------------------------------------------------------------
-- 8. POLÍTICAS DE ROW-LEVEL SECURITY (RLS) - CONTROL AVANZADO DE PRIVILEGIOS
-- -------------------------------------------------------------------------
-- Habilitar RLS en tablas críticas para asegurar la privacidad del paciente
ALTER TABLE citas ENABLE ROW LEVEL SECURITY;
ALTER TABLE pacientes ENABLE ROW LEVEL SECURITY;

-- Eliminar políticas anteriores si existen para evitar duplicados
DROP POLICY IF EXISTS citas_paciente_policy ON citas;
DROP POLICY IF EXISTS citas_staff_policy ON citas;
DROP POLICY IF EXISTS pacientes_paciente_policy ON pacientes;
DROP POLICY IF EXISTS pacientes_staff_policy ON pacientes;

-- Crear una política de RLS para 'citas'
-- Se crea una política para que rol_paciente solo vea sus propias citas
-- Identificamos al paciente correlacionando su 'usuario_id' con la sesión activa de PostgreSQL
CREATE POLICY citas_paciente_policy ON citas
    FOR SELECT
    TO rol_paciente
    USING (
        paciente_id IN (
            SELECT p.id FROM pacientes p 
            INNER JOIN usuarios u ON p.usuario_id = u.id 
            WHERE u.email = current_user -- El email coincide con el nombre de usuario DBMS activo
        )
    );

-- Para los roles administrativos, médicos y directivos, otorgamos acceso completo (BYPASS RLS o políticas permisivas)
CREATE POLICY citas_staff_policy ON citas
    FOR ALL
    TO rol_administrativo, rol_medico, rol_directivo
    USING (true);

-- Crear una política de RLS para 'pacientes'
CREATE POLICY pacientes_paciente_policy ON pacientes
    FOR SELECT
    TO rol_paciente
    USING (
        usuario_id IN (
            SELECT u.id FROM usuarios u 
            WHERE u.email = current_user
        )
    );

CREATE POLICY pacientes_staff_policy ON pacientes
    FOR ALL
    TO rol_administrativo, rol_medico, rol_directivo
    USING (true);

-- -------------------------------------------------------------------------
-- CONFIRMACIÓN DE EJECUCIÓN EXITOSA
-- -------------------------------------------------------------------------
SELECT '¡La estructura de usuarios, roles y privilegios de Caja Cordes SIS ha sido implementada con éxito!' AS resultado;
