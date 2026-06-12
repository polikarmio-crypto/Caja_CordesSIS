<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Resultado de Laboratorio</h1>
 <p>Sube los resultados de los análisis del paciente.</p>
 </div>
 <a href="<?= BASE_URL ?>/laboratorio" class="btn btn-outline">Volver</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 800px;">
 <form action="<?= BASE_URL ?>/laboratorio/create" method="POST">
 <div style="display:flex; gap:20px;">
 <div class="form-group" style="flex:1;">
 <label>Paciente</label>
 <select name="paciente_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
 <option value="">Selecciona un paciente...</option>
 <?php foreach($pacientes as $p): ?>
 <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' - CI: ' . $p['ci']) ?></option>
 <?php endforeach; ?>
 </select>
 </div>

 <div class="form-group" style="flex:1;">
 <label>Examen Realizado</label>
 <select name="examen_id" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);">
 <option value="">Selecciona el tipo de examen...</option>
 <?php foreach($examenes as $e): ?>
 <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($e['tipo_muestra']) ?>)</option>
 <?php endforeach; ?>
 </select>
 </div>
 </div>

 <div class="form-group">
 <label>Resultado / Conclusión Clínica</label>
 <textarea name="resultado" rows="4" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;" required></textarea>
 </div>

 <div class="form-group">
 <label>Valores de Referencia Utilizados</label>
 <input type="text" name="valores_referencia" placeholder="Ej. 70-100 mg/dL" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color);" required>
 </div>

 <div style="margin-top: 20px; text-align: right;">
 <button type="submit" class="btn">Guardar Resultado Oficial</button>
 </div>
 </form>
 </div>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
