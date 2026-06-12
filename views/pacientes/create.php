<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Paciente</h1>
 <p>Añade un nuevo paciente al sistema.</p>
 </div>
 <a href="<?= BASE_URL ?>/pacientes" class="btn btn-outline">Volver</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 800px;">
 <form action="<?= BASE_URL ?>/pacientes/create" method="POST">
 <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
 <div class="form-group">
 <label>Nombres</label>
 <input type="text" name="nombres" required>
 </div>
 <div class="form-group">
 <label>Apellidos</label>
 <input type="text" name="apellidos" required>
 </div>
 <div class="form-group">
 <label>CI</label>
 <input type="text" name="ci" required>
 </div>
 <div class="form-group">
 <label>Teléfono</label>
 <input type="text" name="telefono">
 </div>
 <div class="form-group">
 <label>Fecha de Nacimiento</label>
 <input type="date" name="fecha_nac" required>
 </div>
 <div class="form-group">
 <label>Correo Electrónico (para acceso)</label>
 <input type="email" name="email" required>
 </div>
 <div class="form-group" style="grid-column: span 2;">
 <label>Contraseña (para acceso)</label>
 <input type="password" name="password" required>
 </div>
 </div>
 <div style="margin-top: 20px; text-align: right;">
 <button type="submit" class="btn">Guardar Paciente</button>
 </div>
 </form>
 </div>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
