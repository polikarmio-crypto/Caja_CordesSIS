-- Script to add missing Primary Keys and Sequences in PostgreSQL for caja_cordes

-- 1. Fix facturas
CREATE SEQUENCE IF NOT EXISTS facturas_id_seq;
ALTER TABLE facturas ALTER COLUMN id SET DEFAULT nextval('facturas_id_seq');
ALTER SEQUENCE facturas_id_seq OWNED BY facturas.id;
SELECT setval('facturas_id_seq', COALESCE(MAX(id), 1)) FROM facturas;
ALTER TABLE facturas ADD PRIMARY KEY (id);

-- 2. Fix factura_detalles
CREATE SEQUENCE IF NOT EXISTS factura_detalles_id_seq;
ALTER TABLE factura_detalles ALTER COLUMN id SET DEFAULT nextval('factura_detalles_id_seq');
ALTER SEQUENCE factura_detalles_id_seq OWNED BY factura_detalles.id;
SELECT setval('factura_detalles_id_seq', COALESCE(MAX(id), 1)) FROM factura_detalles;
ALTER TABLE factura_detalles ADD PRIMARY KEY (id);

-- 3. Fix horarios_medicos
CREATE SEQUENCE IF NOT EXISTS horarios_medicos_id_seq;
ALTER TABLE horarios_medicos ALTER COLUMN id SET DEFAULT nextval('horarios_medicos_id_seq');
ALTER SEQUENCE horarios_medicos_id_seq OWNED BY horarios_medicos.id;
SELECT setval('horarios_medicos_id_seq', COALESCE(MAX(id), 1)) FROM horarios_medicos;
ALTER TABLE horarios_medicos ADD PRIMARY KEY (id);

-- 4. Fix hospitalizaciones
CREATE SEQUENCE IF NOT EXISTS hospitalizaciones_id_seq;
ALTER TABLE hospitalizaciones ALTER COLUMN id SET DEFAULT nextval('hospitalizaciones_id_seq');
ALTER SEQUENCE hospitalizaciones_id_seq OWNED BY hospitalizaciones.id;
SELECT setval('hospitalizaciones_id_seq', COALESCE(MAX(id), 1)) FROM hospitalizaciones;
ALTER TABLE hospitalizaciones ADD PRIMARY KEY (id);

-- 5. Fix notificaciones
CREATE SEQUENCE IF NOT EXISTS notificaciones_id_seq;
ALTER TABLE notificaciones ALTER COLUMN id SET DEFAULT nextval('notificaciones_id_seq');
ALTER SEQUENCE notificaciones_id_seq OWNED BY notificaciones.id;
SELECT setval('notificaciones_id_seq', COALESCE(MAX(id), 1)) FROM notificaciones;
ALTER TABLE notificaciones ADD PRIMARY KEY (id);

-- 6. Fix medico_especialidades (Composite Primary Key)
ALTER TABLE medico_especialidades ADD PRIMARY KEY (medico_id, especialidad_id);

-- 7. Fix receta_medicamentos (Composite Primary Key)
ALTER TABLE receta_medicamentos ADD PRIMARY KEY (receta_id, medicamento_id);
