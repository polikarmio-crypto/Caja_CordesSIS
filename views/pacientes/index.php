<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Pacientes</h1>
                <p>Gestión de expedientes de pacientes.</p>
            </div>
            <a href="<?= BASE_URL ?>/pacientes/create" class="btn">+ Nuevo Paciente</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px;">
                Operación realizada con éxito.
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px;">
            <input type="text" id="searchInput" placeholder="Buscar por CI, Nombre o Apellido..." style="width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid transparent; background: var(--secondary-color); font-size: 1rem; transition: all 0.3s ease;">
        </div>

        <div class="card">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px;">CI</th>
                        <th style="padding: 12px;">Nombres</th>
                        <th style="padding: 12px;">Apellidos</th>
                        <th style="padding: 12px;">Teléfono</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="pacientesTableBody">
                    <?php foreach($pacientes as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;"><?= htmlspecialchars($p['ci']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($p['nombres']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($p['apellidos']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($p['telefono']) ?></td>
                            <td style="padding: 12px;">
                                <a href="<?= BASE_URL ?>/pacientes/<?= $p['id'] ?>/historia" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Ver Historia</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pacientes)): ?>
                        <tr id="emptyRow"><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No hay pacientes registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function(e) {
    let query = e.target.value;
    
    // Highlight effect
    this.style.boxShadow = '0 0 0 4px rgba(0, 122, 94, 0.1)';
    this.style.borderColor = 'var(--primary-color)';
    
    fetch('<?= BASE_URL ?>/pacientes/search?q=' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {
        let tbody = document.getElementById('pacientesTableBody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No se encontraron resultados.</td></tr>';
        } else {
            data.forEach(p => {
                let row = `<tr style="border-bottom: 1px solid var(--border-color); animation: fadeInUp 0.3s ease-out forwards;">
                    <td style="padding: 12px;">${p.ci}</td>
                    <td style="padding: 12px;">${p.nombres}</td>
                    <td style="padding: 12px;">${p.apellidos}</td>
                    <td style="padding: 12px;">${p.telefono ? p.telefono : ''}</td>
                    <td style="padding: 12px;">
                        <a href="<?= BASE_URL ?>/pacientes/${p.id}/historia" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem;">Ver Historia</a>
                    </td>
                </tr>`;
                tbody.innerHTML += row;
            });
        }
    });
});

document.getElementById('searchInput').addEventListener('blur', function() {
    this.style.boxShadow = 'none';
    this.style.borderColor = 'transparent';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
