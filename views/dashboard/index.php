<?php require_once '../views/layouts/header.php'; ?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Bienvenido al Panel de Control</h1>
                <p>Estás conectado como <strong><?= htmlspecialchars($rol) ?></strong>.</p>
            </div>
            <?php if ($rol !== 'Paciente'): ?>
                <a href="<?= BASE_URL ?>/citas/create" class="btn">+ Nueva Cita</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/citas/create" class="btn">Agendar Nueva Cita</a>
            <?php endif; ?>
        </div>

        <?php if ($rol === 'Administrativo' || $rol === 'Directivo'): ?>

        <!-- KPIs Row -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
            <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); color: white; padding: 24px;">
                <p style="margin: 0 0 8px; opacity: 0.85; font-size: 0.9em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Pacientes</p>
                <div id="kpi_pacientes" style="font-size: 2.8em; font-weight: 800; line-height: 1;">...</div>
            </div>
            <div class="card" style="text-align: center; padding: 24px;">
                <p style="margin: 0 0 8px; color: var(--text-muted); font-size: 0.9em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Efectividad Agenda</p>
                <div id="kpi_efectividad" style="font-size: 2.8em; font-weight: 800; color: #16a34a; line-height: 1;">...</div>
            </div>
            <div class="card" style="text-align: center; padding: 24px;">
                <p style="margin: 0 0 8px; color: var(--text-muted); font-size: 0.9em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Tasa de Cancelación</p>
                <div id="kpi_cancelacion" style="font-size: 2.8em; font-weight: 800; color: #dc2626; line-height: 1;">...</div>
            </div>
        </div>

        <!-- Charts Row 1: Citas + Pacientes -->
        <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 24px;">
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 16px; font-size: 1em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Evolución de Citas Últimos 6 meses</h3>
                <div style="position: relative; height: 220px;">
                    <canvas id="citasChart"></canvas>
                </div>
            </div>
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 16px; font-size: 1em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Nuevos Pacientes Registrados</h3>
                <div style="position: relative; height: 220px;">
                    <canvas id="pacientesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Interanual + Export -->
        <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 20px;">
            <div class="card" style="padding: 20px;">
                <h3 style="margin: 0 0 16px; font-size: 1em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Tendencia Interanual Volumen de Citas</h3>
                <div style="position: relative; height: 200px;">
                    <canvas id="interanualChart"></canvas>
                </div>
            </div>
            <div class="card" style="padding: 24px; display: flex; flex-direction: column; justify-content: center; gap: 12px;">
                <h3 style="margin: 0 0 4px; font-size: 1em;">Exportar Reportes Gerenciales</h3>
                <p style="margin: 0 0 8px; font-size: 0.85em; color: var(--text-muted);">Descarga el resumen del período actual en el formato deseado.</p>
                <a href="<?= BASE_URL ?>/dashboard/export_pdf" class="btn" style="text-align: center;">Descargar Informe PDF</a>
                <a href="<?= BASE_URL ?>/dashboard/export_csv" class="btn btn-outline" style="text-align: center;">Exportar Datos CSV</a>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/js/chart.js"></script>
        <script>
        const chartDefaults = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

        fetch('<?= BASE_URL ?>/api/dashboard/stats')
            .then(res => res.json())
            .then(data => {
                // KPIs
                document.getElementById('kpi_pacientes').innerText = data.pacientes_total ?? 0;

                const totalCitas = data.citas.reduce((acc, c) => acc + parseInt(c.total), 0);
                const completadas = data.citas.filter(c => c.estado === 'completada').reduce((acc, c) => acc + parseInt(c.total), 0);
                const canceladas  = data.citas.filter(c => c.estado === 'cancelada').reduce((acc, c) => acc + parseInt(c.total), 0);

                document.getElementById('kpi_efectividad').innerText = totalCitas > 0 ? ((completadas / totalCitas) * 100).toFixed(1) + '%' : '0%';
                document.getElementById('kpi_cancelacion').innerText = totalCitas > 0 ? ((canceladas  / totalCitas) * 100).toFixed(1) + '%' : '0%';

                // Gráfico Citas
                const mesesCitas = [...new Set(data.citas.map(c => c.mes))];
                new Chart(document.getElementById('citasChart'), {
                    type: 'bar',
                    data: {
                        labels: mesesCitas,
                        datasets: [
                            { label: 'Completadas', data: mesesCitas.map(m => { let c = data.citas.find(x => x.mes === m && x.estado === 'completada'); return c ? c.total : 0; }), backgroundColor: 'rgba(22,163,74,0.8)', borderRadius: 6 },
                            { label: 'Canceladas',  data: mesesCitas.map(m => { let c = data.citas.find(x => x.mes === m && x.estado === 'cancelada');  return c ? c.total : 0; }), backgroundColor: 'rgba(220,38,38,0.7)', borderRadius: 6 }
                        ]
                    },
                    options: { ...chartDefaults }
                });

                // Gráfico Pacientes
                if (data.crecimiento_pacientes && data.crecimiento_pacientes.length > 0) {
                    new Chart(document.getElementById('pacientesChart'), {
                        type: 'line',
                        data: {
                            labels: data.crecimiento_pacientes.map(c => c.mes),
                            datasets: [{ label: 'Nuevos Pacientes', data: data.crecimiento_pacientes.map(c => c.total), borderColor: '#007a5e', backgroundColor: 'rgba(0,122,94,0.15)', fill: true, tension: 0.4, pointRadius: 5 }]
                        },
                        options: { ...chartDefaults }
                    });
                } else {
                    document.getElementById('pacientesChart').closest('div').innerHTML = '<p style="text-align:center; color:var(--text-muted); padding-top:80px;">Sin datos de nuevos pacientes este período.</p>';
                }

                // Gráfico Interanual
                if (data.interanual && data.interanual.length > 0) {
                    new Chart(document.getElementById('interanualChart'), {
                        type: 'bar',
                        data: {
                            labels: data.interanual.map(c => 'Año ' + c.anio),
                            datasets: [{ label: 'Citas', data: data.interanual.map(c => c.total), backgroundColor: ['rgba(202,138,4,0.8)', 'rgba(0,122,94,0.8)'], borderRadius: 6 }]
                        },
                        options: { ...chartDefaults, plugins: { legend: { display: false } } }
                    });
                } else {
                    document.getElementById('interanualChart').closest('div').innerHTML = '<p style="text-align:center; color:var(--text-muted); padding-top:70px;">Sin datos interanuales disponibles.</p>';
                }
            });
        </script>

        <?php else: ?>
            <?php if ($rol === 'Paciente'): ?>
                <?php 
                $paciente_id = $_SESSION['paciente_id'] ?? null;
                $past_citas = [];
                if ($paciente_id) {
                    $conn = Database::getInstance();
                    $stmt = $conn->prepare("
                        SELECT c.*, u.email as medico_email,
                               (SELECT puntuacion FROM calificaciones WHERE cita_id = c.id LIMIT 1) as rating,
                               (SELECT comentarios FROM calificaciones WHERE cita_id = c.id LIMIT 1) as rating_comment
                        FROM citas c
                        JOIN medicos m ON c.medico_id = m.id
                        JOIN usuarios u ON m.usuario_id = u.id
                        WHERE c.paciente_id = :pid
                        ORDER BY c.fecha_hora DESC
                    ");
                    $stmt->execute([':pid' => $paciente_id]);
                    $past_citas = $stmt->fetchAll();
                }
                ?>
                <div class="card recent-activity" style="margin-bottom: 24px;">
                    <h3>Historial de tus Citas y Consultas</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Aquí puedes ver tus citas pasadas y programadas. Valora la atención recibida por nuestros profesionales médicos.</p>
                    
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px;">Fecha y Hora</th>
                                <th style="padding: 12px;">Médico</th>
                                <th style="padding: 12px;">Motivo</th>
                                <th style="padding: 12px;">Estado</th>
                                <th style="padding: 12px; text-align: center;">Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($past_citas as $c): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;">
                                        <strong><?= date('d/m/Y', strtotime($c['fecha_hora'])) ?></strong><br>
                                        <span style="color: var(--text-muted); font-size: 0.9em;"><?= date('H:i', strtotime($c['fecha_hora'])) ?></span>
                                    </td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($c['medico_email']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($c['motivo']) ?></td>
                                    <td style="padding: 12px;">
                                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.85em; 
                                            <?= $c['estado'] === 'pendiente' ? 'background: #fef08a; color: #854d0e;' : 
                                               ($c['estado'] === 'completada' ? 'background: #bbf7d0; color: #166534;' : 'background: #fecaca; color: #991b1b;') ?>">
                                            <?= ucfirst(htmlspecialchars($c['estado'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if ($c['estado'] === 'completada'): ?>
                                            <?php if ($c['rating'] !== null): ?>
                                                <div style="font-weight: 600; color: #eab308; font-size: 1.1rem;" title="<?= htmlspecialchars($c['rating_comment']) ?>">
                                                    <?php for($i=1; $i<=5; $i++) echo $i <= $c['rating'] ? '★' : '☆'; ?>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; background: var(--primary-color); color: white;" 
                                                        onclick="openRatingModal(<?= $c['id'] ?>, <?= $c['medico_id'] ?>, '<?= htmlspecialchars($c['medico_email']) ?>')">
                                                    ⭐ Calificar
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($past_citas)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">
                                        No has agendado ninguna cita médica hasta el momento.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal de Calificación -->
                <div id="ratingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
                    <div style="background:white; padding:30px; border-radius:16px; width:440px; box-shadow: var(--shadow-lg); animation: fadeInUp 0.3s;">
                        <h2 style="margin-bottom:10px; color:var(--primary-dark); display: flex; align-items: center; gap: 8px;">
                            ⭐ Valorar Atención
                        </h2>
                        <p style="margin-bottom:20px; color: var(--text-muted);">Valora la consulta con el médico <strong id="rating_medico_email"></strong>.</p>
                        
                        <form action="<?= BASE_URL ?>/calificaciones/create" method="POST">
                            <input type="hidden" name="cita_id" id="rating_cita_id" value="">
                            <input type="hidden" name="medico_id" id="rating_medico_id" value="">
                            
                            <div class="form-group" style="margin-bottom: 20px; text-align: center;">
                                <label style="display: block; margin-bottom: 10px;">Tu Puntuación</label>
                                <div style="display: inline-flex; flex-direction: row-reverse; gap: 8px; justify-content: center;">
                                    <?php for($i=5; $i>=1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="puntuacion" value="<?= $i ?>" style="display: none;" <?= $i===5 ? 'checked' : '' ?>>
                                        <label for="star<?= $i ?>" class="star-label" style="font-size: 2.2rem; color: #cbd5e1; cursor: pointer; transition: color 0.2s;">★</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="comentarios">Comentarios / Sugerencias</label>
                                <textarea name="comentarios" id="comentarios" rows="3" required style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background: var(--secondary-color); font-family: inherit;" placeholder="Comparte tu experiencia con este profesional..."></textarea>
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 25px;">
                                <button type="button" class="btn btn-outline" onclick="closeRatingModal()">Cancelar</button>
                                <button type="submit" class="btn">Guardar Calificación</button>
                            </div>
                        </form>
                    </div>
                </div>

                <style>
                    /* Star Rating Hover & Active Logic */
                    .star-label:hover,
                    .star-label:hover ~ .star-label,
                    input[type="radio"]:checked ~ .star-label {
                        color: #eab308 !important;
                    }
                </style>

                <script>
                    function openRatingModal(citaId, medicoId, medicoEmail) {
                        document.getElementById('rating_cita_id').value = citaId;
                        document.getElementById('rating_medico_id').value = medicoId;
                        document.getElementById('rating_medico_email').innerText = medicoEmail;
                        document.getElementById('ratingModal').style.display = 'flex';
                    }
                    function closeRatingModal() {
                        document.getElementById('ratingModal').style.display = 'none';
                    }
                </script>

            <?php else: ?>
                <div class="card recent-activity">
                    <h3>Actividad Reciente</h3>
                    <p style="color: var(--text-muted);">El sistema está operativo. Utiliza el menú lateral para navegar por los distintos módulos.</p>

                    <?php if ($rol === 'Médico'): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(0,122,94,0.1); border-left: 4px solid var(--primary-color); border-radius: 6px;">
                            <strong>Aviso:</strong> Consulta tu agenda para ver las citas pendientes del día.
                        </div>
                    <?php elseif (strpos($rol, 'Farmac') !== false): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(202,138,4,0.1); border-left: 4px solid #ca8a04; border-radius: 6px;">
                            <strong>Aviso:</strong> Revisa las recetas pendientes de despacho en el módulo de Farmacia.
                        </div>
                    <?php elseif ($rol === 'Laboratorista'): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(0,122,94,0.1); border-left: 4px solid var(--primary-color); border-radius: 6px;">
                            <strong>Aviso:</strong> Registra los resultados de laboratorio desde el módulo correspondiente.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require_once '../views/layouts/footer.php'; ?>

