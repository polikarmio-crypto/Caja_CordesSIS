<?php require_once __DIR__ . '/../layouts/header.php'; 
$conn = Database::getInstance();
$categorias = $conn->query("SELECT id, nombre FROM categorias_insumo ORDER BY nombre")->fetchAll();
?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Registrar Insumo Médico</h1>
 <p>Agrega un nuevo artículo de inventario o insumo médico al almacén.</p>
 </div>
 <a href="<?= BASE_URL ?>/insumo" class="btn btn-outline">Volver a Insumos</a>
 </div>

 <?php if (isset($error)): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <div class="card" style="max-width: 600px; margin: 0 auto;">
 <form action="<?= BASE_URL ?>/insumo/create" method="POST">
 <div class="form-group">
 <label for="nombre">Nombre del Insumo / Artículo</label>
 <input type="text" id="nombre" name="nombre" required placeholder="Ej. Gasas estériles, Jeringas 5ml, Guantes látex...">
 </div>

 <div class="form-group">
 <label for="id_categoria">Categoría</label>
 <select id="id_categoria" name="id_categoria" required style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-size: 1rem;">
 <option value="">Seleccione una categoría...</option>
 <?php foreach($categorias as $c): ?>
 <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
 <?php endforeach; ?>
 <?php if (empty($categorias)): ?>
 <option value="">No hay categorías. Agrega una en la BD.</option>
 <?php endif; ?>
 </select>
 </div>

 <div style="display: flex; gap: 20px;">
 <div class="form-group" style="flex: 1;">
 <label for="cantidad">Cantidad en Stock</label>
 <input type="number" id="cantidad" name="cantidad" required min="0" placeholder="Ej. 100">
 </div>

 <div class="form-group" style="flex: 1;">
 <label for="precio_unitario">Precio Unitario (Bs/BOB)</label>
 <input type="number" id="precio_unitario" name="precio_unitario" required min="0" step="0.01" placeholder="Ej. 15.50">
 </div>
 </div>

 <div class="form-group">
 <label for="descripcion">Descripción</label>
 <textarea id="descripcion" name="descripcion" rows="3" style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-family: inherit;" placeholder="Ej. Caja de 50 unidades, de uso descartable para cirugía..."></textarea>
 </div>

 <div style="margin-top: 30px; text-align: right;">
 <button type="submit" class="btn" style="width: 100%;">Registrar Insumo</button>
 </div>
 </form>
 </div>
 </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>