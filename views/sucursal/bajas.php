<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Sucursales Dadas de Baja</h1>
                <p>Lista de sucursales o clínicas inactivas en el sistema.</p>
            </div>
            <a href="<?= BASE_URL ?>/sucursal" class="btn btn-outline">← Volver a Sucursales</a>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'eliminado'): ?>
            <div class="alert-success">✅ Sucursal eliminada permanentemente de la base de datos.</div>
        <?php endif; ?>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table" style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 15px 12px;">ID</th>
                        <th style="padding: 15px 12px;">Nombre</th>
                        <th style="padding: 15px 12px;">Ubicación</th>
                        <th style="padding: 15px 12px;">Horarios</th>
                        <th style="padding: 15px 12px;">Geocerca</th>
                        <th style="padding: 15px 12px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sucursales as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);" class="table-row-hover">
                            <td style="padding: 15px 12px; font-weight: 500; color: var(--text-muted);">#<?= $s['id'] ?></td>
                            <td style="padding: 15px 12px; font-weight: 600; color: var(--primary-dark);"><?= htmlspecialchars($s['nombre']) ?></td>
                            <td style="padding: 15px 12px;"><?= htmlspecialchars($s['ubicacion']) ?></td>
                            <td style="padding: 15px 12px; font-size: 0.9rem; color: var(--text-muted);"><?= htmlspecialchars($s['horarios']) ?></td>
                            <td style="padding: 15px 12px; font-size: 0.85rem; font-family: monospace; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($s['limitesgeocerca'] ?: 'No definida') ?>
                            </td>
                            <td style="padding: 15px 12px; text-align: center;">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                    <!-- Restaurar -->
                                    <form action="<?= BASE_URL ?>/sucursal/restaurar" method="POST" style="margin:0;">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn" style="padding:5px 10px;font-size:0.8rem;background:var(--success-color,#22c55e);border:none;cursor:pointer;">
                                            ♻️ Restaurar
                                        </button>
                                    </form>
                                    <!-- Eliminar Permanente -->
                                    <button onclick="confirmarEliminar(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>')"
                                            class="btn" style="padding:5px 10px;font-size:0.8rem;background:#ef4444;border:none;cursor:pointer;color:white;">
                                        🔥 Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sucursales)): ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay sucursales dadas de baja.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal: Confirmar Eliminación Permanente -->
<div id="modalEliminar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:5000;justify-content:center;align-items:center;">
    <div style="background:var(--card-bg,#fff);border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 40px rgba(0,0,0,0.3);text-align:left;">
        <div style="font-size:3rem;margin-bottom:16px;text-align:center;">🚨</div>
        <h2 style="margin:0 0 10px;font-size:1.3rem;color:#ef4444;text-align:center;">Eliminar Sucursal Definitivamente</h2>
        <p id="modalEliminarMsg" style="color:var(--text-muted);margin-bottom:16px;font-weight:bold;text-align:center;"></p>
        <div class="alert-error" style="font-size:0.85rem;margin-bottom:20px;padding:10px 14px;border-left-width:3px;">
            ⚠️ <strong>ATENCIÓN:</strong> Esta acción es irreversible. Se eliminará permanentemente la sucursal y toda la información asociada en el sistema.
        </div>
        <form id="formEliminar" method="POST" action="<?= BASE_URL ?>/sucursal/eliminar">
            <input type="hidden" name="id" id="eliminarSucursalId">
            <label style="display:flex;align-items:flex-start;gap:8px;font-size:0.85rem;margin-bottom:24px;cursor:pointer;color:var(--text-main);">
                <input type="checkbox" name="confirm_delete" value="1" required style="margin-top:3px;">
                <span>Confirmo que deseo eliminar esta sucursal permanentemente y entiendo que no se puede deshacer.</span>
            </label>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <button type="button" onclick="document.getElementById('modalEliminar').style.display='none'"
                        class="btn btn-outline" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn" style="flex:1;background:#ef4444;border-color:#ef4444;color:white;">Eliminar Permanentemente</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminarSucursalId').value = id;
    document.getElementById('modalEliminarMsg').textContent = '¿Eliminar definitivamente la sucursal ' + nombre + '?';
    document.getElementById('modalEliminar').style.display = 'flex';
}

// Cerrar modal haciendo clic fuera
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
