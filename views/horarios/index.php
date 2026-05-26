<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Horarios Médicos</h1>
                <p>Configuración de turnos y disponibilidad de los médicos.</p>
            </div>
            <a href="<?= BASE_URL ?>/horarios/create" class="btn">+ Nuevo Horario</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                Operación realizada con éxito.
            </div>
        <?php endif; ?>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">Médico</th>
                        <th style="padding: 12px;">Día de la Semana</th>
                        <th style="padding: 12px;">Hora Inicio</th>
                        <th style="padding: 12px;">Hora Fin</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($horarios as $h): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><?= htmlspecialchars($h['medico_email']) ?></td>
                            <td style="padding: 12px; text-transform: capitalize;"><?= htmlspecialchars($h['dia_semana']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars(substr($h['hora_inicio'], 0, 5)) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars(substr($h['hora_fin'], 0, 5)) ?></td>
                            <td style="padding: 12px;">
                                <form action="<?= BASE_URL ?>/horarios/delete" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="color: red; border-color: red; padding: 4px 8px; font-size: 0.85em;" onclick="return confirm('¿Seguro que deseas eliminar este horario?');">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($horarios)): ?>
                        <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay horarios configurados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
