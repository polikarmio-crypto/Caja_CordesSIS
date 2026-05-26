<?php 
require_once __DIR__ . '/../layouts/header.php'; 
$conn = Database::getInstance();
$medicosList = $conn->query("SELECT m.id, u.email as medico_email FROM medicos m JOIN usuarios u ON m.usuario_id = u.id")->fetchAll();
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Registrar Horario Médico</h1>
                <p>Define el bloque de disponibilidad.</p>
            </div>
            <a href="<?= BASE_URL ?>/horarios" class="btn btn-outline">Volver</a>
        </div>

        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px;">
            <form action="<?= BASE_URL ?>/horarios/create" method="POST">
                <div class="form-group">
                    <label>Médico</label>
                    <select name="medico_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <?php foreach($medicosList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['medico_email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Día de la Semana</label>
                    <select name="dia_semana" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miercoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sabado">Sábado</option>
                        <option value="domingo">Domingo</option>
                    </select>
                </div>

                <div style="display:flex; gap: 20px;">
                    <div class="form-group" style="flex:1;">
                        <label>Hora Inicio</label>
                        <input type="time" name="hora_inicio" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Hora Fin</label>
                        <input type="time" name="hora_fin" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn">Guardar Horario</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
