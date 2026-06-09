<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Horarios Médicos</h1>
                <p>Configuración de turnos y disponibilidad de los médicos.</p>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="<?= BASE_URL ?>/horarios/bajas" class="btn btn-outline" style="font-size:0.85rem;">🗂 Dados de Baja</a>
                <a href="<?= BASE_URL ?>/horarios/create" class="btn">+ Nuevo Horario</a>
            </div>
        </div>

        <?php
        $msg = $_GET['success'] ?? '';
        if ($msg === 'baja'):?>
            <div class="alert-success">✅ Horario dado de baja. Puede restaurarlo desde "Dados de Baja".</div>
        <?php elseif ($msg === 'restaurado'):?>
            <div class="alert-success">✅ Horario restaurado correctamente.</div>
        <?php elseif ($msg === '1'):?>
            <div class="alert-success">✅ Operación realizada con éxito.</div>
        <?php endif; ?>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table" style="width:100%;text-align:left;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border-color);">
                        <th style="padding:12px;">Médico</th>
                        <th style="padding:12px;">Día de la Semana</th>
                        <th style="padding:12px;">Hora Inicio</th>
                        <th style="padding:12px;">Hora Fin</th>
                        <th style="padding:12px;text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($horarios as $h): ?>
                        <tr style="border-bottom:1px solid var(--border-color);" class="table-row-hover">
                            <td style="padding:12px;"><?= htmlspecialchars($h['medico_email']) ?></td>
                            <td style="padding:12px;text-transform:capitalize;"><?= htmlspecialchars($h['dia_semana']) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars(substr($h['hora_inicio'], 0, 5)) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars(substr($h['hora_fin'], 0, 5)) ?></td>
                            <td style="padding:12px;text-align:center;">
                                <form action="<?= BASE_URL ?>/horarios/delete" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn"
                                        style="padding:5px 10px;font-size:0.8rem;background:#f59e0b;border:none;cursor:pointer;"
                                        onclick="return confirm('¿Dar de baja este horario? Podrá restaurarlo después.');">
                                        🗑 Dar de Baja
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($horarios)): ?>
                        <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted);">No hay horarios configurados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
