<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Bloqueos y Ausencias Médicas</h1>
                <p>Gestión de disponibilidad del personal médico por ausencias imprevistas.</p>
            </div>
            <a href="<?= BASE_URL ?>/ausencias/create" class="btn">+ Registrar Ausencia / Bloqueo</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">Médico</th>
                        <th style="padding: 12px;">Fecha y Hora Inicio</th>
                        <th style="padding: 12px;">Fecha y Hora Fin</th>
                        <th style="padding: 12px;">Motivo / Descripción</th>
                        <th style="padding: 12px;">Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ausencias as $aus): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><strong>Dr(a). <?= htmlspecialchars($aus['medico_email']) ?></strong></td>
                            <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($aus['fecha_inicio'])) ?></td>
                            <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($aus['fecha_fin'])) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($aus['motivo']) ?></td>
                            <td style="padding: 12px; color: var(--text-muted); font-size: 0.9em;">
                                <?= date('d/m/Y H:i', strtotime($aus['creado_en'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ausencias)): ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                No hay ausencias o bloqueos registrados hasta el momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
