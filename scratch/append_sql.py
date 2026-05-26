with open("caja_cordes_pg.sql", "a") as f:
    f.write("\n\n-- Nuevas tablas rescatadas del proyecto aleslisis --\n")
    f.write("""
CREATE TABLE sucursales (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    ubicacion VARCHAR(255),
    horarios VARCHAR(100),
    limitesgeocerca TEXT,
    estado VARCHAR(20) DEFAULT 'activo',
    id_administrador INTEGER
);

CREATE TABLE categorias_insumo (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50),
    descripcion TEXT,
    estado VARCHAR(20) DEFAULT 'activo'
);

CREATE TABLE insumos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    descripcion TEXT,
    precio_unitario NUMERIC(10,2),
    cantidad INTEGER,
    estado VARCHAR(20) DEFAULT 'activo',
    id_categoria INTEGER
);
""")
print("Tablas agregadas al SQL")
