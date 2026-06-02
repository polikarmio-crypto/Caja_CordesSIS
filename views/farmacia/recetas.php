<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Despacho de Recetas</h1>
                <p>Órdenes médicas pendientes por entregar.</p>
            </div>
            <a href="<?= BASE_URL ?>/farmacia" class="btn btn-outline">Ir a Inventario</a>
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

        <?php if(empty($recetas)): ?>
            <div class="card" style="text-align: center; color: var(--text-muted); padding: 40px;">
                No hay recetas pendientes por despachar.
            </div>
        <?php endif; ?>

        <div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));">
            <?php foreach($recetas as $r): ?>
                <div class="card" style="border-top: 4px solid var(--accent-color);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Receta #<?= $r['receta_id'] ?></strong>
                        <span style="font-size: 0.85em; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])) ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <p style="margin: 0; font-size: 0.9em; color: var(--text-muted);">Paciente:</p>
                        <p style="margin: 0; font-weight: bold;"><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?> <small>(CI: <?= htmlspecialchars($r['ci']) ?>)</small></p>
                    </div>

                    <form action="<?= BASE_URL ?>/farmacia/despachar" method="POST">
                        <input type="hidden" name="receta_id" value="<?= $r['receta_id'] ?>">
                        <div style="background: var(--secondary-color); padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                            <p style="font-size: 0.85em; font-weight: bold; margin-bottom: 5px;">Medicamentos a Despachar:</p>
                            <?php foreach($r['medicamentos'] as $med): 
                                // Calculo simple: asume 1 unidad por toma si no se especifica
                                // Para demo, descontaremos la duracion * 3 (asumiendo 3 tomas al dia) como valor sugerido
                                $sugerido = (int)$med['duracion_dias'] * 3;
                            ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding: 5px 0;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500; font-size: 0.9em;"><?= htmlspecialchars($med['nombre']) ?></div>
                                        <div style="font-size: 0.75em; color: var(--text-muted);"><?= htmlspecialchars($med['dosis']) ?> - <?= htmlspecialchars($med['frecuencia']) ?> por <?= $med['duracion_dias'] ?> días.</div>
                                    </div>
                                    <div style="width: 80px;">
                                        <input type="number" name="cantidades[<?= $med['medicamento_id'] ?>]" value="<?= $sugerido ?>" min="1" max="<?= $med['stock'] ?>" style="width: 100%; padding: 5px; text-align: center; border-radius: 4px; border: 1px solid var(--border-color);" required>
                                    </div>
                                    <div style="font-size: 0.75em; color: <?= $med['stock'] < $sugerido ? '#dc2626' : '#16a34a' ?>; margin-left: 10px;">
                                        Stock: <?= $med['stock'] ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;" onclick="return confirm('¿Confirmar el despacho de estos medicamentos? Se descontarán del inventario.')">Despachar y Descontar Stock</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-header" style="margin-top: 40px; border-top: 2px solid var(--border-color); padding-top: 20px;">
            <div class="welcome-text">
                <h2>Historial de Despachos</h2>
                <p>Recetas que ya han sido entregadas al paciente.</p>
            </div>
        </div>

        <?php if(empty($despachadas)): ?>
            <div class="card" style="text-align: center; color: var(--text-muted); padding: 40px;">
                No hay recetas despachadas aún.
            </div>
        <?php endif; ?>

        <div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));">
            <?php foreach($despachadas as $r): ?>
                <div class="card" style="border-top: 4px solid #16a34a;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong>Receta #<?= $r['receta_id'] ?> <span style="color:#16a34a;">(Entregada)</span></strong>
                        <span style="font-size: 0.85em; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])) ?></span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <p style="margin: 0; font-size: 0.9em; color: var(--text-muted);">Paciente:</p>
                        <p style="margin: 0; font-weight: bold;"><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?> <small>(CI: <?= htmlspecialchars($r['ci']) ?>)</small></p>
                    </div>

                    <a href="<?= BASE_URL ?>/farmacia/comprobante?id=<?= $r['receta_id'] ?>" target="_blank" class="btn btn-outline" style="width: 100%; text-align: center;">⬇️ Descargar Comprobante PDF</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
