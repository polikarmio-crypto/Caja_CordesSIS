<?php require_once '../views/layouts/header.php'; ?>
<div class="login-wrapper">
    
    <div class="login-left">
        <h1>Caja Cordes</h1>
        <p>Sistema Integral de Gestión de Expedientes Clínicos y Agendamiento Médico.</p>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2 style="margin-bottom: 30px; color: var(--text-main); font-size: 1.8rem;">Iniciar Sesión</h2>
            
            <?php if (!empty($error)): ?>
                <div style="padding: 12px; background-color: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['success'])): ?>
                <div style="padding: 12px; background-color: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="ejemplo@cajacordes.com" required>
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password" style="margin-bottom: 0;">Contraseña</label>
                        <a href="<?= BASE_URL ?>/password/reset" style="font-size: 0.85rem; color: var(--primary-color); text-decoration: none;">¿Olvidó su contraseña?</a>
                    </div>
                    <input type="password" id="password" name="password" placeholder="••••••••" required style="margin-top: 5px;">
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 10px; font-size: 1.1rem; padding: 14px;">Ingresar al Sistema</button>
            </form>

            <div class="test-credentials">
                <strong>Credenciales de Prueba Activas:</strong>
                <p style="font-size: 0.8rem; margin-top: 5px;">Nota: El sistema SÍ evalúa el cifrado real de alta seguridad (bcrypt) automáticamente.</p>
                <ul>
                    <li>Directivo/Admin: <b>admin@cajacordes.com</b><br> Clave: <b>admin123</b></li>
                    <li>Médico: <b>medico@cajacordes.com</b><br> Clave: <b>medico123</b></li>
                    <li>Paciente: <b>paciente@cajacordes.com</b><br> Clave: <b>paciente123</b></li>
                    <li>Farmacéutico: <b>farmacia@cajacordes.com</b><br> Clave: <b>farmacia123</b></li>
                    <li>Laboratorista: <b>laboratorio@cajacordes.com</b><br> Clave: <b>lab123</b></li>
                </ul>
            </div>
        </div>
    </div>

</div>
<?php require_once '../views/layouts/footer.php'; ?>
