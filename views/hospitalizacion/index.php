<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<style>
.bed-grid {
 display: grid;
 grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
 gap: 15px;
 margin-top: 20px;
}
.bed-card {
 border-radius: 12px;
 padding: 15px;
 text-align: center;
 cursor: pointer;
 transition: all 0.3s ease;
 border: 2px solid transparent;
 color: #fff;
 box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.bed-card:hover { transform: translateY(-5px); }
.bed-libre { background: rgba(34, 197, 94, 0.9); }
.bed-ocupada { background: rgba(239, 68, 68, 0.9); }
.bed-limpieza { background: rgba(234, 179, 8, 0.9); }
.bed-mantenimiento { background: rgba(107, 114, 128, 0.9); }
</style>

<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Matriz de Camas</h1>
 <p>Gestión visual de hospitalización.</p>
 </div>
 </div>

 <?php if (isset($_GET['success'])): ?>
 <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($_GET['success']) ?>
 </div>
 <?php endif; ?>

 <?php foreach($matriz as $hab): ?>
 <div class="card" style="margin-bottom: 20px;">
 <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
 Habitación <?= htmlspecialchars($hab['numero']) ?> <span style="font-size: 0.8em; color: var(--text-muted); text-transform: capitalize;">(<?= htmlspecialchars($hab['tipo']) ?>)</span>
 </h3>
 
 <div class="bed-grid">
 <?php foreach($hab['camas'] as $cama): ?>
 <div class="bed-card bed-<?= $cama['estado'] ?>" 
 onclick="openModal(<?= htmlspecialchars(json_encode($cama)) ?>)">
 <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 5px;">
 Cama N°<?= htmlspecialchars($cama['numero']) ?>
 </div>
 <div style="font-size: 0.85em; text-transform: uppercase; font-weight: 600;">
 <?= htmlspecialchars($cama['estado']) ?>
 </div>
 <?php if($cama['estado'] === 'ocupada' && $cama['paciente']): ?>
 <div style="font-size: 0.8em; margin-top: 10px; opacity: 0.9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
 <?= htmlspecialchars($cama['paciente']) ?>
 </div>
 <?php endif; ?>
 </div>
 <?php endforeach; ?>
 </div>
 </div>
 <?php endforeach; ?>
 </main>
</div>

<!-- Modal Ingreso -->
<div id="modalIngreso" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
 <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s;">
 <h2 style="margin-bottom:15px; color:var(--primary-color);">Ingresar Paciente</h2>
 <p style="margin-bottom:15px;">Cama <strong id="lbl_cama_libre"></strong></p>
 <form action="<?= BASE_URL ?>/hospitalizacion/ingresar" method="POST">
 <input type="hidden" name="cama_id" id="ingreso_cama_id">
 <div class="form-group">
 <label>Paciente</label>
 <select name="paciente_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 <?php foreach($pacientes as $p): ?>
 <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' - ' . $p['ci']) ?></option>
 <?php endforeach; ?>
 </select>
 </div>
 <div class="form-group" style="margin-bottom: 20px;">
 <label>Motivo de Ingreso</label>
 <textarea name="motivo_ingreso" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required></textarea>
 </div>
 <div style="display:flex; justify-content:flex-end; gap:10px;">
 <button type="button" class="btn btn-outline" onclick="closeModals()">Cancelar</button>
 <button type="submit" class="btn">Confirmar Ingreso</button>
 </div>
 </form>
 </div>
</div>

<!-- Modal Alta -->
<div id="modalAlta" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
 <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s;">
 <h2 style="margin-bottom:15px; color:var(--danger-color, #e3342f);">Dar de Alta</h2>
 <p style="margin-bottom:15px;">Cama <strong id="lbl_cama_ocupada"></strong> - <span id="lbl_paciente_ocupada"></span></p>
 <form action="<?= BASE_URL ?>/hospitalizacion/alta" method="POST">
 <input type="hidden" name="cama_id" id="alta_cama_id">
 <div class="form-group" style="margin-bottom: 20px;">
 <label>Notas de Alta (Opcional)</label>
 <textarea name="notas_alta" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;"></textarea>
 </div>
 <div style="display:flex; justify-content:flex-end; gap:10px;">
 <button type="button" class="btn btn-outline" onclick="closeModals()">Cancelar</button>
 <button type="submit" class="btn" style="background:#e3342f; color:white;">Confirmar Alta</button>
 </div>
 </form>
 </div>
</div>

<!-- Modal Limpieza -->
<div id="modalLimpieza" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
 <div style="background:white; padding:30px; border-radius:12px; width:400px; animation: fadeInUp 0.3s;">
 <h2 style="margin-bottom:15px; color:#ca8a04;">Cama en Limpieza</h2>
 <p style="margin-bottom:15px;">¿La cama <strong id="lbl_cama_limpieza"></strong> ya fue desinfectada y está lista para un nuevo ingreso?</p>
 <form action="<?= BASE_URL ?>/hospitalizacion/limpiar" method="POST">
 <input type="hidden" name="cama_id" id="limpiar_cama_id">
 <div style="display:flex; justify-content:flex-end; gap:10px;">
 <button type="button" class="btn btn-outline" onclick="closeModals()">No, volver</button>
 <button type="submit" class="btn" style="background:#ca8a04; color:white;">Sí, Marcar como Libre</button>
 </div>
 </form>
 </div>
</div>

<script>
function openModal(cama) {
 if (cama.estado === 'libre') {
 document.getElementById('lbl_cama_libre').innerText = cama.numero;
 document.getElementById('ingreso_cama_id').value = cama.id;
 document.getElementById('modalIngreso').style.display = 'flex';
 } else if (cama.estado === 'ocupada') {
 document.getElementById('lbl_cama_ocupada').innerText = cama.numero;
 document.getElementById('lbl_paciente_ocupada').innerText = cama.paciente;
 document.getElementById('alta_cama_id').value = cama.id;
 document.getElementById('modalAlta').style.display = 'flex';
 } else if (cama.estado === 'limpieza') {
 document.getElementById('lbl_cama_limpieza').innerText = cama.numero;
 document.getElementById('limpiar_cama_id').value = cama.id;
 document.getElementById('modalLimpieza').style.display = 'flex';
 }
}

function closeModals() {
 document.getElementById('modalIngreso').style.display = 'none';
 document.getElementById('modalAlta').style.display = 'none';
 document.getElementById('modalLimpieza').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
