<?php require_once '../views/layouts/header.php'; ?>

<div class="login-wrapper">
    <canvas id="login-canvas"></canvas>

    <div class="login-layout login-layout--centered">

        <div class="login-card">
            <div class="login-card-accent"></div>

            <div class="login-card-logo">
                <div class="login-brand-icon login-brand-icon--sm">
                    <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="14" cy="14" r="11" stroke="#00ba8b" stroke-width="1.8"/>
                        <circle cx="14" cy="14" r="5"  stroke="#00ba8b" stroke-width="1.8"/>
                        <circle cx="14" cy="14" r="1.8" fill="#00ba8b"/>
                    </svg>
                </div>
                <span class="login-brand-name">Caja Cordes</span>
            </div>

            <h2 class="login-title">Recuperar acceso</h2>
            <p class="login-subtitle">Le enviaremos un enlace para restablecer su contraseña.</p>

            <?php if (!empty($error)): ?>
                <div class="login-alert login-alert--error">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="login-alert login-alert--success">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/password/reset" class="login-form">
                <div class="form-group">
                    <label for="email" class="login-label">Correo electrónico</label>
                    <div class="login-input-wrap">
                        <svg class="login-input-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M1.5 5.5l6.5 4 6.5-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                        <input type="email" id="email" name="email"
                               placeholder="usuario@cajacordes.com"
                               autocomplete="email" required autofocus>
                    </div>
                </div>
                <button type="submit" class="login-btn">Enviar enlace de recuperación</button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="login-link">← Volver al Dashboard</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/" class="login-link">← Volver al inicio de sesión</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/js/login-bg.js"></script>
<?php require_once '../views/layouts/footer.php'; ?>