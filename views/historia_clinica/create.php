<?php 
require_once __DIR__ . '/../layouts/header.php'; 
// Obtener médicos
$conn = Database::getInstance();
$medicosList = $conn->query("
 SELECT m.id, u.email as medico_email, STRING_AGG(DISTINCT e.nombre, ', ') as especialidad,
 COALESCE(AVG(cal.puntuacion), 0) as avg_rating,
 COUNT(cal.id) as total_ratings
 FROM medicos m 
 JOIN usuarios u ON m.usuario_id = u.id 
 LEFT JOIN medico_especialidades me ON m.id = me.medico_id
 LEFT JOIN especialidades e ON me.especialidad_id = e.id
 LEFT JOIN calificaciones cal ON m.id = cal.medico_id
 GROUP BY m.id, u.email
")->fetchAll();

$medicamentosList = $conn->query("SELECT id, nombre FROM medicamentos")->fetchAll();
?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Evolución / Diagnóstico</h1>
 <p>Añade una nueva entrada a la historia clínica.</p>
 </div>
 <a href="<?= BASE_URL ?>/pacientes/<?= htmlspecialchars($paciente_id) ?>/historia" class="btn btn-outline">Cancelar</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 800px;">
 <form action="<?= BASE_URL ?>/historia_clinica/create" method="POST" enctype="multipart/form-data">
 <input type="hidden" name="paciente_id" value="<?= htmlspecialchars($paciente_id) ?>">
 
 <div class="form-group">
 <label>Médico Atendido</label>
 <select name="medico_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
 <option value="">Selecciona un médico...</option>
 <?php foreach($medicosList as $m): ?>
 <option value="<?= $m['id'] ?>">
 <?= htmlspecialchars($m['medico_email'] . ' (' . $m['especialidad'] . ')') ?>
 <?= $m['total_ratings'] > 0 ? ' - ' . number_format($m['avg_rating'], 1) . ' (' . $m['total_ratings'] . ' valoraciones)' : ' - (Sin valoraciones)' ?>
 </option>
 <?php endforeach; ?>
 </select>
 </div>

 <div class="form-group">
 <label>Diagnóstico / Evolución</label>
 <textarea name="diagnostico" rows="4" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;"></textarea>
 </div>

 <div class="form-group">
 <label>Indicaciones Generales de la Receta</label>
 <textarea name="receta_notas" rows="3" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;"></textarea>
 </div>

 <!-- Receta Dinámica -->
 <div class="form-group" style="padding: 15px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid var(--border-color);">
 <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
 <label style="margin:0;">Medicamentos Recetados</label>
 <button type="button" class="btn btn-outline" onclick="addMedicamento()" style="padding: 5px 10px; font-size: 0.8em;">+ Agregar</button>
 </div>
 
 <div id="medicamentos-container"></div>
 </div>
 
 <div class="form-group">
 <label>Adjuntar Archivo (Exámenes, imágenes)</label>
 <input type="file" name="archivo" style="padding: 10px; background: var(--secondary-color); width: 100%; border-radius: 8px;">
 </div>

 <div style="margin-top: 20px; text-align: right;">
 <button type="submit" class="btn">Guardar Registro Clínico</button>
 </div>
 </form>
 </div>
 </main>
</div>

<script>
const medicamentosOpts = `<?php foreach($medicamentosList as $med) echo '<option value="'.$med['id'].'">'.addslashes($med['nombre']).'</option>'; ?>`;

function addMedicamento() {
 const container = document.getElementById('medicamentos-container');
 const index = container.children.length;
 
 const div = document.createElement('div');
 div.style.cssText = "display:flex; gap:10px; margin-bottom:10px; align-items:center; animation: fadeInUp 0.3s;";
 div.innerHTML = `
 <select name="medicamentos_id[]" style="flex:2; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);" required>
 <option value="">Medicamento...</option>
 ${medicamentosOpts}
 </select>
 <input type="text" name="medicamentos_dosis[]" placeholder="Dosis (ej. 500mg)" style="flex:1; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);" required>
 <input type="text" name="medicamentos_frecuencia[]" placeholder="Frecuencia (ej. c/8h)" style="flex:1; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);" required>
 <input type="number" name="medicamentos_duracion[]" placeholder="Días" style="flex:0.5; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);" required>
 <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:red; cursor:pointer; font-weight:bold;">X</button>
 `;
 container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
