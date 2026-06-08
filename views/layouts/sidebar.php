    <aside class="sidebar">
        <div class="sidebar-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M11 2v6h-6v4h6v6h4v-6h6v-4h-6v-6h-4z"/>
            </svg>
            Caja Cordes
        </div>
        <nav>
            <a href="<?= BASE_URL ?>/dashboard" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">Dashboard</a>
            
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
                    <summary class="group-title">🏥 Gestión Clínica</summary>
                    <div class="group-items">
                        <a href="<?= BASE_URL ?>/pacientes" class="nav-link <?= strpos($uri, 'paciente') !== false ? 'active' : '' ?>">Pacientes</a>
                        <a href="<?= BASE_URL ?>/citas" class="nav-link <?= strpos($uri, 'cita') !== false ? 'active' : '' ?>">Citas Médicas</a>
                        <a href="<?= BASE_URL ?>/horarios" class="nav-link <?= strpos($uri, 'horario') !== false ? 'active' : '' ?>">Horarios Médicos</a>
                        <a href="<?= BASE_URL ?>/hospitalizacion" class="nav-link <?= strpos($uri, 'hospitalizacion') !== false ? 'active' : '' ?>">Hospitalización (Camas)</a>
                        <a href="<?= BASE_URL ?>/laboratorio" class="nav-link <?= strpos($uri, 'laboratorio') !== false ? 'active' : '' ?>">Laboratorio Clínico</a>
                        <a href="<?= BASE_URL ?>/ausencias" class="nav-link <?= strpos($uri, 'ausencias') !== false ? 'active' : '' ?>">Ausencias Médicas</a>
                    </div>
                </details>

                <!-- Grupo 2: Farmacia y Suministros -->
                <details class="sidebar-group" <?= $farmaciaOpen ?>>
                    <summary class="group-title">💊 Farmacia e Insumos</summary>
                    <div class="group-items">
                        <a href="<?= BASE_URL ?>/farmacia" class="nav-link <?= strpos($uri, 'farmacia') !== false && strpos($uri, 'recetas') === false ? 'active' : '' ?>">Inventario Farmacia</a>
                        <a href="<?= BASE_URL ?>/farmacia/recetas" class="nav-link <?= strpos($uri, 'farmacia/recetas') !== false ? 'active' : '' ?>">Despacho Recetas</a>
                        <a href="<?= BASE_URL ?>/insumo" class="nav-link <?= strpos($uri, 'insumo') !== false ? 'active' : '' ?>">Inventario Insumos</a>
                        <a href="<?= BASE_URL ?>/facturacion" class="nav-link <?= strpos($uri, 'facturacion') !== false ? 'active' : '' ?>">Facturación</a>
                    </div>
                </details>

                <!-- Grupo 3: Administración y Reportes -->
                <details class="sidebar-group" <?= $adminOpen ?>>
                    <summary class="group-title">💼 Administración</summary>
                    <div class="group-items">
                        <a href="<?= BASE_URL ?>/sucursal" class="nav-link <?= strpos($uri, 'sucursal') !== false ? 'active' : '' ?>">Sucursales</a>
                        <a href="<?= BASE_URL ?>/reportes/citas" class="nav-link" target="_blank">⬇️ Reporte CSV Citas</a>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($isMedico): ?>
                <!-- Grupo Médico -->
                <details class="sidebar-group" <?= $clinicaOpen ?>>
                    <summary class="group-title">🏥 Mi Consulta Médica</summary>
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
                    <summary class="group-title">💊 Farmacia e Insumos</summary>
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
            
            <?php if ($rol === 'Paciente'): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="nav-link <?= strpos($uri, 'dashboard') !== false ? 'active' : '' ?>">Mi Portal</a>
                <a href="<?= BASE_URL ?>/pacientes/edit" class="nav-link <?= strpos($uri, 'pacientes/edit') !== false ? 'active' : '' ?>">✍️ Mi Perfil</a>
            <?php endif; ?>
        </nav>

        <div style="margin-top: auto; padding: 20px; flex-shrink: 0;">
            <div style="padding: 15px; background: rgba(0,0,0,0.03); border-radius: 8px; margin-bottom: 10px;">
                <p style="font-size: 0.9em; font-weight: bold;"><?= htmlspecialchars($_SESSION['email'] ?? 'Usuario') ?></p>
                <p style="font-size: 0.8em; color: var(--text-muted);"><?= htmlspecialchars($_SESSION['rol_nombre'] ?? '') ?></p>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="btn btn-outline" style="width: 100%;">Cerrar Sesión</a>
        </div>
    </aside>
