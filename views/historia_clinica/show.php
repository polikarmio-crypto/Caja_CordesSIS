<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Historia Clínica</h1>
                <p>Expediente del paciente.</p>
            </div>
            <a href="<?= BASE_URL ?>/historia_clinica/create?paciente_id=<?= htmlspecialchars($paciente_id) ?>" class="btn">+ Nuevo Registro</a>
        </div>

        <div style="margin-bottom: 20px;">
            <a href="<?= BASE_URL ?>/pacientes" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.9em;">&larr; Volver a Pacientes</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                Registro médico guardado correctamente.
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($registros)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 40px 0;">No hay registros médicos para este paciente.</p>
            <?php else: ?>
                <?php foreach($registros as $r): ?>
                    <div style="border-left: 4px solid var(--primary-color); padding: 20px; background: rgba(0,0,0,0.02); margin-bottom: 20px; border-radius: 0 8px 8px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span style="font-weight: 600; color: var(--primary-dark);">Fecha: <?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></span>
                        </div>
                        
                        <h4 style="margin-bottom: 8px;">Diagnóstico</h4>
                        <p style="margin-bottom: 15px; color: var(--text-main);"><?= nl2br(htmlspecialchars($r['diagnostico'])) ?></p>
                        
                        <h4 style="margin-bottom: 8px;">Receta / Notas</h4>
                        <p style="margin-bottom: 15px; color: var(--text-main);"><?= nl2br(htmlspecialchars($r['receta_notas'])) ?></p>
                        
                        <?php if ($r['archivo_ruta']): ?>
                            <div style="margin-top: 15px;">
                                📄 <a href="<?= BASE_URL . htmlspecialchars($r['archivo_ruta']) ?>" target="_blank" style="color: var(--primary-color); font-weight: 500;">Ver Archivo Adjunto</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sección de Laboratorio -->
        <h2 style="margin-top: 30px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">Exámenes de Laboratorio</h2>
        
        <?php if(empty($resultados_lab)): ?>
            <div class="card" style="text-align: center; color: var(--text-muted); padding: 20px;">
                No hay resultados de laboratorio registrados para este paciente.
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 20px;">
                <?php foreach($resultados_lab as $rl): ?>
                    <div class="card" style="border-left: 4px solid var(--primary-color);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <strong><?= htmlspecialchars($rl['examen_nombre']) ?> (<?= htmlspecialchars($rl['tipo_muestra']) ?>)</strong>
                            <span style="color: var(--text-muted); font-size: 0.9em;">Realizado: <?= date('d/m/Y H:i', strtotime($rl['fecha_resultado'])) ?></span>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <p style="margin-bottom: 5px; color: var(--text-muted);">Resultado / Conclusión:</p>
                            <div style="padding: 10px; background: var(--secondary-color); border-radius: 8px; font-weight: 500;">
                                <?= nl2br(htmlspecialchars($rl['resultado'])) ?>
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 0.85em; color: var(--text-muted);">Valores de Referencia: <?= htmlspecialchars($rl['valores_referencia']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
