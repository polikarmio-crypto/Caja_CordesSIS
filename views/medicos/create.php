<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Nuevo Médico</h1>
 <p>Crea una cuenta de usuario y asigna la licencia y especialidades correspondientes.</p>
 </div>
 <a href="<?= BASE_URL ?>/medicos" class="btn btn-outline">Volver a la Lista</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 800px;">
 <form action="<?= BASE_URL ?>/medicos/create" method="POST">
 <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
 <div class="form-group">
 <label>Email de Usuario</label>
 <input type="email" name="email" placeholder="ejemplo@cajacordes.com" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 </div>
 <div class="form-group">
 <label>Contraseña</label>
 <input type="password" name="password" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 </div>
 <div class="form-group" style="grid-column: span 2;">
 <label>Licencia Médica (C.O.M. / Matrícula)</label>
 <input type="text" name="licencia_medica" placeholder="Ej: MED-12345" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 </div>
 
 <div class="form-group" style="grid-column: span 2;">
 <label style="font-weight: bold; margin-bottom: 10px; display: block;">Especialidades Médicas</label>
 <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
 <?php foreach($especialidadesList as $esp): ?>
 <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: rgba(0,0,0,0.01);">
 <input type="checkbox" name="especialidades[]" value="<?= htmlspecialchars($esp['id']) ?>">
 <?= htmlspecialchars($esp['nombre']) ?>
 </label>
 <?php endforeach; ?>
 </div>
 </div>
 </div>
 
 <div style="margin-top: 30px; text-align: right;">
 <button type="submit" class="btn" style="padding: 12px 24px; font-size: 1rem;">Registrar Médico</button>
 </div>
 </form>
 </div>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
