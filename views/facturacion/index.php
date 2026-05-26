<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Facturación</h1>
                <p>Gestión de facturas y cobros de pacientes.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/facturacion/create" class="btn">+ Nueva Factura</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">ID / Nº</th>
                        <th style="padding: 12px;">Fecha Emisión</th>
                        <th style="padding: 12px;">Paciente</th>
                        <th style="padding: 12px;">Total</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facturas)): ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay facturas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach($facturas as $f): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px;"><strong><?= str_pad($f['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($f['fecha_emision'])) ?></td>
                                <td style="padding: 12px;">
                                    <?= htmlspecialchars($f['nombres'] . ' ' . $f['apellidos']) ?><br>
                                    <small style="color: var(--text-muted);">CI: <?= htmlspecialchars($f['ci']) ?></small>
                                </td>
                                <td style="padding: 12px; font-weight: bold;">$<?= number_format($f['total'], 2) ?></td>
                                <td style="padding: 12px;">
                                    <?php
                                        $color = $f['estado'] === 'pagada' ? '#16a34a' : ($f['estado'] === 'anulada' ? '#dc2626' : '#ca8a04');
                                    ?>
                                    <span style="color: <?= $color ?>; font-weight: bold; text-transform: uppercase; font-size: 0.85em;">
                                        <?= htmlspecialchars($f['estado']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; display: flex; gap: 5px;">
                                    <a href="<?= BASE_URL ?>/facturacion/pdf?id=<?= $f['id'] ?>" target="_blank" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8em;" title="Descargar PDF">PDF</a>
                                    
                                    <form action="<?= BASE_URL ?>/facturacion/status" method="POST" style="display:inline;" onsubmit="return confirm('\u00bfCambiar estado a Pagada?');">
                                        <input type="hidden" name="factura_id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="estado" value="pagada">
                                        <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.8em; background: #16a34a;" <?= $f['estado'] === 'pagada' ? 'disabled' : '' ?>>Pagar</button>
                                    </form>

                                    <form action="<?= BASE_URL ?>/facturacion/status" method="POST" style="display:inline;" onsubmit="return confirm('\u00bfEst\u00e1s seguro de anular esta factura?');">
                                        <input type="hidden" name="factura_id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="estado" value="anulada">
                                        <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.8em; background: #dc2626;" <?= $f['estado'] === 'anulada' ? 'disabled' : '' ?>>Anular</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
