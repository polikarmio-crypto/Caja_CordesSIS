<?php require_once '../views/layouts/header.php'; ?>

<div class="login-wrapper">
 <canvas id="login-canvas"></canvas>

 <div class="login-layout login-layout--centered">

 <div class="login-card">
 <div class="login-card-accent"></div>

 <div class="login-card-logo">
 <img src="<?= BASE_URL ?>/logo.png" alt="Caja Cordes Logo" style="height: 40px; width: auto; object-fit: contain;">
 <span class="login-brand-name">Caja Cordes</span>
 </div>

 <h2 class="login-title">Verificación de Seguridad</h2>
 <p class="login-subtitle">Hemos enviado un código de verificación de 6 dígitos a su correo electrónico.</p>

 <?php if (!empty($error)): ?>
 <div class="login-alert login-alert--error">
 <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
 <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
 <path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
 </svg>
 <?= htmlspecialchars($error) ?>
 </div>
 <?php endif; ?>

 <?php if (isset($_SESSION['mock_2fa_code'])): ?>
 <div class="login-alert login-alert--success" style="font-size: 0.9rem;">
 🔑 <strong>[Simulador]</strong> Código 2FA: <b style="font-size: 1.1rem; letter-spacing: 2px; color: #e2f5ee; margin-left: 5px;"><?= htmlspecialchars($_SESSION['mock_2fa_code']) ?></b>
 </div>
 <?php endif; ?>

 <form method="POST" action="<?= BASE_URL ?>/login/2fa" class="login-form">
 <div class="form-group">
 <label for="code" class="login-label">Código de Verificación (6 dígitos)</label>
 <div class="login-input-wrap">
 <input type="text" id="code" name="code" placeholder="000000" maxlength="6" pattern="\d{6}" style="text-align: center; font-size: 1.5rem; letter-spacing: 6px; padding: 10px 14px;" required autofocus autocomplete="off">
 </div>
 </div>
 <button type="submit" class="login-btn">Verificar Código</button>
 </form>

 <div style="margin-top: 20px; text-align: center;">
 <a href="<?= BASE_URL ?>/" class="login-link">← Volver al inicio de sesión</a>
 </div>
 </div>

 </div>
</div>

<script src="<?= BASE_URL ?>/js/login-bg.js"></script>
<?php require_once '../views/layouts/footer.php'; ?>
