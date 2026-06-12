<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Inventario de Farmacia</h1>
                <p>Gestión de existencias y precios de medicamentos.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/farmacia/recetas" class="btn btn-outline" style="margin-right: 10px;">Despachar Recetas</a>
                <button class="btn" onclick="document.getElementById('modalCreate').style.display='flex'">+ Añadir Medicamento</button>
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
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;">Medicamento</th>
                        <th style="padding: 12px;">Tipo</th>
                        <th style="padding: 12px;">Stock Actual</th>
                        <th style="padding: 12px;">Precio Unitario ($)</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($medicamentos as $m): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><?= htmlspecialchars($m['id']) ?></td>
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($m['tipo'] ?? 'N/A') ?></td>
                            <td style="padding: 12px;">
                                <span style="color: <?= $m['stock'] < 10 ? '#dc2626' : '#16a34a' ?>; font-weight: bold;">
                                    <?= htmlspecialchars($m['stock']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px;">$<?= number_format($m['precio_unitario'], 2) ?></td>
                            <td style="padding: 12px;">
                                <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8em;" onclick="openUpdateModal(<?= htmlspecialchars(json_encode($m)) ?>)">Actualizar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal Update -->
<div id="modalUpdate" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s;">
        <h2 style="margin-bottom:15px; color:var(--primary-color);">Actualizar Medicamento</h2>
        <p style="margin-bottom:15px;">Producto: <strong id="lbl_nombre"></strong></p>
        <form action="<?= BASE_URL ?>/farmacia/update" method="POST">
            <input type="hidden" name="id" id="med_id">
            
            <div class="form-group">
                <label>Stock Actual</label>
                <input type="number" name="stock" id="med_stock" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Precio Unitario ($)</label>
                <input type="number" step="0.01" name="precio_unitario" id="med_precio" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalUpdate').style.display='none'">Cancelar</button>
                <button type="submit" class="btn">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create -->
<div id="modalCreate" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s; color: #1e293b;">
        <h2 style="margin-bottom:15px; color:var(--primary-color);">Añadir Medicamento</h2>
        <form action="<?= BASE_URL ?>/farmacia/create" method="POST">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #475569;">Nombre del Medicamento</label>
                <input type="text" name="nombre" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; color: #1e293b;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #475569;">Tipo (Tabletas, Jarabe, etc.)</label>
                <input type="text" name="tipo" placeholder="Ej. Tabletas, Jarabe" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; color: #1e293b;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #475569;">Stock Inicial</label>
                <input type="number" name="stock" value="0" min="0" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; color: #1e293b;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #475569;">Precio Unitario ($)</label>
                <input type="number" step="0.01" name="precio_unitario" value="0.00" min="0" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; color: #1e293b;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #475569;">Fecha de Vencimiento</label>
                <input type="date" name="vencimiento" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc; color: #1e293b;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modalCreate').style.display='none'">Cancelar</button>
                <button type="submit" class="btn">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUpdateModal(med) {
    document.getElementById('lbl_nombre').innerText = med.nombre;
    document.getElementById('med_id').value = med.id;
    document.getElementById('med_stock').value = med.stock;
    document.getElementById('med_precio').value = med.precio_unitario;
    document.getElementById('modalUpdate').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
