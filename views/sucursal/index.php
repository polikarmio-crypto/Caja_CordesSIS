<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Sucursales</h1>
 <p>Gestión de las sucursales, clínicas y áreas descentralizadas. Mostrando <?= count($sucursales) ?> de <?= $totalSucursales ?> sucursales activas.</p>
 </div>
 <div style="display:flex;gap:10px;flex-wrap:wrap;">
 <a href="<?= BASE_URL ?>/sucursal/bajas" class="btn btn-outline" style="font-size:0.85rem;">🗂 Dados de Baja</a>
 <a href="<?= BASE_URL ?>/sucursal/create" class="btn">+ Nueva Sucursal</a>
 </div>
 </div>

 <?php
 $msg = $_GET['success'] ?? '';
 if ($msg === 'baja'): ?>
 <div class="alert-success"> Sucursal dada de baja. Puede restaurarla desde "Dados de Baja".</div>
 <?php elseif ($msg === 'restaurada'): ?>
 <div class="alert-success"> Sucursal restaurada correctamente.</div>
 <?php elseif (!empty($msg)): ?>
 <div class="alert-success"> <?= htmlspecialchars($msg) ?></div>
 <?php endif; ?>

 <div class="card" style="overflow-x:auto;">
 <table class="data-table" style="width: 100%; text-align: left; border-collapse: collapse;">
 <thead>
 <tr style="border-bottom: 2px solid var(--border-color);">
 <th style="padding: 15px 12px;">ID</th>
 <th style="padding: 15px 12px;">Nombre</th>
 <th style="padding: 15px 12px;">Ubicación</th>
 <th style="padding: 15px 12px;">Horarios</th>
 <th style="padding: 15px 12px;">Estado</th>
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
 <td style="padding: 15px 12px;">
 <span style="display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; 
 background: <?= $s['estado'] === 'activo' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
 <?= htmlspecialchars(ucfirst($s['estado'])) ?>
 </span>
 </td>
 <td style="padding: 15px 12px; text-align: center;">
 <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
 <button onclick="confirmarBaja(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>')"
 class="btn" style="padding:5px 10px;font-size:0.8rem;background:var(--warning-color,#f59e0b);border:none;cursor:pointer;">
 Dar de Baja
 </button>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php if (empty($sucursales)): ?>
 <tr>
 <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
 No hay sucursales registradas en el sistema.
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
 <h2 style="margin:0 0 10px;font-size:1.3rem;color:var(--text-primary);">Dar de Baja la Sucursal</h2>
 <p id="modalBajaMsg" style="color:var(--text-muted);margin-bottom:24px;"></p>
 <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:24px;">La sucursal quedará <strong>inactiva</strong> pero no se eliminará. Puede restaurarla desde "Dados de Baja".</p>
 <form id="formBaja" method="POST" action="<?= BASE_URL ?>/sucursal/baja">
 <input type="hidden" name="id" id="bajaSucursalId">
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
 document.getElementById('bajaSucursalId').value = id;
 document.getElementById('modalBajaMsg').textContent = '¿Dar de baja la sucursal ' + nombre + '?';
 document.getElementById('modalBaja').style.display = 'flex';
}

// Cerrar modal haciendo clic fuera
document.getElementById('modalBaja').addEventListener('click', function(e) {
 if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>