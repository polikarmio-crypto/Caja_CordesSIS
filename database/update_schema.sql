-- 1. Modificaciones de tablas
ALTER TABLE especialidades ADD COLUMN IF NOT EXISTS parent_id INTEGER REFERENCES especialidades(id) ON DELETE CASCADE;

ALTER TABLE medicamentos ADD COLUMN IF NOT EXISTS codigo_identificacion VARCHAR(50) UNIQUE;
ALTER TABLE medicamentos ADD COLUMN IF NOT EXISTS activo BOOLEAN DEFAULT TRUE;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS nombres VARCHAR(100);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS apellidos VARCHAR(100);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) DEFAULT 'default_avatar.png';

-- 2. Actualización de medicamentos existentes
UPDATE medicamentos SET codigo_identificacion = 'MED-001' WHERE id = 1 AND codigo_identificacion IS NULL;
UPDATE medicamentos SET codigo_identificacion = 'MED-002' WHERE id = 2 AND codigo_identificacion IS NULL;

-- 3. Inserción de medicamentos adicionales de prueba
INSERT INTO medicamentos (nombre, tipo, stock, vencimiento, precio_unitario, codigo_identificacion, activo) VALUES 
('Ibuprofeno 400mg', 'Tabletas', 200, '2028-12-31', 0.80, 'MED-003', true),
('Amoxicilina 500mg', 'Cápsulas', 150, '2027-06-30', 1.50, 'MED-004', true),
('Loratadina 10mg', 'Tabletas', 300, '2028-09-30', 0.50, 'MED-005', true),
('Omeprazol 20mg', 'Cápsulas', 250, '2028-03-31', 1.20, 'MED-006', true),
('Metformina 850mg', 'Tabletas', 400, '2029-01-31', 0.90, 'MED-007', true),
('Losartán 50mg', 'Tabletas', 350, '2028-11-30', 1.10, 'MED-008', true),
('Atorvastatina 20mg', 'Tabletas', 180, '2028-08-31', 2.00, 'MED-009', true),
('Salbutamol 100mcg', 'Inhalador', 80, '2027-10-31', 25.00, 'MED-010', true),
('Paracetamol Jarabe', 'Jarabe', 120, '2027-04-30', 8.50, 'MED-011', true),
('Complejo B', 'Tabletas', 500, '2029-05-31', 0.40, 'MED-012', true)
ON CONFLICT (codigo_identificacion) DO NOTHING;

-- 4. Actualización de perfiles en la tabla de usuarios
UPDATE usuarios u SET nombres = p.nombres, apellidos = p.apellidos 
FROM pacientes p 
WHERE u.id = p.usuario_id AND u.nombres IS NULL;

UPDATE usuarios u SET nombres = 'Dr. ' || INITCAP(SPLIT_PART(email, '@', 1)), apellidos = 'Médico' 
WHERE rol_id = (SELECT id FROM roles WHERE nombre = 'Médico' LIMIT 1) AND u.nombres IS NULL;

UPDATE usuarios SET nombres = 'Dr. Juan', apellidos = 'Pérez' WHERE email = 'usr_medico_1';
UPDATE usuarios SET nombres = 'Administrador', apellidos = 'Caja Cordes' WHERE email = 'usr_admin_1';
UPDATE usuarios SET nombres = 'Paciente', apellidos = 'De Prueba' WHERE email = 'usr_paciente_1';
UPDATE usuarios SET nombres = 'Farmacéutico', apellidos = 'De Guardia' WHERE email = 'farmacia@cajacordes.com';
UPDATE usuarios SET nombres = 'Laboratorista', apellidos = 'De Guardia' WHERE email = 'laboratorio@cajacordes.com';

UPDATE usuarios SET nombres = 'Usuario', apellidos = 'Sistema' WHERE nombres IS NULL;

-- 5. Estructura y semilla de especialidades
UPDATE especialidades SET parent_id = NULL WHERE id IN (1, 2, 3);

INSERT INTO especialidades (id, nombre, parent_id) VALUES 
(4, 'Ginecología y Obstetricia', NULL),
(5, 'Dermatología', NULL),
(6, 'Traumatología y Ortopedia', NULL),
(7, 'Neurología', NULL),
(8, 'Oftalmología', NULL),
(9, 'Otorrinolaringología', NULL),
(10, 'Endocrinología', NULL),
(11, 'Gastroenterología', NULL),
(12, 'Neumología', NULL),
(13, 'Psiquiatría', NULL),
(14, 'Urología', NULL),
(15, 'Nefrología', NULL)
ON CONFLICT (nombre) DO UPDATE SET parent_id = NULL;

-- Ajustar la secuencia de IDs de especialidades
SELECT setval('especialidades_id_seq', COALESCE((SELECT MAX(id)+1 FROM especialidades), 1), false);

-- Insertar subespecialidades de forma segura
DO $$
DECLARE
    v_ped_id INT;
    v_card_id INT;
    v_gyn_id INT;
    v_traum_id INT;
    v_neuro_id INT;
