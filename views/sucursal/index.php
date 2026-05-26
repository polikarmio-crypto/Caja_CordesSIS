<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Sucursales</h1>
                <p>Gestión de las sucursales, clínicas y áreas descentralizadas de la red.</p>
            </div>
            <a href="<?= BASE_URL ?>/sucursal/create" class="btn">+ Nueva Sucursal</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px; animation: fadeIn 0.3s;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 15px 12px;">ID</th>
                        <th style="padding: 15px 12px;">Nombre</th>
                        <th style="padding: 15px 12px;">Ubicación</th>
                        <th style="padding: 15px 12px;">Horarios</th>
                        <th style="padding: 15px 12px;">Geocerca</th>
                        <th style="padding: 15px 12px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sucursales as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.01)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 15px 12px; font-weight: 500; color: var(--text-muted);">#<?= $s['id'] ?></td>
                            <td style="padding: 15px 12px; font-weight: 600; color: var(--primary-dark);"><?= htmlspecialchars($s['nombre']) ?></td>
                            <td style="padding: 15px 12px;"><?= htmlspecialchars($s['ubicacion']) ?></td>
                            <td style="padding: 15px 12px; font-size: 0.9rem; color: var(--text-muted);"><?= htmlspecialchars($s['horarios']) ?></td>
                            <td style="padding: 15px 12px; font-size: 0.85rem; font-family: monospace; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($s['limitesgeocerca'] ?: 'No definida') ?>
                            </td>
                            <td style="padding: 15px 12px;">
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; 
                                    background: <?= $s['estado'] === 'activo' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
                                    <?= htmlspecialchars(ucfirst($s['estado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sucursales)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                No hay sucursales registradas en el sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>