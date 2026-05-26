USE caja_cordes;

-- Insert roles
INSERT INTO roles (nombre) VALUES 
('Administrativo'), 
('Médico'), 
('Directivo'), 
('Paciente');

-- Insert specialties
INSERT INTO especialidades (nombre) VALUES 
('Medicina General'), 
('Pediatría'), 
('Traumatología'), 
('Cardiología');

-- Insert a test admin user (password is 'admin123')
INSERT INTO usuarios (rol_id, email, password_hash) VALUES 
(1, 'admin@cajacordes.com', '$2y$10$Iq3Z9YZLVAmxlGzyJ80zl.Byy18hGxCxzYAb11iyws4X80MXntDt2');

-- Insert a test medic (password is 'medico123')
-- $2y$10$mDBT4F0l3K1e08B/bQ.z1eP0Z.Hk5QvU/nOoR4.Zt3A07S7Xz8/hG (medico123)
INSERT INTO usuarios (rol_id, email, password_hash) VALUES 
(2, 'medico@cajacordes.com', '$2y$10$fUSsFTdI/bI5pSq2N9FXjuuA1qz.VVOX1HRGqlw/h5PUDnJ0RmuLm');

-- Insert initial patient user (password 'paciente123')
INSERT INTO usuarios (rol_id, email, password_hash) VALUES 
(4, 'paciente@cajacordes.com', '$2y$10$as.izYOsvtj32241r.y9O.L2zSGGW5DOUEGi8XT6GG7keOjCS.soC');

-- Link users to specific profiles
INSERT INTO medicos (usuario_id, licencia_medica) VALUES ((SELECT id FROM usuarios WHERE email='medico@cajacordes.com'), 'MED-12345');
INSERT INTO medico_especialidades (medico_id, especialidad_id) VALUES ((SELECT id FROM medicos WHERE licencia_medica='MED-12345'), 1);

INSERT INTO pacientes (usuario_id, dni, nombres, apellidos, fecha_nac) VALUES ((SELECT id FROM usuarios WHERE email='paciente@cajacordes.com'), '12345678', 'Juan', 'Perez', '1990-01-01');
INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES ((SELECT id FROM pacientes WHERE dni='12345678'), '555-1234');

