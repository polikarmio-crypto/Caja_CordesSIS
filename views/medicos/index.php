<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Médicos</h1>
 <p>Gestión del personal médico y especialidades. Mostrando <?= count($medicos) ?> de <?= $totalMedicos ?> médicos activos.</p>
 </div>
 <div style="display:flex;gap:10px;flex-wrap:wrap;">
 <a href="<?= BASE_URL ?>/medicos/bajas" class="btn btn-outline" style="font-size:0.85rem;">🗂 Dados de Baja</a>
 <a href="<?= BASE_URL ?>/medicos/create" class="btn">+ Nuevo Médico</a>
 </div>
 </div>

 <?php
 $msg = $_GET['success'] ?? '';
 if ($msg === 'baja'): ?>
 <div class="alert-success"> Médico dado de baja. Puede restaurarlo desde "Dados de Baja".</div>
 <?php elseif ($msg === 'restaurado'): ?>
 <div class="alert-success"> Médico restaurado correctamente.</div>
 <?php elseif ($msg === '1'): ?>
 <div class="alert-success"> Médico registrado con éxito.</div>
 <?php endif; ?>

 <div class="card" style="overflow-x:auto;">
 <table class="data-table" style="width: 100%; text-align: left; border-collapse: collapse;">
 <thead>
 <tr style="border-bottom: 2px solid var(--border-color);">
 <th style="padding: 12px;">ID</th>
 <th style="padding: 12px;">Email / Usuario</th>
 <th style="padding: 12px;">Licencia Médica</th>
 <th style="padding: 12px;">Especialidades</th>
 <th style="padding: 12px; text-align: center;">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach($medicos as $m): ?>
 <tr style="border-bottom: 1px solid var(--border-color);" class="table-row-hover">
 <td style="padding: 12px;"><?= htmlspecialchars($m['id']) ?></td>
 <td style="padding: 12px;"><?= htmlspecialchars($m['email']) ?></td>
 <td style="padding: 12px;"><?= htmlspecialchars($m['licencia_medica']) ?></td>
 <td style="padding: 12px;"><?= htmlspecialchars($m['especialidades'] ?? 'Ninguna') ?></td>
 <td style="padding: 12px; text-align: center;">
 <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
 <button onclick="confirmarBaja(<?= $m['id'] ?>, '<?= htmlspecialchars($m['email'], ENT_QUOTES) ?>')"
 class="btn" style="padding:5px 10px;font-size:0.8rem;background:var(--warning-color,#f59e0b);border:none;cursor:pointer;">
 Dar de Baja
 </button>
 </div>
 </td>
 </tr>
 <?php endforeach; ?>
 <?php if (empty($medicos)): ?>
 <tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay médicos registrados.</td></tr>
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
 <h2 style="margin:0 0 10px;font-size:1.3rem;color:var(--text-primary);">Dar de Baja al Médico</h2>
 <p id="modalBajaMsg" style="color:var(--text-muted);margin-bottom:24px;"></p>
 <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:24px;">El médico quedará <strong>inactivo</strong> pero no se eliminará del sistema. Puede restaurarlo desde "Dados de Baja".</p>
 <form id="formBaja" method="POST" action="<?= BASE_URL ?>/medicos/baja">
 <input type="hidden" name="id" id="bajaMedicoId">
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
 document.getElementById('bajaMedicoId').value = id;
 document.getElementById('modalBajaMsg').textContent = '¿Dar de baja a ' + nombre + '?';
 document.getElementById('modalBaja').style.display = 'flex';
}

// Cerrar modal haciendo clic fuera
document.getElementById('modalBaja').addEventListener('click', function(e) {
 if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
