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
            ?>
            
            <?php if ($isAdminOrDir): ?>
                <a href="<?= BASE_URL ?>/pacientes" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'paciente') !== false ? 'active' : '' ?>">Pacientes</a>
                <a href="<?= BASE_URL ?>/citas" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'cita') !== false ? 'active' : '' ?>">Citas</a>
                <a href="<?= BASE_URL ?>/horarios" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'horario') !== false ? 'active' : '' ?>">Horarios Médicos</a>
                <a href="<?= BASE_URL ?>/hospitalizacion" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'hospitalizacion') !== false ? 'active' : '' ?>">Hospitalización (Camas)</a>
                <a href="<?= BASE_URL ?>/sucursal" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'sucursal') !== false ? 'active' : '' ?>">Sucursales</a>
            <?php endif; ?>

            <?php if ($isAdminOrDir || $isLaboratorista || $isMedico): ?>
                <a href="<?= BASE_URL ?>/laboratorio" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'laboratorio') !== false ? 'active' : '' ?>">Laboratorio Clínico</a>
            <?php endif; ?>

            <?php if ($isAdminOrDir || $isFarmaceutico): ?>
                <a href="<?= BASE_URL ?>/farmacia" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'farmacia') !== false && strpos($_SERVER['REQUEST_URI'], 'recetas') === false ? 'active' : '' ?>">Inventario Farmacia</a>
                <a href="<?= BASE_URL ?>/farmacia/recetas" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'farmacia/recetas') !== false ? 'active' : '' ?>">Despacho Recetas</a>
                <a href="<?= BASE_URL ?>/insumo" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'insumo') !== false ? 'active' : '' ?>">Inventario de Insumos</a>
                <a href="<?= BASE_URL ?>/facturacion" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'facturacion') !== false ? 'active' : '' ?>">Facturación</a>
            <?php endif; ?>

            <?php if ($isAdminOrDir): ?>
                <a href="<?= BASE_URL ?>/reportes/citas" class="nav-link" target="_blank" style="margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 15px;">⬇️ Descargar Reporte CSV</a>
            <?php endif; ?>
            
            <?php if ($isMedico): ?>
                <a href="<?= BASE_URL ?>/citas" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'cita') !== false ? 'active' : '' ?>">Mi Agenda</a>
                <a href="<?= BASE_URL ?>/pacientes" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'paciente') !== false ? 'active' : '' ?>">Pacientes (Expedientes)</a>
                <a href="<?= BASE_URL ?>/hospitalizacion" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'hospitalizacion') !== false ? 'active' : '' ?>">Hospitalización</a>
            <?php endif; ?>

            <?php if ($rol === 'Paciente'): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">Mi Portal</a>
            <?php endif; ?>
        </nav>

        <div style="margin-top: auto; padding: 20px;">
            <div style="padding: 15px; background: rgba(0,0,0,0.03); border-radius: 8px; margin-bottom: 10px;">
                <p style="font-size: 0.9em; font-weight: bold;"><?= htmlspecialchars($_SESSION['email'] ?? 'Usuario') ?></p>
                <p style="font-size: 0.8em; color: var(--text-muted);"><?= htmlspecialchars($_SESSION['rol_nombre'] ?? '') ?></p>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="btn btn-outline" style="width: 100%;">Cerrar Sesión</a>
        </div>
    </aside>
