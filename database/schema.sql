DROP DATABASE IF EXISTS caja_cordes;
CREATE DATABASE caja_cordes;
USE caja_cordes;

-- =========================================================================
-- NORMALIZACIÓN AVANZADA (HASTA 5NF) Y AFINAMIENTO DE RENDIMIENTO
-- Uso de INT UNSIGNED, Índices compuestos B-Tree y Transaccionalidad
-- =========================================================================

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id INT UNSIGNED NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    UNIQUE INDEX idx_email (email)
) ENGINE=InnoDB;

CREATE TABLE pacientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    dni VARCHAR(20) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nac DATE NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_dni (dni)
) ENGINE=InnoDB;

-- 4NF/5NF: Dependencia Multivaluada separada
CREATE TABLE paciente_telefonos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_paciente_telefono (paciente_id, telefono)
) ENGINE=InnoDB;

CREATE TABLE especialidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE medicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    licencia_medica VARCHAR(50) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_licencia (licencia_medica)
) ENGINE=InnoDB;

-- 4NF/5NF: Un médico puede tener múltiples especialidades
CREATE TABLE medico_especialidades (
    medico_id INT UNSIGNED NOT NULL,
    especialidad_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (medico_id, especialidad_id),
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE horarios_medicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medico_id INT UNSIGNED NOT NULL,
    dia_semana ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    INDEX idx_medico_dia (medico_id, dia_semana)
) ENGINE=InnoDB;

CREATE TABLE citas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    medico_id INT UNSIGNED NOT NULL,
    fecha_hora DATETIME NOT NULL,
    motivo TEXT,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    -- Covering Indexes para consultas rápidas
    INDEX idx_agenda_medico (medico_id, fecha_hora),
    INDEX idx_historial_paciente_citas (paciente_id, fecha_hora)
) ENGINE=InnoDB;

-- Cabecera de Historia Clínica
CREATE TABLE historia_clinica (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    medico_id INT UNSIGNED NOT NULL,
    cita_id INT UNSIGNED,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    INDEX idx_hc_paciente (paciente_id, fecha_registro DESC)
) ENGINE=InnoDB;

-- Detalles de HC separados para cumplir formas normales
CREATE TABLE hc_diagnosticos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hc_id INT UNSIGNED NOT NULL,
    diagnostico TEXT NOT NULL,
    FOREIGN KEY (hc_id) REFERENCES historia_clinica(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE hc_archivos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hc_id INT UNSIGNED NOT NULL,
    archivo_ruta VARCHAR(255) NOT NULL,
    FOREIGN KEY (hc_id) REFERENCES historia_clinica(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recetas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hc_id INT UNSIGNED NOT NULL,
    indicaciones_generales TEXT,
    FOREIGN KEY (hc_id) REFERENCES historia_clinica(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE medicamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50),
    stock INT NOT NULL DEFAULT 0,
    vencimiento DATE,
    INDEX idx_medicamento_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE receta_medicamentos (
    receta_id INT UNSIGNED NOT NULL,
    medicamento_id INT UNSIGNED NOT NULL,
    dosis VARCHAR(100) NOT NULL,
    frecuencia VARCHAR(100) NOT NULL,
    duracion_dias INT UNSIGNED NOT NULL,
    PRIMARY KEY (receta_id, medicamento_id),
    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(50),
    mensaje TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida)
) ENGINE=InnoDB;

-- Para auditoria usamos particionamiento (RANGE) por Año en la BD si crece mucho.
-- Dado que la clave foránea no es soportada en MySQL con particionamiento en algunas versiones (a menos que esté en la PK),
-- guardamos el usuario_id sin la restricción FOREIGN KEY para permitir particionado, o usamos un TRIGGER.
CREATE TABLE auditoria (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla VARCHAR(50) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id, fecha), -- Se requiere fecha en PK para particionar
    INDEX idx_audit_fecha (fecha)
) ENGINE=InnoDB
PARTITION BY RANGE (YEAR(fecha)) (
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
