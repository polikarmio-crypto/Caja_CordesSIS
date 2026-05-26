<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Inventario de Insumos Médicos</h1>
                <p>Monitoreo y administración de stock de materiales, insumos y herramientas médicas.</p>
            </div>
            <a href="<?= BASE_URL ?>/insumo/create" class="btn">+ Nuevo Insumo</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px; animation: fadeIn 0.3s;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <!-- Panel de Alertas de Stock Bajo -->
        <?php 
        $bajoStock = [];
        foreach ($insumos as $i) {
            if ($i['cantidad'] <= 10) {
                $bajoStock[] = $i;
            }
        }
        if (!empty($bajoStock)): 
        ?>
            <div style="padding: 15px; background: #fffbeb; border-left: 4px solid #f59e0b; color: #b45309; border-radius: 8px; margin-bottom: 25px; animation: fadeInUp 0.4s;">
                <h4 style="font-weight: bold; margin-bottom: 5px;">⚠️ Alerta de Stock Crítico o Bajo</h4>
                <p style="font-size: 0.9rem; margin-bottom: 10px;">Los siguientes insumos médicos tienen 10 unidades o menos en existencia y requieren reabastecimiento:</p>
                <ul style="margin-left: 20px; font-size: 0.9rem;">
                    <?php foreach ($bajoStock as $item): ?>
                        <li><strong><?= htmlspecialchars($item['nombre']) ?></strong> - Solamente quedan <strong><?= $item['cantidad'] ?></strong> unidades.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 15px 12px;">ID</th>
                        <th style="padding: 15px 12px;">Artículo</th>
                        <th style="padding: 15px 12px;">Categoría</th>
                        <th style="padding: 15px 12px;">Precio Unitario</th>
                        <th style="padding: 15px 12px; text-align: center;">Cantidad</th>
                        <th style="padding: 15px 12px; text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($insumos as $i): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(0,0,0,0.01)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 15px 12px; font-weight: 500; color: var(--text-muted);">#<?= $i['id'] ?></td>
                            <td style="padding: 15px 12px;">
                                <div style="font-weight: 600; color: var(--primary-dark);"><?= htmlspecialchars($i['nombre']) ?></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); max-width: 300px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    <?= htmlspecialchars($i['descripcion'] ?: 'Sin descripción') ?>
                                </div>
                            </td>
                            <td style="padding: 15px 12px;">
                                <span style="padding: 4px 10px; background: rgba(0, 122, 94, 0.08); color: var(--primary-color); border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
                                    <?= htmlspecialchars($i['categoria_nombre'] ?: 'General') ?>
                                </span>
                            </td>
                            <td style="padding: 15px 12px; font-weight: 500;"><?= number_format($i['precio_unitario'], 2) ?> Bs</td>
                            <td style="padding: 15px 12px; text-align: center;">
                                <span style="font-weight: bold; font-size: 1.1rem; color: <?= $i['cantidad'] <= 10 ? 'var(--danger)' : 'var(--text-main)' ?>">
                                    <?= $i['cantidad'] ?>
                                </span>
                            </td>
                            <td style="padding: 15px 12px; text-align: center;">
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; 
                                    background: <?= $i['estado'] === 'activo' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
                                    <?= htmlspecialchars(ucfirst($i['estado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($insumos)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                No hay insumos médicos en inventario actualmente.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>