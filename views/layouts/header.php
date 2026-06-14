<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Caja Cordes - Gestión Clínica</title>

 <?php
 /* ID único por usuario para separar preferencias de tema */
 $themeUserId = $_SESSION['user_id'] ?? $_SESSION['email'] ?? 'guest';
 ?>

 <script>
 (function() {
 var uid = '<?= addslashes($themeUserId) ?>';
 var saved = localStorage.getItem('cc_theme_' + uid);
 var theme = saved || 'dark';
 document.documentElement.setAttribute('data-theme', theme);
 })();
 </script>

 <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
 <link rel="stylesheet" href="<?= BASE_URL ?>/css/theme.css?v=<?= filemtime(__DIR__ . '/../../public/css/theme.css') ?>">
 <script src="<?= BASE_URL ?>/js/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/theme.js') ?>" defer></script>
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
  <a href="<?= BASE_URL ?>/perfil" class="btn btn-header btn-outline">Ver Perfil</a>
 <a href="<?= BASE_URL ?>/logout" class="btn btn-header btn-logout">Cerrar Sesión</a>
 </div>
 </header>
 <?php endif; ?>
