<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Cordes - Gestión Clínica</title>
    <!-- Local Google Fonts loaded via style.css -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">

</head>
<body>
    <div class="app-container">
    <?php if (isset($_SESSION['user_id'])): ?>
        <header class="top-header">
            <div class="header-logo-section">
                <img src="<?= BASE_URL ?>/logo.png" alt="Caja Cordes Logo" class="header-logo">
                <span class="header-title">Caja Salud Cordes</span>
            </div>
            <div class="header-actions">
                <div class="user-badge">
                    <span class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['rol_nombre'] ?? '') ?></span>
                </div>
                <?php if (($_SESSION['rol_nombre'] ?? '') === 'Paciente'): ?>
                    <a href="<?= BASE_URL ?>/pacientes/edit" class="btn btn-header btn-outline">Ver Perfil</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/password/change" class="btn btn-header btn-outline">Ver Perfil</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/logout" class="btn btn-header btn-logout">Cerrar Sesión</a>
            </div>
        </header>
    <?php endif; ?>
