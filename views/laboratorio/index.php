<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Resultados de Laboratorio</h1>
                <p>Historial de exámenes clínicos de los pacientes.</p>
            </div>
            <a href="<?= BASE_URL ?>/laboratorio/create" class="btn">+ Registrar Resultado</a>
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
                        <th style="padding: 12px;">Fecha</th>
                        <th style="padding: 12px;">Paciente</th>
                        <th style="padding: 12px;">Examen</th>
                        <th style="padding: 12px;">Resultado</th>
                        <th style="padding: 12px;">Valores Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resultados as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($r['fecha_resultado'])) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?> <br><small class="text-muted">CI: <?= htmlspecialchars($r['ci']) ?></small></td>
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($r['examen_nombre']) ?></strong></td>
                            <td style="padding: 12px; white-space: pre-line;"><?= htmlspecialchars($r['resultado']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($r['valores_referencia']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resultados)): ?>
                        <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay resultados registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
