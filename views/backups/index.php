<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
  <main class="main-content">

    <div class="dashboard-header">
      <div class="welcome-text">
        <h1 style="font-size:1.7rem; font-weight:800; color:var(--text-main);">
          Gestión de Backups
        </h1>
        <p style="color:var(--text-secondary); margin-top:4px; font-size:0.88rem;">
          Copias de seguridad de la base de datos PostgreSQL
        </p>
      </div>
    </div>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success" style="display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:12px; margin-bottom:22px; font-size:0.9rem;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:12px; margin-bottom:22px; font-size:0.9rem;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- ── Tarjeta principal de acción ────────────────────────── -->
    <div class="card" style="margin-bottom:28px;">
      <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">

        <div style="display:flex; align-items:center; gap:16px;">
          <div style="width:54px; height:54px; border-radius:14px;
                      background:linear-gradient(135deg,rgba(0,122,94,0.15),rgba(0,186,139,0.25));
                      display:flex; align-items:center; justify-content:center;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--primary-light)" stroke-width="2">
              <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
          </div>
          <div>
            <h2 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin:0;">
              Backup Manual — Para Demostración
            </h2>
            <p style="color:var(--text-secondary); font-size:0.83rem; margin:4px 0 0;">
              Genera un volcado SQL completo de la base de datos en este momento.
              Los backups automáticos se ejecutan <strong>cada semana</strong> mediante una tarea programada.
            </p>
          </div>
        </div>

        <!-- Botón de demo -->
        <form method="POST" action="<?= BASE_URL ?>/backups/manual"
              onsubmit="return confirmarBackup(this);" style="flex-shrink:0;">
          <button type="submit" id="btn-backup-now"
                  style="display:inline-flex; align-items:center; gap:10px;
                         padding:13px 24px; background:var(--primary-color);
                         color:#fff; border:none; border-radius:12px; font-size:0.95rem;
                         font-weight:700; cursor:pointer; font-family:inherit;
                         transition:all 0.25s ease; box-shadow:0 4px 14px rgba(0,122,94,0.3);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Generar Backup Ahora
          </button>
        </form>

      </div>

      <!-- Info adicional -->
      <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
                  gap:14px; margin-top:22px; padding-top:18px;
                  border-top:1px solid var(--border-color);">
        <div style="background:var(--bg-input); border-radius:10px; padding:12px 16px;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700;">
            Total backups
          </div>
          <div style="font-size:1.6rem; font-weight:800; color:var(--primary-light); margin-top:4px;">
            <?= count($backups) ?>
          </div>
        </div>
        <div style="background:var(--bg-input); border-radius:10px; padding:12px 16px;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700;">
            Último backup
          </div>
          <div style="font-size:0.92rem; font-weight:700; color:var(--text-main); margin-top:4px;">
            <?= !empty($backups) ? $backups[0]['created'] : 'Ninguno aún' ?>
          </div>
        </div>
        <div style="background:var(--bg-input); border-radius:10px; padding:12px 16px;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700;">
            Retención
          </div>
          <div style="font-size:0.92rem; font-weight:700; color:var(--text-main); margin-top:4px;">
            30 días automático
          </div>
        </div>
        <div style="background:var(--bg-input); border-radius:10px; padding:12px 16px;">
          <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:700;">
            Motor BD
          </div>
          <div style="font-size:0.92rem; font-weight:700; color:var(--text-main); margin-top:4px;">
            PostgreSQL (pg_dump)
          </div>
        </div>
      </div>
    </div>

    <!-- ── Lista de backups disponibles ─────────────────────────── -->
    <div class="card">
      <h3 style="font-size:1rem; font-weight:700; color:var(--text-main); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Backups Disponibles
      </h3>

      <?php if (empty($backups)): ?>
        <div style="text-align:center; padding:40px 20px;">
          <div style="margin-bottom:12px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin:auto;display:block;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
          <p style="color:var(--text-muted); font-size:0.92rem;">
            No hay backups generados aún. Haga clic en <strong>"Generar Backup Ahora"</strong> para crear el primero.
          </p>
        </div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px 14px; font-size:0.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); border-bottom:1px solid var(--border-color);">#</th>
              <th style="text-align:left; padding:10px 14px; font-size:0.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); border-bottom:1px solid var(--border-color);">Archivo</th>
              <th style="text-align:left; padding:10px 14px; font-size:0.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); border-bottom:1px solid var(--border-color);">Fecha de Creación</th>
              <th style="text-align:right; padding:10px 14px; font-size:0.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); border-bottom:1px solid var(--border-color);">Tamaño</th>
              <th style="text-align:center; padding:10px 14px; font-size:0.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); border-bottom:1px solid var(--border-color);">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($backups as $i => $backup): ?>
            <tr style="transition:background 0.15s;">
              <td style="padding:11px 14px; color:var(--text-muted); font-size:0.82rem;">
                <?= $i + 1 ?>
              </td>
              <td style="padding:11px 14px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary-light)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <span style="font-size:0.85rem; font-weight:600; color:var(--text-main); font-family:'Courier New',monospace; word-break:break-all;">
                    <?= htmlspecialchars($backup['filename']) ?>
                  </span>
                  <?php if ($i === 0): ?>
                  <span style="font-size:0.65rem; font-weight:700; background:rgba(0,186,139,0.15);
                                color:var(--success); padding:2px 8px; border-radius:999px; white-space:nowrap;">
                    MAS RECIENTE
                  </span>
                  <?php endif; ?>
                </div>
              </td>
              <td style="padding:11px 14px; font-size:0.85rem; color:var(--text-secondary);">
                <?= htmlspecialchars($backup['created']) ?>
              </td>
              <td style="padding:11px 14px; font-size:0.85rem; color:var(--text-secondary); text-align:right;">
                <?= htmlspecialchars($backup['size']) ?>
              </td>
              <td style="padding:11px 14px; text-align:center;">
                <a href="<?= BASE_URL ?>/backups/download?file=<?= urlencode($backup['filename']) ?>"
                   style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px;
                          background:var(--bg-hover); color:var(--primary-light); border:1px solid var(--border-color);
                          border-radius:8px; font-size:0.8rem; font-weight:600; text-decoration:none;
                          transition:all 0.2s ease;"
                   onmouseover="this.style.background='var(--primary-color)';this.style.color='#fff';"
                   onmouseout="this.style.background='var(--bg-hover)';this.style.color='var(--primary-light)';">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  Descargar
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Nota informativa ──────────────────────────────────────── -->
    <div style="margin-top:18px; padding:14px 18px; border-radius:10px;
                background:rgba(234,179,8,0.08); border:1px solid rgba(234,179,8,0.25);">
      <p style="font-size:0.83rem; color:var(--text-secondary); margin:0;">
        <strong>Nota:</strong> Para configurar backups automáticos semanales, agregue la siguiente tarea en el Programador de Tareas de Windows o en un cron:
        <br><br>
        <code style="display:block; margin-top:8px; padding:8px 12px; background:var(--bg-input);
                     border-radius:8px; font-size:0.78rem; color:var(--text-main); word-break:break-all;">
          php <?= htmlspecialchars(dirname(__DIR__)) ?>/public/index.php /backups/manual
        </code>
      </p>
    </div>

  </main>
</div>

<script>
function confirmarBackup(form) {
  const btn = document.getElementById('btn-backup-now');
  if (!confirm('¿Está seguro de generar un backup ahora?\nEsta operación puede tardar unos segundos.')) {
    return false;
  }
  btn.disabled = true;
  btn.innerHTML = `
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
         style="animation:spin 1s linear infinite;">
      <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" opacity=".25"/>
      <path d="M21 12a9 9 0 00-9-9" stroke-linecap="round"/>
    </svg>
    Generando...
  `;
  return true;
}
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
