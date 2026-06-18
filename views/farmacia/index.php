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

 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px;">
     <form action="<?= BASE_URL ?>/farmacia" method="GET" style="display: flex; gap: 10px; width: 400px; margin: 0;">
         <input type="text" name="q" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
                style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
         <button type="submit" class="btn">Buscar</button>
         <?php if (!empty($_GET['q'])): ?>
             <a href="<?= BASE_URL ?>/farmacia" class="btn btn-outline">Limpiar</a>
         <?php endif; ?>
     </form>
     <div>
         <a href="<?= BASE_URL ?>/farmacia/bajas" class="btn btn-outline" style="color: #dc2626; border-color: #dc2626;">Ver Bajas</a>
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
 <th style="padding: 12px;">Código</th>
 <th style="padding: 12px;">Medicamento</th>
 <th style="padding: 12px;">Tipo</th>
 <th style="padding: 12px;">Stock Actual</th>
 <th style="padding: 12px;">Precio Unitario (Bs.)</th>
 <th style="padding: 12px;">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach($medicamentos as $m): ?>
 <tr style="border-bottom: 1px solid var(--border-color);">
 <td style="padding: 12px; font-family: monospace; font-weight: bold;"><?= htmlspecialchars($m['codigo_identificacion'] ?? 'N/A') ?></td>
 <td style="padding: 12px;"><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
 <td style="padding: 12px;"><?= htmlspecialchars($m['tipo'] ?? 'N/A') ?></td>
 <td style="padding: 12px;">
 <span style="color: <?= $m['stock'] < 10 ? '#dc2626' : '#16a34a' ?>; font-weight: bold;">
 <?= htmlspecialchars($m['stock']) ?>
 </span>
 </td>
 <td style="padding: 12px;">Bs. <?= number_format($m['precio_unitario'], 2) ?></td>
 <td style="padding: 12px;">
 <div style="display: flex; gap: 5px;">
     <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8em;" onclick="openUpdateModal(<?= htmlspecialchars(json_encode($m)) ?>)">Actualizar</button>
     <form action="<?= BASE_URL ?>/farmacia/baja" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('¿Está seguro de dar de baja este medicamento?');">
         <input type="hidden" name="id" value="<?= $m['id'] ?>">
         <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8em; color: #dc2626; border-color: #dc2626;">Eliminar</button>
     </form>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php if (empty($medicamentos)): ?>
 <tr><td colspan="6" style="padding: 20px; text-align: center; color: var(--text-muted);">No se encontraron medicamentos.</td></tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 </main>
</div>

<!-- Modal Update -->
<div id="modalUpdate" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center;">
 <div style="background:var(--bg-card); border:1px solid var(--border-color); padding:30px; border-radius:16px; width:400px; animation: fadeInUp 0.3s; box-shadow:var(--shadow-lg);">
 <h2 style="margin-bottom:15px; color:var(--primary-light);">Actualizar Medicamento</h2>
 <p style="margin-bottom:15px; color:var(--text-secondary);">Producto: <strong id="lbl_nombre" style="color:var(--text-main);"></strong></p>
 <form action="<?= BASE_URL ?>/farmacia/update" method="POST">
 <input type="hidden" name="id" id="med_id">
 
 <div class="form-group">
 <label style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-secondary); font-size:0.88rem;">Stock Actual</label>
 <input type="number" name="stock" id="med_stock" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background:var(--bg-input); color:var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 20px;">
 <label style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-secondary); font-size:0.88rem;">Precio Unitario (Bs.)</label>
 <input type="number" step="0.01" name="precio_unitario" id="med_precio" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background:var(--bg-input); color:var(--text-main);">
 </div>

 <div style="display:flex; justify-content:flex-end; gap:10px;">
 <button type="button" class="btn btn-outline" onclick="document.getElementById('modalUpdate').style.display='none'">Cancelar</button>
 <button type="submit" class="btn">Guardar Cambios</button>
 </div>
 </form>
 </div>
</div>

<!-- Modal Create -->
<div id="modalCreate" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center;">
 <div style="background:var(--bg-card); border:1px solid var(--border-color); padding:30px; border-radius:16px; width:400px; animation: fadeInUp 0.3s; box-shadow:var(--shadow-lg); max-height:90vh; overflow-y:auto;">
 <h2 style="margin-bottom:15px; color:var(--primary-light);">Añadir Medicamento</h2>
 <form action="<?= BASE_URL ?>/farmacia/create" method="POST">
 <div class="form-group" style="margin-bottom: 15px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Código de Identificación</label>
 <input type="text" name="codigo_identificacion" placeholder="Ej. MED-020" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 15px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Nombre del Medicamento</label>
 <input type="text" name="nombre" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 15px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Tipo (Tabletas, Jarabe, etc.)</label>
 <input type="text" name="tipo" placeholder="Ej. Tabletas, Jarabe" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 15px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Stock Inicial</label>
 <input type="number" name="stock" value="0" min="0" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 15px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Precio Unitario (Bs.)</label>
 <input type="number" step="0.01" name="precio_unitario" value="0.00" min="0" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
 </div>
 <div class="form-group" style="margin-bottom: 20px;">
 <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); font-size:0.88rem;">Fecha de Vencimiento</label>
 <input type="date" name="vencimiento" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main);">
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
