<?php require_once '../views/layouts/header.php'; ?>

<div class="login-wrapper">
    <canvas id="login-canvas"></canvas>

    <div class="login-layout">

        <!-- LADO IZQUIERDO — marca -->
        <div class="login-brand-side">
            <div class="login-brand-logo">
                <div class="login-brand-icon">
                    <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="14" cy="14" r="11" stroke="#00ba8b" stroke-width="1.8"/>
                        <circle cx="14" cy="14" r="5"  stroke="#00ba8b" stroke-width="1.8"/>
                        <circle cx="14" cy="14" r="1.8" fill="#00ba8b"/>
                    </svg>
                </div>
                <div>
                    <span class="login-brand-name">Caja Cordes</span>
                    <span class="login-brand-tag">Sistema de gestión clínica</span>
                </div>
            </div>

            <h1 class="login-headline">
                Gestión clínica<br>
                Sistema de Salud<br>
                <span>Caja Cordes</span>
            </h1>

            <p class="login-brand-desc">
                Control integral de expedientes, agendamiento médico y caja — todo desde un solo lugar.
            </p>

            <div class="login-pills">
                <span class="login-pill">Expedientes</span>
                <span class="login-pill">Farmacia</span>
                <span class="login-pill">Laboratorio</span>
                <span class="login-pill">Agendamiento</span>
            </div>
        </div>

        <!-- CARD DE LOGIN -->
        <div class="login-card">
            <div class="login-card-accent"></div>

            <h2 class="login-title">Bienvenido</h2>
            <p class="login-subtitle">Ingrese sus credenciales para continuar</p>

            <?php if (!empty($error)): ?>
                <div class="login-alert login-alert--error">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['success'])): ?>
                <div class="login-alert login-alert--success">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login" class="login-form">
                <div class="form-group">
                    <label for="email" class="login-label">Correo electrónico</label>
                    <div class="login-input-wrap">
                        <svg class="login-input-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M1.5 5.5l6.5 4 6.5-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                        <input type="email" id="email" name="email"
                               placeholder="usuario@cajacordes.com"
                               autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="login-label-row">
                        <label for="password" class="login-label">Contraseña</label>
                        <a href="<?= BASE_URL ?>/password/reset" class="login-link">¿Olvidó su contraseña?</a>
                    </div>
                    <div class="login-input-wrap">
                        <svg class="login-input-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="3.5" y="7" width="9" height="6" rx="1.3" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M5.5 7V5.5a2.5 2.5 0 015 0V7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <circle cx="8" cy="10" r=".9" fill="currentColor"/>
                        </svg>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="login-btn">Ingresar al sistema</button>
            </form>

            <div class="login-credentials">
                <button class="login-cred-toggle" id="credToggle" type="button"
                        onclick="document.getElementById('credList').classList.toggle('open');this.classList.toggle('active')">
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
                        <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.2"/>
                        <path d="M6.5 5.5v3M6.5 4.2h.01" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                    </svg>
                    Credenciales de prueba
                    <svg class="cred-chevron" width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true">
                        <path d="M2.5 4l3 3 3-3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="login-cred-list" id="credList">
                    <div class="login-cred-item"><span class="cred-role">Admin</span><code>admin@cajacordes.com</code><code>admin123</code></div>
                    <div class="login-cred-item"><span class="cred-role">Médico</span><code>medico@cajacordes.com</code><code>medico123</code></div>
                    <div class="login-cred-item"><span class="cred-role">Paciente</span><code>paciente@cajacordes.com</code><code>paciente123</code></div>
                    <div class="login-cred-item"><span class="cred-role">Farmacia</span><code>farmacia@cajacordes.com</code><code>farmacia123</code></div>
                    <div class="login-cred-item"><span class="cred-role">Lab</span><code>laboratorio@cajacordes.com</code><code>lab123</code></div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/js/login-bg.js"></script>
<?php require_once '../views/layouts/footer.php'; ?>