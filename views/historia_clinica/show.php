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

        <!-- Navegador Interactivo de Consultas (Estructura de Lista Doblemente Enlazada) -->
        <div class="card" style="background: var(--secondary-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: var(--primary-color); font-size: 1.15em; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span>🔄</span> Navegador Bidireccional de Consultas (Doubly Linked List)
                </h3>
                <div style="font-size: 0.85em; background: rgba(0,0,0,0.05); padding: 4px 10px; border-radius: 20px; font-weight: 600; color: var(--text-muted);">
                    Nodos en Lista: <?= $historialList->getSize() ?>
                </div>
            </div>
            
            <?php if ($historialList->isEmpty()): ?>
                <p style="text-align: center; color: var(--text-muted); margin: 20px 0;">No hay consultas registradas para navegación interactiva.</p>
            <?php else: ?>
                <div id="dll-navigation-container">
                    <?php 
                    $currentNode = $historialList->getHead();
                    $index = 0;
                    while ($currentNode !== null): 
                        $data = $currentNode->data;
                        $hasPrev = ($currentNode->prev !== null);
                        $hasNext = ($currentNode->next !== null);
                        
                        $prevDate = $hasPrev ? date('d/m/Y', strtotime($currentNode->prev->data['fecha_registro'])) : 'Inicio';
                        $nextDate = $hasNext ? date('d/m/Y', strtotime($currentNode->next->data['fecha_registro'])) : 'Fin';
                    ?>
                        <div class="dll-node-card" id="node-<?= $index ?>" style="display: <?= $index === ($historialList->getSize() - 1) ? 'block' : 'none' ?>;" data-index="<?= $index ?>">
                            
                            <!-- Barra de Navegación de Nodos -->
                            <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 20px;">
                                <button type="button" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85em;" onclick="showNode(<?= $index - 1 ?>)" <?= !$hasPrev ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : '' ?> title="Ir a: <?= $prevDate ?>">
                                    &larr; Anterior (<?= $prevDate ?>)
                                </button>
                                
                                <div style="font-size: 0.9em; font-weight: 700; color: var(--text-main);">
                                    Consulta <?= $index + 1 ?> de <?= $historialList->getSize() ?>
                                </div>
                                
                                <button type="button" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85em;" onclick="showNode(<?= $index + 1 ?>)" <?= !$hasNext ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : '' ?> title="Ir a: <?= $nextDate ?>">
                                    Siguiente (<?= $nextDate ?>) &rarr;
                                </button>
                            </div>

                            <!-- Datos del Nodo Actual -->
                            <div style="background: white; border-radius: 8px; border: 1px solid var(--border-color); padding: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; align-items: center;">
                                    <span style="font-weight: bold; color: var(--primary-dark); font-size: 1.05em;">📅 Fecha Consulta: <?= date('d/m/Y H:i', strtotime($data['fecha_registro'])) ?></span>
                                    <?php if ($index === ($historialList->getSize() - 1)): ?>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 0.75em; font-weight: 600;">Consulta Más Reciente (Tail 📌)</span>
                                    <?php elseif ($index === 0): ?>
                                        <span style="background: #f3f4f6; color: #4b5563; padding: 4px 8px; border-radius: 4px; font-size: 0.75em; font-weight: 600;">Consulta Inicial (Head 📍)</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h4 style="margin: 0 0 8px 0; color: var(--primary-color); font-size: 0.95em; font-weight: 700; text-transform: uppercase;">Diagnóstico Clínico</h4>
                                <div style="background: var(--secondary-color); padding: 12px; border-radius: 6px; color: var(--text-main); font-size: 0.95em; line-height: 1.5; margin-bottom: 15px; border: 1px solid rgba(0,0,0,0.03);">
                                    <?= !empty(trim($data['diagnostico'])) ? nl2br(htmlspecialchars($data['diagnostico'])) : '<em style="color:var(--text-muted)">Sin diagnóstico registrado.</em>' ?>
                                </div>
                                
                                <h4 style="margin: 0 0 8px 0; color: var(--primary-color); font-size: 0.95em; font-weight: 700; text-transform: uppercase;">Receta / Notas Médicas</h4>
                                <div style="background: var(--secondary-color); padding: 12px; border-radius: 6px; color: var(--text-main); font-size: 0.95em; line-height: 1.5; border: 1px solid rgba(0,0,0,0.03);">
                                    <?= !empty(trim($data['receta_notas'])) ? nl2br(htmlspecialchars($data['receta_notas'])) : '<em style="color:var(--text-muted)">Sin notas de receta registradas.</em>' ?>
                                </div>
                                
                                <?php if ($data['archivo_ruta']): ?>
                                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed var(--border-color); display: flex; align-items: center; gap: 6px;">
                                        <span>📄</span> <a href="<?= BASE_URL . htmlspecialchars($data['archivo_ruta']) ?>" target="_blank" style="color: var(--primary-color); font-weight: 600; text-decoration: none; font-size: 0.9em;">Ver Documento Adjunto</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        $index++;
                        $currentNode = $currentNode->next;
                    endwhile; 
                    ?>
                </div>
                
                <script>
                    function showNode(index) {
                        const cards = document.querySelectorAll('.dll-node-card');
                        cards.forEach(card => {
                            if(parseInt(card.getAttribute('data-index')) === index) {
                                card.style.display = 'block';
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    }
                </script>
            <?php endif; ?>
        </div>

        <!-- Línea de Tiempo del Expediente Completo -->
        <div class="card">
            <?php if ($historialList->isEmpty()): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 40px 0;">No hay registros médicos para este paciente.</p>
            <?php else: ?>
                <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; color: var(--text-main);">
                    📜 Historial Completo (Orden Cronológico Inverso - Recorrido de Tail a Head)
                </h3>
                
                <?php 
                // Recorremos la Lista Doblemente Enlazada hacia atrás usando los punteros prev (desde Tail hasta Head)
                $currentNode = $historialList->getTail();
                while ($currentNode !== null): 
                    $r = $currentNode->data;
                ?>
                    <div style="border-left: 4px solid var(--primary-color); padding: 20px; background: rgba(0,0,0,0.015); margin-bottom: 20px; border-radius: 0 8px 8px 0; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center;">
                            <span style="font-weight: 700; color: var(--primary-dark); font-size: 0.95em;">Fecha Registro: <?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></span>
                        </div>
                        
                        <h4 style="margin: 0 0 6px 0; font-size: 0.9em; color: var(--text-muted); text-transform: uppercase;">Diagnóstico</h4>
                        <p style="margin: 0 0 15px 0; color: var(--text-main); font-size: 0.95em; line-height: 1.4;"><?= nl2br(htmlspecialchars($r['diagnostico'])) ?></p>
                        
                        <h4 style="margin: 0 0 6px 0; font-size: 0.9em; color: var(--text-muted); text-transform: uppercase;">Receta / Notas</h4>
                        <p style="margin: 0 0 15px 0; color: var(--text-main); font-size: 0.95em; line-height: 1.4;"><?= nl2br(htmlspecialchars($r['receta_notas'])) ?></p>
                        
                        <?php if ($r['archivo_ruta']): ?>
                            <div style="margin-top: 10px;">
                                📄 <a href="<?= BASE_URL . htmlspecialchars($r['archivo_ruta']) ?>" target="_blank" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Ver Archivo Adjunto</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php 
                    $currentNode = $currentNode->prev;
                endwhile; 
                ?>
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
