<?php require_once '../views/layouts/header.php'; ?>
<div class="login-wrapper">
    <div class="login-left">
        <h1>Caja Cordes</h1>
        <p>Sistema de Doble Factor de Autenticación para resguardar la seguridad de la información médica.</p>
    </div>

    <div class="login-right">
        <div class="login-box">
            <h2 style="margin-bottom: 20px; color: var(--text-main); font-size: 1.8rem;">Verificación de Seguridad</h2>
            <p style="margin-bottom: 20px; font-size: 0.95rem; color: var(--text-muted);">
                Hemos enviado un código de verificación de 6 dígitos a su correo electrónico. Por favor, introdúzcalo a continuación.
            </p>

            <?php if (!empty($error)): ?>
                <div style="padding: 12px; background-color: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; border-radius: 4px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['mock_2fa_code'])): ?>
                <div style="padding: 12px; background-color: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem;">
                    🔑 <strong>[Simulador de Correo]</strong> Su código 2FA es: <b style="font-size: 1.2rem; letter-spacing: 2px; color: #15803d;"><?= htmlspecialchars($_SESSION['mock_2fa_code']) ?></b>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login/2fa">
                <div class="form-group">
                    <label for="code">Código de Verificación (6 dígitos)</label>
                    <input type="text" id="code" name="code" placeholder="000000" maxlength="6" pattern="\d{6}" style="text-align: center; font-size: 1.5rem; letter-spacing: 6px; padding: 10px;" required autofocus autocomplete="off">
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 15px; font-size: 1.1rem; padding: 14px;">Verificar Código</button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="<?= BASE_URL ?>/" style="color: var(--primary-color); font-size: 0.9rem; text-decoration: none;">← Volver al Login</a>
            </div>
        </div>
    </div>
</div>
<?php require_once '../views/layouts/footer.php'; ?>
