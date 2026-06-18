<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Sucursal</h1>
 <p>Agrega una nueva clínica o centro de atención descentralizado.</p>
 </div>
 <a href="<?= BASE_URL ?>/sucursal" class="btn btn-outline">Volver a Sucursales</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 600px; margin: 0 auto;">
 <form action="<?= BASE_URL ?>/sucursal/create" method="POST">
 <div class="form-group">
 <label for="nombre">Nombre de la Sucursal</label>
 <input type="text" id="nombre" name="nombre" required placeholder="Ej. Clínica Central, Anexo Sur...">
 </div>

 <div class="form-group">
 <label for="ubicacion">Dirección / Ubicación</label>
 <input type="text" id="ubicacion" name="ubicacion" required placeholder="Ej. Av. Hernando Siles #1234, La Paz">
 </div>

 <div class="form-group">
 <label for="horarios">Horarios de Atención</label>
 <input type="text" id="horarios" name="horarios" required placeholder="Ej. Lunes a Viernes 08:00 - 20:00, Sábados 08:00 - 12:00">
 </div>

 <div style="margin-top: 30px; text-align: right;">
 <button type="submit" class="btn" style="width: 100%;">Crear Sucursal</button>
 </div>
 </form>
 </div>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>