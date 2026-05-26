with open("caja_cordes_pg.sql", "a") as f:
    f.write("""
-- SPRINT 11 (Optimización) --
CREATE INDEX idx_citas_fecha_hora ON citas(fecha_hora);
CREATE INDEX idx_citas_estado ON citas(estado);
CREATE INDEX idx_pacientes_ci ON pacientes(ci);
CREATE INDEX idx_resultados_lab_paciente ON resultados_laboratorio(paciente_id);
""")
print("Indices added")
