<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Registrar Ausencia / Bloqueo</h1>
                <p>Bloquea la agenda de un médico por motivos de fuerza mayor. Las citas existentes se cancelarán y notificarán.</p>
            </div>
            <a href="<?= BASE_URL ?>/ausencias" class="btn btn-outline">Volver</a>
        </div>

        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px;">
            <form action="<?= BASE_URL ?>/ausencias/create" method="POST">
                
                <?php if ($rol !== 'Médico'): ?>
                    <div class="form-group">
                        <label>Seleccionar Médico</label>
                        <select name="medico_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
                            <option value="">Selecciona un médico...</option>
                            <?php foreach($medicosList as $m): ?>
                                <option value="<?= $m['id'] ?>">Dr(a). <?= htmlspecialchars($m['medico_email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Médico a Bloquear</label>
                        <input type="text" value="Dr(a). <?= htmlspecialchars($_SESSION['email']) ?>" readonly style="background: #e2e8f0;">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Fecha y Hora Inicio del Bloqueo</label>
                    <input type="datetime-local" name="fecha_inicio" required>
                </div>

                <div class="form-group">
                    <label>Fecha y Hora Fin del Bloqueo</label>
                    <input type="datetime-local" name="fecha_fin" required>
                </div>

                <div class="form-group">
                    <label>Motivo de la Ausencia (se enviará a los pacientes)</label>
                    <textarea name="motivo" rows="3" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;" placeholder="Ej: Licencia médica, viaje imprevisto, capacitación..."></textarea>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn" style="background: #dc2626; color: white;">Confirmar y Bloquear Agenda</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
