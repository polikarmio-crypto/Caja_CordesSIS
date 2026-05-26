<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Citas Médicas</h1>
                <p>Gestión de agenda de citas.</p>
            </div>
            <a href="<?= BASE_URL ?>/citas/create" class="btn">+ Nueva Cita</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                Operación realizada con éxito.
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px;">
            <form action="<?= BASE_URL ?>/citas" method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label style="font-size: 0.85em; color: var(--text-muted);">Desde</label>
                    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                </div>
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label style="font-size: 0.85em; color: var(--text-muted);">Hasta</label>
                    <input type="date" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                </div>
                <div>
                    <button type="submit" class="btn">Filtrar Agenda</button>
                    <a href="<?= BASE_URL ?>/citas" class="btn btn-outline">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">Fecha y Hora</th>
                        <th style="padding: 12px;">Paciente</th>
                        <th style="padding: 12px;">Médico</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px;">Motivo</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($citas as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;">
                                <strong><?= date('d/m/Y', strtotime($c['fecha_hora'])) ?></strong><br>
                                <span style="color: var(--text-muted); font-size: 0.9em;"><?= date('H:i', strtotime($c['fecha_hora'])) ?></span>
                            </td>
                            <td style="padding: 12px;"><?= htmlspecialchars($c['paciente_nombres'] . ' ' . $c['paciente_apellidos']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($c['medico_email']) ?></td>
                            <td style="padding: 12px;">
                                <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.85em; 
                                    <?= $c['estado'] === 'pendiente' ? 'background: #fef08a; color: #854d0e;' : 
                                       ($c['estado'] === 'completada' ? 'background: #bbf7d0; color: #166534;' : 'background: #fecaca; color: #991b1b;') ?>">
                                    <?= ucfirst(htmlspecialchars($c['estado'])) ?>
                                </span>
                            </td>
                            <td style="padding: 12px;"><?= htmlspecialchars($c['motivo']) ?></td>
                            <td style="padding: 12px;">
                                <?php if($c['estado'] === 'pendiente'): ?>
                                    <div style="display:flex; gap:5px;">
                                        <form action="<?= BASE_URL ?>/citas/completar" method="POST" style="display:inline;">
                                            <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-outline" style="color:#16a34a; border-color:#16a34a; padding:6px 10px; font-size:0.8rem;">Completar</button>
                                        </form>
                                        <button class="btn btn-outline" style="color: red; border-color: red; padding:6px 10px; font-size:0.8rem;" onclick="openCancelModal(<?= $c['id'] ?>)">Cancelar</button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($citas)): ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay citas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal de Cancelacion -->
<div id="cancelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s;">
        <h2 style="margin-bottom:15px; color:var(--danger-color, #e3342f);">Cancelar Cita</h2>
        <p style="margin-bottom:15px;">¿Estás seguro de que deseas cancelar esta cita? Esta acción notificará al paciente.</p>
        <form action="<?= BASE_URL ?>/citas/cancel" method="POST">
            <input type="hidden" name="cita_id" id="cancel_cita_id" value="">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Motivo de cancelación (Opcional)</label>
                <textarea name="motivo_cancelacion" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeCancelModal()">Volver</button>
                <button type="submit" class="btn" style="background:#e3342f; color:white;">Confirmar Cancelación</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(id) {
    document.getElementById('cancel_cita_id').value = id;
    document.getElementById('cancelModal').style.display = 'flex';
}
function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
