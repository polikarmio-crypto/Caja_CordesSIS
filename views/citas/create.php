<?php 
require_once __DIR__ . '/../layouts/header.php'; 
// Obtener listas de pacientes y médicos
$conn = Database::getInstance();
$pacientesList = $conn->query("SELECT id, nombres, apellidos, CI FROM pacientes")->fetchAll();
$medicosList = $conn->query("
    SELECT m.id, u.email as medico_email, STRING_AGG(DISTINCT e.nombre, ', ') as especialidad,
           COALESCE(AVG(cal.puntuacion), 0) as avg_rating,
           COUNT(cal.id) as total_ratings
    FROM medicos m 
    JOIN usuarios u ON m.usuario_id = u.id 
    LEFT JOIN medico_especialidades me ON m.id = me.medico_id
    LEFT JOIN especialidades e ON me.especialidad_id = e.id
    LEFT JOIN calificaciones cal ON m.id = cal.medico_id
    GROUP BY m.id, u.email
")->fetchAll();
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
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' - CI: ' . $p['ci']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Médico</label>
                    <select name="medico_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="">Selecciona un médico...</option>
                        <?php foreach($medicosList as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['medico_email'] . ' (' . $m['especialidad'] . ')') ?>
                                <?= $m['total_ratings'] > 0 ? ' - ⭐ ' . number_format($m['avg_rating'], 1) . ' (' . $m['total_ratings'] . ' valoraciones)' : ' - (Sin valoraciones)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
