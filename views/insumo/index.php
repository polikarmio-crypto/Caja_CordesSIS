<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Inventario de Insumos Médicos</h1>
 <p>Monitoreo y administración de stock de materiales, insumos y herramientas. Mostrando <?= count($insumos) ?> de <?= $totalInsumos ?> insumos activos.</p>
 </div>
 <div style="display:flex;gap:10px;flex-wrap:wrap;">
 <a href="<?= BASE_URL ?>/insumo/bajas" class="btn btn-outline" style="font-size:0.85rem;">🗂 Dados de Baja</a>
 <a href="<?= BASE_URL ?>/insumo/create" class="btn">+ Nuevo Insumo</a>
 </div>
 </div>

 <?php
 $msg = $_GET['success'] ?? '';
 if ($msg === 'baja'): ?>
 <div class="alert-success"> Insumo dado de baja. Puede restaurarlo desde "Dados de Baja".</div>
 <?php elseif ($msg === 'restaurado'): ?>
 <div class="alert-success"> Insumo restaurado correctamente.</div>
 <?php elseif (!empty($msg)): ?>
 <div class="alert-success"> <?= htmlspecialchars($msg) ?></div>
 <?php endif; ?>

 <!-- Panel de Alertas de Stock Bajo -->
 <?php 
 $bajoStock = [];
 foreach ($insumos as $itemTmp) {
 if ($itemTmp['cantidad'] <= 10) {
 $bajoStock[] = $itemTmp;
 }
 }
 if (!empty($bajoStock)): 
 ?>
  <div class="alert-warning" style="margin-bottom: 25px; animation: fadeInUp 0.4s;">
  <h4 style="font-weight: bold; margin-bottom: 5px;">Alerta de Stock Crítico o Bajo</h4>
  <p style="font-size: 0.9rem; margin-bottom: 10px;">Los siguientes insumos médicos tienen 10 unidades o menos en existencia y requieren reabastecimiento:</p>
  <ul style="margin-left: 20px; font-size: 0.9rem;">
  <?php foreach ($bajoStock as $item): ?>
  <li><strong><?= htmlspecialchars($item['nombre']) ?></strong> - Solamente quedan <strong><?= $item['cantidad'] ?></strong> unidades.</li>
  <?php endforeach; ?>
  </ul>
  </div>
 <?php endif; ?>

 <div class="card" style="overflow-x:auto;">
 <table class="data-table" style="width: 100%; text-align: left; border-collapse: collapse;">
 <thead>
 <tr style="border-bottom: 2px solid var(--border-color);">
 <th style="padding: 15px 12px;">ID</th>
 <th style="padding: 15px 12px;">Artículo</th>
 <th style="padding: 15px 12px;">Categoría</th>
 <th style="padding: 15px 12px;">Precio Unitario</th>
 <th style="padding: 15px 12px; text-align: center;">Cantidad</th>
 <th style="padding: 15px 12px; text-align: center;">Estado</th>
 <th style="padding: 15px 12px; text-align: center;">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach($insumos as $i): ?>
 <tr style="border-bottom: 1px solid var(--border-color);" class="table-row-hover">
 <td style="padding: 15px 12px; font-weight: 500; color: var(--text-muted);">#<?= $i['id'] ?></td>
 <td style="padding: 15px 12px;">
 <div style="font-weight: 600; color: var(--primary-dark);"><?= htmlspecialchars($i['nombre']) ?></div>
 <div style="font-size: 0.85rem; color: var(--text-muted); max-width: 300px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
 <?= htmlspecialchars($i['descripcion'] ?: 'Sin descripción') ?>
 </div>
 </td>
 <td style="padding: 15px 12px;">
 <span style="padding: 4px 10px; background: rgba(0, 122, 94, 0.08); color: var(--primary-color); border-radius: 6px; font-size: 0.85rem; font-weight: 500;">
 <?= htmlspecialchars($i['categoria_nombre'] ?: 'General') ?>
 </span>
 </td>
 <td style="padding: 15px 12px; font-weight: 500;"><?= number_format($i['precio_unitario'], 2) ?> Bs</td>
 <td style="padding: 15px 12px; text-align: center;">
 <span style="font-weight: bold; font-size: 1.1rem; color: <?= $i['cantidad'] <= 10 ? 'var(--danger)' : 'var(--text-main)' ?>">
 <?= $i['cantidad'] ?>
 </span>
 </td>
 <td style="padding: 15px 12px; text-align: center;">
 <span style="display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; 
 background: <?= $i['estado'] === 'activo' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
 <?= htmlspecialchars(ucfirst($i['estado'])) ?>
 </span>
 </td>
 <td style="padding: 15px 12px; text-align: center;">
 <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
 <button onclick="confirmarBaja(<?= $i['id'] ?>, '<?= htmlspecialchars($i['nombre'], ENT_QUOTES) ?>')"
 class="btn" style="padding:5px 10px;font-size:0.8rem;background:var(--warning-color,#f59e0b);border:none;cursor:pointer;">
 Dar de Baja
 </button>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php if (empty($insumos)): ?>
 <tr>
 <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
 No hay insumos médicos en inventario actualmente.
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>

 <!-- Paginación -->
 <?php if ($totalPages > 1): ?>
 <div class="pagination-bar" style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:24px;flex-wrap:wrap;">
 <?php if ($page > 1): ?>
 <a href="?page=1" class="btn btn-outline page-btn" style="padding:8px 14px;font-size:0.85rem;">«</a>
 <a href="?page=<?= $page - 1 ?>" class="btn btn-outline page-btn" style="padding:8px 14px;font-size:0.85rem;">‹ Anterior</a>
 <?php endif; ?>

 <?php
 $start = max(1, $page - 2);
 $end = min($totalPages, $page + 2);
 for ($i = $start; $i <= $end; $i++):
 ?>
 <a href="?page=<?= $i ?>"
 class="btn <?= $i === $page ? '' : 'btn-outline' ?> page-btn"
 style="padding:8px 14px;font-size:0.85rem;<?= $i === $page ? 'pointer-events:none;opacity:0.8;' : '' ?>">
 <?= $i ?>
 </a>
 <?php endfor; ?>

 <?php if ($page < $totalPages): ?>
 <a href="?page=<?= $page + 1 ?>" class="btn btn-outline page-btn" style="padding:8px 14px;font-size:0.85rem;">Siguiente ›</a>
 <a href="?page=<?= $totalPages ?>" class="btn btn-outline page-btn" style="padding:8px 14px;font-size:0.85rem;">»</a>
 <?php endif; ?>

 <span style="color:var(--text-muted);font-size:0.85rem;margin-left:10px;">
 Página <?= $page ?> de <?= $totalPages ?>
 </span>
 </div>
 <?php endif; ?>
 </main>
