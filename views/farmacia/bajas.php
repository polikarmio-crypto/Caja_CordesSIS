<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Medicamentos Dados de Baja</h1>
                <p>Lista de medicamentos inactivos en el inventario.</p>
            </div>
            <a href="<?= BASE_URL ?>/farmacia" class="btn btn-outline">← Volver a Inventario</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table" style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">Código</th>
                        <th style="padding: 12px;">Medicamento</th>
                        <th style="padding: 12px;">Tipo</th>
                        <th style="padding: 12px;">Precio Unitario (Bs.)</th>
                        <th style="padding: 12px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($medicamentos as $m): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);" class="table-row-hover">
                            <td style="padding: 12px; font-family: monospace; font-weight: bold;"><?= htmlspecialchars($m['codigo_identificacion'] ?? 'N/A') ?></td>
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($m['tipo'] ?? 'N/A') ?></td>
                            <td style="padding: 12px;">Bs. <?= number_format($m['precio_unitario'], 2) ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap; align-items: center;">
                                    <!-- Restaurar -->
                                    <form action="<?= BASE_URL ?>/farmacia/restaurar" method="POST" style="margin:0;">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn" style="padding:5px 10px; font-size:0.8rem; background:#22c55e; border:none; cursor:pointer; color:white; border-radius: 6px;">
                                            Restaurar
                                        </button>
                                    </form>
                                    <!-- Eliminar Físico -->
                                    <button onclick="confirmarEliminar(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>')"
                                            class="btn" style="padding:5px 10px; font-size:0.8rem; background:#ef4444; border:none; cursor:pointer; color:white; border-radius: 6px;">
                                        Eliminar Definitivo
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($medicamentos)): ?>
                        <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay medicamentos dados de baja.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal: Confirmar Eliminación Permanente -->
<div id="modalEliminar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:5000; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
    <div style="background:#fff; border-radius:16px; padding:32px; max-width:420px; width:90%; box-shadow:0 20px 40px rgba(0,0,0,0.3); text-align:left; color: #1e293b;">
        <h2 style="margin:0 0 10px; font-size:1.3rem; color:#ef4444; text-align:center;">Eliminar Medicamento Definitivamente</h2>
        <p id="modalEliminarMsg" style="color:#64748b; margin-bottom:16px; font-weight:bold; text-align:center;"></p>
        
        <div style="font-size:0.85rem; margin-bottom:20px; padding:10px 14px; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; border-radius: 6px;">
            <strong>ATENCIÓN:</strong> Esta acción es irreversible. Se eliminará permanentemente el medicamento del inventario de farmacia de la base de datos.
        </div>
        
        <form id="formEliminar" method="POST" action="<?= BASE_URL ?>/farmacia/eliminar">
            <input type="hidden" name="id" id="eliminarMedId">
            <label style="display:flex; align-items:flex-start; gap:8px; font-size:0.85rem; margin-bottom:24px; cursor:pointer; color:#1e293b;">
                <input type="checkbox" name="confirm_delete" value="1" required style="margin-top:3px;">
                <span>Confirmo que deseo eliminar este medicamento y entiendo que no se puede deshacer.</span>
            </label>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <button type="button" onclick="document.getElementById('modalEliminar').style.display='none'"
                        class="btn btn-outline" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn" style="flex:1; background:#ef4444; border-color:#ef4444; color:white;">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminarMedId').value = id;
    document.getElementById('modalEliminarMsg').textContent = '¿Eliminar definitivamente ' + nombre + '?';
    document.getElementById('modalEliminar').style.display = 'flex';
}

// Cerrar modal al hacer clic fuera de la caja
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
