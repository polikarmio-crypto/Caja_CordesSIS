<?php require_once '../views/layouts/header.php'; ?>
<div class="login-wrapper">
    <div class="login-left">
        <h1>Caja Cordes</h1>
        <p>Recuperación de Contraseña y Seguridad de Acceso.</p>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2 style="margin-bottom: 20px; color: var(--text-main); font-size: 1.8rem;">Recuperar Contraseña</h2>
            <p style="margin-bottom: 20px; font-size: 0.95rem; color: var(--text-muted);">
                Introduzca su correo electrónico registrado y le proporcionaremos un enlace seguro para restablecer su contraseña.
            </p>

            <?php if (!empty($error)): ?>
                <div style="padding: 12px; background-color: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="padding: 12px; background-color: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['mock_reset_link'])): ?>
                <div style="padding: 12px; background-color: #eff6ff; border-left: 4px solid #3b82f6; color: #1e3a8a; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; word-break: break-all;">
                    🔗 <strong>[Simulador de Enlace]</strong> Haga clic para restablecer:<br>
                    <a href="<?= htmlspecialchars($_SESSION['mock_reset_link']) ?>" style="font-weight: bold; color: var(--primary-color);"><?= htmlspecialchars($_SESSION['mock_reset_link']) ?></a>
                </div>
                <?php unset($_SESSION['mock_reset_link']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/password/reset">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="ejemplo@cajacordes.com" required autofocus>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; font-size: 1.1rem; padding: 14px;">Enviar Enlace de Recuperación</button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="<?= BASE_URL ?>/" style="color: var(--primary-color); font-size: 0.9rem; text-decoration: none;">← Volver al Login</a>
            </div>
        </div>
    </div>
</div>
<?php require_once '../views/layouts/footer.php'; ?>
