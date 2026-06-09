<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Pacientes Dados de Baja</h1>
                <p>Registros inactivos. Puede restaurarlos o eliminarlos permanentemente.</p>
            </div>
            <a href="<?= BASE_URL ?>/pacientes" class="btn btn-outline">← Volver a Pacientes Activos</a>
        </div>

        <?php if (empty($pacientes)): ?>
            <div class="card" style="text-align:center;padding:40px;color:var(--text-muted);">
                <div style="font-size:3rem;margin-bottom:12px;">✅</div>
                <p>No hay pacientes dados de baja.</p>
            </div>
        <?php else: ?>
        <div class="card" style="overflow-x:auto;">
            <table class="data-table" style="width:100%;text-align:left;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border-color);">
                        <th style="padding:12px;">CI</th>
                        <th style="padding:12px;">Nombres</th>
                        <th style="padding:12px;">Apellidos</th>
                        <th style="padding:12px;">Teléfono</th>
                        <th style="padding:12px;text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pacientes as $p): ?>
                    <tr style="border-bottom:1px solid var(--border-color);opacity:0.8;" class="table-row-hover">
                        <td style="padding:12px;"><?= htmlspecialchars($p['ci']) ?></td>
                        <td style="padding:12px;"><?= htmlspecialchars($p['nombres']) ?></td>
                        <td style="padding:12px;"><?= htmlspecialchars($p['apellidos']) ?></td>
                        <td style="padding:12px;"><?= htmlspecialchars($p['telefono'] ?? '') ?></td>
                        <td style="padding:12px;text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                <!-- Restaurar -->
                                <form method="POST" action="<?= BASE_URL ?>/pacientes/restaurar" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn" style="padding:5px 10px;font-size:0.8rem;background:#22c55e;border-color:#22c55e;border:none;cursor:pointer;">
                                        ♻️ Restaurar
                                    </button>
                                </form>
                                <!-- Eliminar permanente -->
                                <button onclick="confirmarEliminar(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombres'].' '.$p['apellidos'], ENT_QUOTES) ?>')"
                                        class="btn" style="padding:5px 10px;font-size:0.8rem;background:#ef4444;border-color:#ef4444;border:none;cursor:pointer;">
                                    🗑 Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal: Confirmar Eliminación Permanente -->
<div id="modalEliminar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:5000;justify-content:center;align-items:center;">
    <div style="background:var(--card-bg,#fff);border-radius:16px;padding:32px;max-width:440px;width:90%;box-shadow:0 20px 40px rgba(0,0,0,0.4);text-align:center;">
        <div style="font-size:3.5rem;margin-bottom:16px;">🚨</div>
        <h2 style="margin:0 0 10px;font-size:1.3rem;color:#ef4444;">Eliminar Permanentemente</h2>
        <p id="modalEliminarMsg" style="color:var(--text-muted);margin-bottom:12px;"></p>
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:24px;">
            <p style="color:#991b1b;font-size:0.9rem;margin:0;">
                ⚠️ <strong>Esta acción es irreversible.</strong> Todos los datos del paciente y su historial serán eliminados definitivamente.
            </p>
        </div>
        <form id="formEliminar" method="POST" action="<?= BASE_URL ?>/pacientes/eliminar">
            <input type="hidden" name="id" id="eliminarPacienteId">
            <input type="hidden" name="confirm_delete" value="1">
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <button type="button" onclick="document.getElementById('modalEliminar').style.display='none'"
                        class="btn btn-outline" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn" style="flex:1;background:#ef4444;border-color:#ef4444;">
                    Eliminar Definitivamente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminarPacienteId').value = id;
    document.getElementById('modalEliminarMsg').textContent = '¿Eliminar definitivamente a ' + nombre + '?';
    document.getElementById('modalEliminar').style.display = 'flex';
}
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
