<?php require_once '../views/layouts/header.php'; ?>
<div class="login-wrapper">
    <div class="login-left">
        <h1>Caja Cordes</h1>
        <p>Restablecimiento Seguro de Contraseña.</p>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2 style="margin-bottom: 20px; color: var(--text-main); font-size: 1.8rem;">Nueva Contraseña</h2>
            <p style="margin-bottom: 20px; font-size: 0.95rem; color: var(--text-muted);">
                Defina su nueva contraseña de alta seguridad para acceder al sistema.
            </p>

            <?php if (!empty($error)): ?>
                <div style="padding: 12px; background-color: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/password/change">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required autofocus>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; font-size: 1.1rem; padding: 14px;">Restablecer e Iniciar Sesión</button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="<?= BASE_URL ?>/" style="color: var(--primary-color); font-size: 0.9rem; text-decoration: none;">← Cancelar</a>
            </div>
        </div>
    </div>
</div>
<?php require_once '../views/layouts/footer.php'; ?>
