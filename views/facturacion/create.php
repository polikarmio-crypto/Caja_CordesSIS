<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
 <div class="dashboard-header">
 <div class="welcome-text">
 <h1>Nueva Factura Manual</h1>
 <p>Emite una factura ingresando los conceptos de cobro manualmente.</p>
 </div>
 <a href="<?= BASE_URL ?>/facturacion" class="btn btn-outline">Volver</a>
 </div>

 <?php if (isset($_GET['error'])): ?>
 <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
 <?= htmlspecialchars($_GET['error']) ?>
 </div>
 <?php endif; ?>

 <div class="card">
 <form action="<?= BASE_URL ?>/facturacion/create" method="POST" id="facturaForm">
 <div class="form-group">
 <label>Paciente</label>
 <select name="paciente_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 <option value="">Seleccione un paciente...</option>
 <?php foreach($pacientes as $p): ?>
 <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' - CI: ' . $p['ci']) ?></option>
 <?php endforeach; ?>
 </select>
 </div>

 <div class="form-group" style="margin-top: 15px;">
 <label>Motivo de Facturación (Exclusiones)</label>
 <select name="motivo" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
 <option value="Paciente Externo (No asegurado)">Paciente Externo (No asegurado)</option>
 <option value="Accidente de Tránsito (SOAT)">Accidente de Tránsito (SOAT)</option>
 <option value="Agresión / Riña">Agresión / Riña</option>
 <option value="Cirugía Estética">Cirugía Estética</option>
 <option value="Servicios Excluidos del Seguro">Servicios Excluidos del Seguro</option>
 </select>
 </div>

 <h3 style="margin-top: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">Detalles de Factura</h3>
 
 <table style="width: 100%; text-align: left; margin-top: 15px;" id="detallesTable">
 <thead>
 <tr>
 <th style="padding: 10px; width: 50%;">Concepto</th>
 <th style="padding: 10px; width: 15%;">Cantidad</th>
 <th style="padding: 10px; width: 25%;">Precio Unitario (Bs.)</th>
 <th style="padding: 10px; width: 10%;"></th>
 </tr>
 </thead>
 <tbody id="detallesBody">
 <tr>
 <td style="padding: 5px;"><input type="text" name="concepto[]" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><input type="number" name="cantidad[]" min="1" value="1" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><input type="number" name="precio[]" step="0.01" min="0" value="0.00" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><button type="button" class="btn btn-outline" style="color: #dc2626;" onclick="this.closest('tr').remove()">X</button></td>
 </tr>
 </tbody>
 </table>
 
 <div style="margin-top: 10px;">
 <button type="button" class="btn btn-outline" onclick="addFila()">+ Agregar Concepto</button>
 </div>

 <div style="margin-top: 30px; text-align: right;">
 <button type="submit" class="btn" style="padding: 12px 30px; font-size: 1.1em;">Generar Factura</button>
 </div>
 </form>
 </div>
 </main>
</div>

<script>
function addFila() {
 const tbody = document.getElementById('detallesBody');
 const tr = document.createElement('tr');
 tr.innerHTML = `
 <td style="padding: 5px;"><input type="text" name="concepto[]" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><input type="number" name="cantidad[]" min="1" value="1" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><input type="number" name="precio[]" step="0.01" min="0" value="0.00" required style="width: 100%; padding: 8px;"></td>
 <td style="padding: 5px;"><button type="button" class="btn btn-outline" style="color: #dc2626;" onclick="this.closest('tr').remove()">X</button></td>
 `;
 tbody.appendChild(tr);
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