</div>

<!-- Modal: Confirmar Baja Lógica -->
<div id="modalBaja" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:5000;justify-content:center;align-items:center;">
 <div style="background:var(--card-bg,#fff);border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 40px rgba(0,0,0,0.3);text-align:center;">
 <div style="font-size:3rem;margin-bottom:16px;"></div>
 <h2 style="margin:0 0 10px;font-size:1.3rem;color:var(--text-primary);">Dar de Baja el Insumo</h2>
 <p id="modalBajaMsg" style="color:var(--text-muted);margin-bottom:24px;"></p>
 <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:24px;">El insumo quedará <strong>inactivo</strong> pero no se eliminará. Puede restaurarlo desde "Dados de Baja".</p>
 <form id="formBaja" method="POST" action="<?= BASE_URL ?>/insumo/baja">
 <input type="hidden" name="id" id="bajaInsumoId">
 <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
 <button type="button" onclick="document.getElementById('modalBaja').style.display='none'"
 class="btn btn-outline" style="flex:1;">Cancelar</button>
 <button type="submit" class="btn" style="flex:1;background:#f59e0b;border-color:#f59e0b;">Confirmar Baja</button>
 </div>
 </form>
 </div>
</div>

<script>
function confirmarBaja(id, nombre) {
 document.getElementById('bajaInsumoId').value = id;
 document.getElementById('modalBajaMsg').textContent = '¿Dar de baja el insumo ' + nombre + '?';
 document.getElementById('modalBaja').style.display = 'flex';
}

// Cerrar modal haciendo clic fuera
document.getElementById('modalBaja').addEventListener('click', function(e) {
 if (e.target === this) this.style.display = 'none';
});
</script>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>