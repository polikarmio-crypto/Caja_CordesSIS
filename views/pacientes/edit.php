<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Editar Perfil</h1>
                <p>Actualiza la información personal del asegurado.</p>
            </div>
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-outline">Volver al Dashboard</a>
        </div>

        <?php if (isset($success)): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 800px;">
            <form action="<?= BASE_URL ?>/pacientes/edit" method="POST">
                <input type="hidden" name="paciente_id" value="<?= htmlspecialchars($paciente['id']) ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nombres</label>
                        <input type="text" name="nombres" value="<?= htmlspecialchars($paciente['nombres']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos</label>
                        <input type="text" name="apellidos" value="<?= htmlspecialchars($paciente['apellidos']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>CI / Documento de Identidad</label>
                        <input type="text" name="ci" value="<?= htmlspecialchars($paciente['ci']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono de Contacto</label>
                        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono_actual) ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nac" value="<?= htmlspecialchars($paciente['fecha_nac'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
