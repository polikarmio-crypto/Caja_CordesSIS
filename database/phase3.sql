-- Tablas para Hospitalizacion
CREATE TABLE IF NOT EXISTS habitaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    tipo ENUM('general', 'privada', 'uci', 'pediatria') NOT NULL,
    estado ENUM('disponible', 'mantenimiento') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS camas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    habitacion_id INT UNSIGNED NOT NULL,
    numero_cama VARCHAR(20) NOT NULL,
    estado ENUM('libre', 'ocupada', 'limpieza', 'mantenimiento') DEFAULT 'libre',
    FOREIGN KEY (habitacion_id) REFERENCES habitaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hospitalizaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    cama_id INT UNSIGNED NOT NULL,
    fecha_ingreso DATETIME NOT NULL,
    fecha_alta DATETIME NULL,
    motivo_ingreso TEXT NOT NULL,
    notas_alta TEXT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (cama_id) REFERENCES camas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas para Laboratorio
CREATE TABLE IF NOT EXISTS examenes_catalogo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    tipo_muestra VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resultados_laboratorio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    hc_id INT UNSIGNED NULL, -- Relacionado a una historia clinica opcional
    examen_id INT UNSIGNED NOT NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resultado DATETIME NULL,
    resultado TEXT NULL,
    valores_referencia VARCHAR(200) NULL,
    estado ENUM('pendiente', 'completado') DEFAULT 'pendiente',
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (hc_id) REFERENCES historia_clinica(id) ON DELETE SET NULL,
    FOREIGN KEY (examen_id) REFERENCES examenes_catalogo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar Datos Semilla
INSERT IGNORE INTO habitaciones (id, numero, tipo) VALUES 
(1, '101', 'general'),
(2, '102', 'privada'),
(3, '201', 'uci');

INSERT IGNORE INTO camas (id, habitacion_id, numero_cama, estado) VALUES
(1, 1, '101-A', 'libre'),
(2, 1, '101-B', 'libre'),
(3, 1, '101-C', 'libre'),
(4, 2, '102-A', 'ocupada'),
(5, 3, '201-UCI', 'libre');

INSERT IGNORE INTO examenes_catalogo (id, nombre, tipo_muestra) VALUES
(1, 'Hemograma Completo', 'Sangre'),
(2, 'Perfil Lipídico', 'Sangre'),
(3, 'Examen de Orina Completo', 'Orina'),
(4, 'Glucosa en Ayunas', 'Sangre');
