<?php 
require_once __DIR__ . '/../layouts/header.php'; 
// Obtener listas de pacientes y médicos
$conn = Database::getInstance();
$pacientesList = $conn->query("SELECT id, nombres, apellidos, CI FROM pacientes")->fetchAll();
$medicosList = $conn->query("
    SELECT m.id, u.email as medico_email, STRING_AGG(DISTINCT e.nombre, ', ') as especialidad,
           COALESCE(AVG(cal.puntuacion), 0) as avg_rating,
           COUNT(cal.id) as total_ratings,
           (
               SELECT STRING_AGG(hm.dia_semana || ' (' || TO_CHAR(hm.hora_inicio, 'HH24:MI') || '-' || TO_CHAR(hm.hora_fin, 'HH24:MI') || ')', ', ' ORDER BY 
                   CASE hm.dia_semana
                       WHEN 'lunes' THEN 1
                       WHEN 'martes' THEN 2
                       WHEN 'miercoles' THEN 3
                       WHEN 'jueves' THEN 4
                       WHEN 'viernes' THEN 5
                       WHEN 'sabado' THEN 6
                       WHEN 'domingo' THEN 7
                       ELSE 8
                   END
               )
               FROM horarios_medicos hm
               WHERE hm.medico_id = m.id AND hm.activo = TRUE
           ) as horarios
    FROM medicos m 
    JOIN usuarios u ON m.usuario_id = u.id 
    LEFT JOIN medico_especialidades me ON m.id = me.medico_id
    LEFT JOIN especialidades e ON me.especialidad_id = e.id
    LEFT JOIN calificaciones cal ON m.id = cal.medico_id
    GROUP BY m.id, u.email
")->fetchAll();

$logged_paciente_id = $_SESSION['paciente_id'] ?? null;
$is_paciente = ($_SESSION['rol_nombre'] ?? '') === 'Paciente';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Agendar Cita</h1>
                <p>Programa una nueva consulta médica.</p>
            </div>
            <a href="<?= BASE_URL ?>/citas" class="btn btn-outline">Volver</a>
        </div>

        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 800px;">
            <form action="<?= BASE_URL ?>/citas/create" method="POST">
                <div class="form-group">
                    <label>Paciente</label>
                    <select name="paciente_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="">Selecciona un paciente...</option>
                        <?php foreach($pacientesList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($is_paciente && $p['id'] == $logged_paciente_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' - CI: ' . $p['ci']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Médico</label>
                    <select name="medico_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="">Selecciona un médico...</option>
                        <?php foreach($medicosList as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['medico_email'] . ' (' . ($m['especialidad'] ?: 'General') . ')') ?>
                                <?= $m['total_ratings'] > 0 ? ' - ⭐ ' . number_format($m['avg_rating'], 1) . ' (' . $m['total_ratings'] . ' valoraciones)' : ' - (Sin valoraciones)' ?>
                                <?= !empty($m['horarios']) ? ' - Horarios: ' . htmlspecialchars($m['horarios']) : ' - Sin horarios registrados' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <div class="form-group">
                    <label>Modalidad de Consulta</label>
                    <select name="modalidad" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="presencial">Presencial (En Consultorio)</option>
                        <option value="virtual">Virtual (Telemedicina via Google Meet)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tipo de Cita</label>
                    <select name="tipo" id="tipoCita" required onchange="toggleHorarioInfo(this.value)" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="normal">&#x1F4C5; Cita Normal (valida horario del médico)</option>
                        <option value="emergencia">&#x1F6A8; Emergencia (atención 24/7 — sin restricción de horario)</option>
                    </select>
                </div>

                <div id="infoHorarioNormal" style="padding: 12px 16px; background: var(--secondary-color); border-left: 4px solid #00ba8b; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; color: var(--text-muted);">
                    &#x2139;&#xFE0F; Las citas normales se agendan dentro del turno laboral del médico (por lo general hasta las 17:00). Puedes ver los horarios registrados en <a href="<?= BASE_URL ?>/horarios" style="color: #00ba8b;">Horarios Médicos</a>.
                </div>

                <div id="infoHorarioEmergencia" style="display:none; padding: 12px 16px; background: #fff3cd; border-left: 4px solid #f59e0b; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; color: #92400e;">
                    &#x26A0;&#xFE0F; <strong>Emergencia:</strong> Se puede agendar a cualquier hora del día. El médico será contactado independientemente de su turno habitual.
                </div>

                <div class="form-group">
                    <label>Fecha y Hora</label>
                    <input type="datetime-local" name="fecha_hora" required>
                </div>

                <div class="form-group">
                    <label>Motivo de Consulta</label>
                    <textarea name="motivo" rows="3" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;"></textarea>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn">Agendar Cita</button>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
function toggleHorarioInfo(tipo) {
    document.getElementById('infoHorarioNormal').style.display     = (tipo === 'normal')      ? 'block' : 'none';
    document.getElementById('infoHorarioEmergencia').style.display = (tipo === 'emergencia')  ? 'block' : 'none';
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