BEGIN
    SELECT id INTO v_ped_id FROM especialidades WHERE nombre = 'Pediatria' OR nombre = 'Pediatría' LIMIT 1;
    SELECT id INTO v_card_id FROM especialidades WHERE nombre = 'Cardiologia' OR nombre = 'Cardiología' LIMIT 1;
    SELECT id INTO v_gyn_id FROM especialidades WHERE nombre = 'Ginecología y Obstetricia' LIMIT 1;
    SELECT id INTO v_traum_id FROM especialidades WHERE nombre = 'Traumatología y Ortopedia' LIMIT 1;
    SELECT id INTO v_neuro_id FROM especialidades WHERE nombre = 'Neurología' LIMIT 1;

    -- Subespecialidades para Pediatría
    IF v_ped_id IS NOT NULL THEN
        INSERT INTO especialidades (nombre, parent_id) VALUES 
        ('Pediatría Neonatal', v_ped_id),
        ('Pediatría del Desarrollo', v_ped_id),
        ('Cardiología Pediátrica', v_ped_id),
        ('Endocrinología Pediátrica', v_ped_id)
        ON CONFLICT (nombre) DO UPDATE SET parent_id = v_ped_id;
    END IF;

    -- Subspecialties for Cardiología
    IF v_card_id IS NOT NULL THEN
        INSERT INTO especialidades (nombre, parent_id) VALUES 
        ('Cardiología Intervencionista', v_card_id),
        ('Electrofisiología', v_card_id)
        ON CONFLICT (nombre) DO UPDATE SET parent_id = v_card_id;
    END IF;

    -- Subspecialties for Ginecología
    IF v_gyn_id IS NOT NULL THEN
        INSERT INTO especialidades (nombre, parent_id) VALUES 
        ('Ginecología Oncológica', v_gyn_id),
        ('Medicina Materno Fetal', v_gyn_id)
        ON CONFLICT (nombre) DO UPDATE SET parent_id = v_gyn_id;
    END IF;

    -- Subspecialties for Traumatología
    IF v_traum_id IS NOT NULL THEN
        INSERT INTO especialidades (nombre, parent_id) VALUES 
        ('Traumatología Infantil', v_traum_id)
        ON CONFLICT (nombre) DO UPDATE SET parent_id = v_traum_id;
    END IF;

    -- Subspecialties for Neurología
    IF v_neuro_id IS NOT NULL THEN
        INSERT INTO especialidades (nombre, parent_id) VALUES 
        ('Neurología Pediátrica', v_neuro_id)
        ON CONFLICT (nombre) DO UPDATE SET parent_id = v_neuro_id;
    END IF;
END $$;

-- 6. Distribución de médicos en las especialidades y generación de sus turnos de lunes a viernes
DO $$
DECLARE
    v_medico RECORD;
    v_esp_count INT;
    v_esp_id INT;
    v_day VARCHAR;
    v_days VARCHAR[] := ARRAY['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    v_sched_type INT;
BEGIN
    -- Limpiar asociaciones existentes para medicos
    DELETE FROM medico_especialidades;
    DELETE FROM horarios_medicos;

    SELECT COUNT(*) INTO v_esp_count FROM especialidades;

    FOR v_medico IN SELECT id FROM medicos LOOP
        -- Obtener ID de especialidad secuencialmente
        SELECT id INTO v_esp_id FROM (
            SELECT id, row_number() over (order by id) as rn from especialidades
        ) t WHERE rn = ((v_medico.id % v_esp_count) + 1);

        IF v_esp_id IS NULL THEN
            SELECT id INTO v_esp_id FROM especialidades LIMIT 1;
        END IF;

        INSERT INTO medico_especialidades (medico_id, especialidad_id)
        VALUES (v_medico.id, v_esp_id)
        ON CONFLICT DO NOTHING;

        -- Asociar también subespecialidad si corresponde
        IF v_esp_id IN (SELECT DISTINCT parent_id FROM especialidades WHERE parent_id IS NOT NULL) THEN
            SELECT id INTO v_esp_id FROM especialidades WHERE parent_id = v_esp_id ORDER BY random() LIMIT 1;
            IF v_esp_id IS NOT NULL THEN
                INSERT INTO medico_especialidades (medico_id, especialidad_id)
                VALUES (v_medico.id, v_esp_id)
                ON CONFLICT DO NOTHING;
            END IF;
        END IF;

        -- Crear horarios laborales lunes a viernes (máximo 18:00)
        v_sched_type := (v_medico.id % 3);

        FOREACH v_day IN ARRAY v_days LOOP
            IF v_sched_type = 0 THEN
                -- Turno mañana temprano
                INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, activo)
                VALUES (v_medico.id, v_day, '07:00:00', '13:00:00', true);
            ELSIF v_sched_type = 1 THEN
                -- Turno tarde hasta las 18:00
                INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, activo)
                VALUES (v_medico.id, v_day, '13:00:00', '18:00:00', true);
            ELSE
                -- Turno partido
                INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, activo)
                VALUES (v_medico.id, v_day, '08:00:00', '12:00:00', true);
                INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, activo)
                VALUES (v_medico.id, v_day, '14:00:00', '18:00:00', true);
            END IF;
        END LOOP;
    END LOOP;
END $$;
