 <aside class="sidebar">
 <div class="sidebar-header">
 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
 <path d="M11 2v6h-6v4h6v6h4v-6h6v-4h-6v-6h-4z"/>
 </svg>
 Caja Cordes
 </div>
 <nav>
 <a href="<?= BASE_URL ?>/dashboard" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">Mi Portal</a>
 
 <?php 
 $rol = $_SESSION['rol_nombre'] ?? '';
 $isAdminOrDir = ($rol === 'Administrativo' || $rol === 'Directivo');
 $isFarmaceutico = (strpos($rol, 'Farmac') !== false);
 $isLaboratorista = ($rol === 'Laboratorista');
 $isMedico = ($rol === 'Médico');

 $uri = $_SERVER['REQUEST_URI'];
 
 // Mapear qué grupos de acordeón deben estar autoabiertos según la URL activa
 $clinicaOpen = (strpos($uri, 'paciente') !== false || strpos($uri, 'cita') !== false || strpos($uri, 'horario') !== false || strpos($uri, 'hospitalizacion') !== false || strpos($uri, 'laboratorio') !== false) ? 'open' : '';
 $farmaciaOpen = (strpos($uri, 'farmacia') !== false || strpos($uri, 'insumo') !== false || strpos($uri, 'facturacion') !== false) ? 'open' : '';
 $adminOpen = (strpos($uri, 'sucursal') !== false) ? 'open' : '';
 ?>
 
 <?php if ($isAdminOrDir): ?>
 <!-- Grupo 1: Gestión Clínica -->
 <details class="sidebar-group" <?= $clinicaOpen ?>>
 <summary class="group-title">Gestión Clínica</summary>
 <div class="group-items">
 <a href="<?= BASE_URL ?>/pacientes" class="nav-link <?= strpos($uri, 'paciente') !== false ? 'active' : '' ?>">Pacientes</a>
 <a href="<?= BASE_URL ?>/medicos" class="nav-link <?= strpos($uri, 'medico') !== false && strpos($uri, 'horario') === false && strpos($uri, 'ausencia') === false ? 'active' : '' ?>">Médicos</a>
 <a href="<?= BASE_URL ?>/citas" class="nav-link <?= strpos($uri, 'cita') !== false ? 'active' : '' ?>">Citas Médicas</a>
 <a href="<?= BASE_URL ?>/hospitalizacion" class="nav-link <?= strpos($uri, 'hospitalizacion') !== false ? 'active' : '' ?>">Hospitalización (Camas)</a>
 <a href="<?= BASE_URL ?>/laboratorio" class="nav-link <?= strpos($uri, 'laboratorio') !== false ? 'active' : '' ?>">Laboratorio Clínico</a>
 <a href="<?= BASE_URL ?>/ausencias" class="nav-link <?= strpos($uri, 'ausencias') !== false ? 'active' : '' ?>">Ausencias Médicas</a>
 </div>
 </details>

 <!-- Grupo 2: Farmacia y Suministros -->
 <details class="sidebar-group" <?= $farmaciaOpen ?>>
 <summary class="group-title">Farmacia e Insumos</summary>
 <div class="group-items">
 <a href="<?= BASE_URL ?>/farmacia" class="nav-link <?= strpos($uri, 'farmacia') !== false && strpos($uri, 'recetas') === false ? 'active' : '' ?>">Inventario Farmacia</a>
 <a href="<?= BASE_URL ?>/farmacia/recetas" class="nav-link <?= strpos($uri, 'farmacia/recetas') !== false ? 'active' : '' ?>">Despacho Recetas</a>
 <a href="<?= BASE_URL ?>/insumo" class="nav-link <?= strpos($uri, 'insumo') !== false ? 'active' : '' ?>">Inventario Insumos</a>
 <a href="<?= BASE_URL ?>/facturacion" class="nav-link <?= strpos($uri, 'facturacion') !== false ? 'active' : '' ?>">Facturación</a>
 </div>
 </details>

 <!-- Grupo 3: Administración y Reportes -->
 <?php $adminOpen = (strpos($uri, 'sucursal') !== false || strpos($uri, 'backups') !== false) ? 'open' : ''; ?>
 <details class="sidebar-group" <?= $adminOpen ?>>
 <summary class="group-title">Administración</summary>
 <div class="group-items">
 <a href="<?= BASE_URL ?>/sucursal" class="nav-link <?= strpos($uri, 'sucursal') !== false ? 'active' : '' ?>">Sucursales</a>
 <a href="<?= BASE_URL ?>/reportes/citas" class="nav-link" target="_blank">Reporte CSV Citas</a>
 <a href="<?= BASE_URL ?>/backups" class="nav-link <?= strpos($uri, 'backups') !== false ? 'active' : '' ?>" title="Copias de seguridad de la BD">Backups BD</a>
 </div>
 </details>
 <?php endif; ?>

 <?php if ($isMedico): ?>
 <!-- Grupo Médico -->
 <details class="sidebar-group" <?= $clinicaOpen ?>>
 <summary class="group-title">Mi Consulta Médica</summary>
 <div class="group-items">
 <a href="<?= BASE_URL ?>/citas" class="nav-link <?= strpos($uri, 'cita') !== false ? 'active' : '' ?>">Mi Agenda</a>
 <a href="<?= BASE_URL ?>/pacientes" class="nav-link <?= strpos($uri, 'paciente') !== false ? 'active' : '' ?>">Pacientes</a>
 <a href="<?= BASE_URL ?>/hospitalizacion" class="nav-link <?= strpos($uri, 'hospitalizacion') !== false ? 'active' : '' ?>">Hospitalización</a>
 <a href="<?= BASE_URL ?>/laboratorio" class="nav-link <?= strpos($uri, 'laboratorio') !== false ? 'active' : '' ?>">Laboratorio Clínico</a>
 <a href="<?= BASE_URL ?>/ausencias" class="nav-link <?= strpos($uri, 'ausencias') !== false ? 'active' : '' ?>">Ausencias / Bloqueos</a>
 </div>
 </details>
 <?php endif; ?>

 <?php if ($isFarmaceutico): ?>
 <!-- Grupo Farmacéutico -->
 <details class="sidebar-group" <?= $farmaciaOpen ?>>
 <summary class="group-title">Farmacia e Insumos</summary>
 <div class="group-items">
 <a href="<?= BASE_URL ?>/farmacia" class="nav-link <?= strpos($uri, 'farmacia') !== false && strpos($uri, 'recetas') === false ? 'active' : '' ?>">Inventario Farmacia</a>
 <a href="<?= BASE_URL ?>/farmacia/recetas" class="nav-link <?= strpos($uri, 'farmacia/recetas') !== false ? 'active' : '' ?>">Despacho Recetas</a>
 <a href="<?= BASE_URL ?>/insumo" class="nav-link <?= strpos($uri, 'insumo') !== false ? 'active' : '' ?>">Inventario Insumos</a>
 <a href="<?= BASE_URL ?>/facturacion" class="nav-link <?= strpos($uri, 'facturacion') !== false ? 'active' : '' ?>">Facturación</a>
 </div>
 </details>
 <?php endif; ?>

 <?php if ($isLaboratorista): ?>
 <a href="<?= BASE_URL ?>/laboratorio" class="nav-link <?= strpos($uri, 'laboratorio') !== false ? 'active' : '' ?>">Laboratorio Clínico</a>
 <?php endif; ?>
 </nav>

 <div style="margin-top:auto; padding:16px 12px; display:flex; flex-direction:column; gap:10px;">

 <!-- ══ BOTÓN TOGGLE MODO ══ -->
 <button class="theme-toggle-btn" id="themeToggleBtn" type="button">
 <div class="theme-toggle-icon">
 <!-- Ícono luna (dark mode activo) -->
 <svg id="themeIconMoon" fill="none" viewBox="0 0 13 13" xmlns="http://www.w3.org/2000/svg">
 <path d="M11 7.5A5.5 5.5 0 015.5 2 5.5 5.5 0 100 7.5 5.5 5.5 0 0011 7.5z" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
 </svg>
 <!-- Ícono sol (light mode activo) -->
 <svg id="themeIconSun" fill="none" viewBox="0 0 13 13" xmlns="http://www.w3.org/2000/svg" style="display:none">
 <circle cx="6.5" cy="6.5" r="2.5" stroke="currentColor" stroke-width="1.3"/>
 <path d="M6.5 1v1M6.5 11v1M1 6.5h1M11 6.5h1M2.8 2.8l.7.7M9.5 9.5l.7.7M2.8 10.2l.7-.7M9.5 3.5l.7-.7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
 </svg>
 </div>
 <span id="themeToggleLabel">Modo claro</span>
 </button>

 <!-- Info usuario -->
 <div style="padding:12px;background:var(--bg-active);border-radius:10px;border:1px solid var(--border-color);">
 <p style="font-size:0.82em;font-weight:700;color:var(--text-main);">
 <?= htmlspecialchars($_SESSION['email'] ?? 'Usuario') ?>
 </p>
 <p style="font-size:0.72em;color:var(--text-muted);">
 <?= htmlspecialchars($_SESSION['rol_nombre'] ?? '') ?>
 </p>
 </div>
 </div>

 </aside>
