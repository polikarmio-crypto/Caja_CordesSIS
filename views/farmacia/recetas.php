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

        <?php 
        $colaVacia = $recetaQueue->isEmpty();
        $totalCola = $recetaQueue->getSize();
        ?>

        <!-- Tarjeta de Estado de Cola de Despacho -->
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; margin-bottom: 25px; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-md);">
            <div>
                <h3 style="margin: 0; font-size: 1.3em; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span>📋</span> Cola de Despacho de Recetas (FIFO)
                </h3>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 0.9em;">El sistema gestiona automáticamente el orden de atención según la hora de creación.</p>
            </div>
            <div style="text-align: right; background: rgba(255,255,255,0.15); padding: 10px 20px; border-radius: 10px; backdrop-filter: blur(5px);">
                <span style="font-size: 2em; font-weight: 800; line-height: 1;"><?= $totalCola ?></span>
                <p style="margin: 3px 0 0 0; font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9;">Recetas en Espera</p>
            </div>
        </div>

        <?php if($colaVacia): ?>
            <div class="card" style="text-align: center; color: var(--text-muted); padding: 40px;">
                No hay recetas pendientes por despachar en la cola.
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));">
                <?php 
                $recetas = $recetaQueue->toArray();
                $pos = 1;
                foreach($recetas as $r): 
                    $esPrimero = ($pos === 1);
                    $colorTema = $esPrimero ? '#10b981' : '#f59e0b';
                    $bgColorTema = $esPrimero ? '#ecfdf5' : '#fffbeb';
                    $textColorTema = $esPrimero ? '#065f46' : '#92400e';
                    $turnoTexto = $esPrimero ? '⏳ Siguiente en Turno (Cabeza de Cola)' : "👥 En Cola - Posición #$pos";
                ?>
                    <div class="card" style="border-top: 4px solid <?= $colorTema ?>; transition: all 0.2s ease; position: relative;">
                        
                        <!-- Badge de Turno FIFO -->
                        <div style="position: absolute; top: 15px; right: 15px; background: <?= $bgColorTema ?>; color: <?= $textColorTema ?>; border: 1px solid <?= $colorTema ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.75em; font-weight: 600; letter-spacing: 0.02em;">
                            <?= $turnoTexto ?>
                        </div>

                        <div style="margin-bottom: 15px; padding-right: 180px;">
                            <strong style="font-size: 1.1em;">Receta #<?= $r['receta_id'] ?></strong>
                            <div style="font-size: 0.8em; color: var(--text-muted); margin-top: 2px;">
                                Emitida: <?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])) ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                            <p style="margin: 0; font-size: 0.85em; color: var(--text-muted);">Paciente:</p>
                            <p style="margin: 2px 0 0 0; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($r['nombres'] . ' ' . $r['apellidos']) ?> <small style="font-weight: normal; color: var(--text-muted);">(CI: <?= htmlspecialchars($r['ci']) ?>)</small></p>
                            <p style="margin: 4px 0 0 0; font-size: 0.8em; color: var(--text-muted);">Médico: <?= htmlspecialchars($r['medico_email']) ?></p>
                        </div>

                        <form action="<?= BASE_URL ?>/farmacia/despachar" method="POST">
                            <input type="hidden" name="receta_id" value="<?= $r['receta_id'] ?>">
                            <div style="background: var(--secondary-color); padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--border-color);">
                                <p style="font-size: 0.85em; font-weight: bold; margin-bottom: 8px; color: var(--primary-dark);">Medicamentos a Despachar:</p>
                                <?php foreach($r['medicamentos'] as $med): 
                                    $sugerido = (int)$med['duracion_dias'] * 3;
                                ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 6px 0;">
                                        <div style="flex: 1; padding-right: 10px;">
                                            <div style="font-weight: 600; font-size: 0.9em; color: var(--text-main);"><?= htmlspecialchars($med['nombre']) ?></div>
                                            <div style="font-size: 0.75em; color: var(--text-muted);"><?= htmlspecialchars($med['dosis']) ?> - <?= htmlspecialchars($med['frecuencia']) ?> por <?= $med['duracion_dias'] ?> días.</div>
                                        </div>
                                        <div style="width: 70px;">
                                            <input type="number" name="cantidades[<?= $med['medicamento_id'] ?>]" value="<?= $sugerido ?>" min="1" max="<?= $med['stock'] ?>" style="width: 100%; padding: 4px; text-align: center; border-radius: 4px; border: 1px solid var(--border-color); font-weight: 600;" required>
                                        </div>
                                        <div style="font-size: 0.75em; color: <?= $med['stock'] < $sugerido ? '#dc2626' : '#16a34a' ?>; margin-left: 10px; font-weight: 500; min-width: 65px; text-align: right;">
                                            Stock: <?= $med['stock'] ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit" class="btn" style="width: 100%; background: <?= $colorTema ?>; border-color: <?= $colorTema ?>; color: white;" onclick="return confirm('¿Confirmar el despacho de estos medicamentos? Se descontarán del inventario.')">
                                <?= $esPrimero ? '⚡ Despachar Siguiente Receta' : 'Despachar Receta' ?>
                            </button>
                        </form>
                    </div>
                <?php 
                $pos++;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>

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
