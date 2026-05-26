import os

with open("caja_cordes_pg.sql", "a") as f:
    f.write("""
-- SPRINT 10 --
CREATE TABLE calificaciones (
    id SERIAL PRIMARY KEY,
    cita_id INTEGER,
    paciente_id INTEGER,
    medico_id INTEGER,
    puntuacion INTEGER CHECK (puntuacion >= 1 AND puntuacion <= 5),
    comentarios TEXT,
    fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
""")

os.makedirs("views/sucursal", exist_ok=True)
os.makedirs("views/insumo", exist_ok=True)

with open("views/sucursal/index.php", "w") as f:
    f.write("<h1>Sucursales</h1><ul><?php foreach($sucursales as $s): ?><li><?= htmlspecialchars($s['nombre']) ?></li><?php endforeach; ?></ul>")

with open("views/sucursal/create.php", "w") as f:
    f.write("<h1>Crear Sucursal</h1><form method='POST'><input name='nombre' placeholder='Nombre'><button>Crear</button></form>")

with open("views/insumo/index.php", "w") as f:
    f.write("<h1>Insumos</h1><ul><?php foreach($insumos as $i): ?><li><?= htmlspecialchars($i['nombre']) ?> - Stock: <?= $i['cantidad'] ?></li><?php endforeach; ?></ul>")

with open("views/insumo/create.php", "w") as f:
    f.write("<h1>Crear Insumo</h1><form method='POST'><input name='nombre' placeholder='Nombre'><input type='number' name='cantidad' placeholder='Cantidad'><button>Crear</button></form>")

print("Sprint 10 tables and basic views created.")
