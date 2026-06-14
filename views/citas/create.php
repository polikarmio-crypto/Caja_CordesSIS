<?php 
require_once __DIR__ . '/../layouts/header.php';

// Obtener lista de pacientes
$conn = Database::getInstance();
$pacientesList = $conn->query("SELECT id, nombres, apellidos, CI FROM pacientes WHERE activo IS NULL OR activo = TRUE ORDER BY nombres, apellidos")->fetchAll();

// Obtener especialidades principales (sin padre)
$especialidadesPrincipales = $conn->query("
    SELECT id, nombre FROM especialidades WHERE parent_id IS NULL ORDER BY nombre
")->fetchAll();

// Obtener todas las especialidades con su padre (para construir el selector jerárquico)
$todasEspecialidades = $conn->query("
    SELECT id, nombre, parent_id FROM especialidades ORDER BY parent_id NULLS FIRST, nombre
")->fetchAll();

$logged_paciente_id = $_SESSION['paciente_id'] ?? null;
$is_paciente       = ($_SESSION['rol_nombre'] ?? '') === 'Paciente';

// URL de retorno: pacientes van al dashboard, resto a /citas
$back_url = $is_paciente ? BASE_URL . '/dashboard' : BASE_URL . '/citas';
?>
<div class="dashboard-layout">
 <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
 <main class="main-content">
  <div class="dashboard-header">
   <div class="welcome-text">
    <h1>Agendar Cita</h1>
    <p>Programa una nueva consulta médica.</p>
   </div>
   <a href="<?= $back_url ?>" class="btn btn-outline">← Volver</a>
  </div>

  <?php if (isset($error)): ?>
  <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;">
   <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="card" style="max-width: 820px;">
   <form action="<?= BASE_URL ?>/citas/create" method="POST" id="formCita">

    <!-- ═══ PACIENTE ═══ -->
    <div class="form-group">
     <label>Paciente</label>
     <?php if ($is_paciente && $logged_paciente_id): ?>
      <?php
        $pac = array_filter($pacientesList, fn($p) => $p['id'] == $logged_paciente_id);
        $pac = reset($pac);
      ?>
      <input type="hidden" name="paciente_id" value="<?= $logged_paciente_id ?>">
      <input type="text"
             value="<?= htmlspecialchars(($pac['nombres'] ?? '') . ' ' . ($pac['apellidos'] ?? '') . ' — CI: ' . ($pac['ci'] ?? '')) ?>"
             disabled
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color); opacity:.85; cursor:not-allowed;">
     <?php else: ?>
      <select name="paciente_id" id="pacienteSelect" required
              style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
       <option value="">Selecciona un paciente…</option>
       <?php foreach($pacientesList as $p): ?>
        <option value="<?= $p['id'] ?>">
         <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'] . ' — CI: ' . $p['ci']) ?>
        </option>
       <?php endforeach; ?>
      </select>
     <?php endif; ?>
    </div>

    <!-- ═══ ESPECIALIDAD PRINCIPAL ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Especialidad</label>
     <select id="selectEspecialidadPrincipal"
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
      <option value="">— Todas las especialidades —</option>
      <?php foreach($especialidadesPrincipales as $ep): ?>
       <option value="<?= $ep['id'] ?>"><?= htmlspecialchars($ep['nombre']) ?></option>
      <?php endforeach; ?>
     </select>
    </div>

    <!-- ═══ SUBESPECIALIDAD (oculto hasta que se elija una especialidad principal con hijos) ═══ -->
    <div class="form-group" id="grupoSubespecialidad" style="margin-top:18px; display:none;">
     <label>Subespecialidad <small style="color:var(--text-muted);">(opcional)</small></label>
     <select id="selectSubespecialidad"
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
      <option value="">— Ver todos los médicos de esta especialidad —</option>
     </select>
    </div>

    <!-- ═══ MÉDICO (cargado vía AJAX) ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Médico</label>
     <div id="medicoLoading" style="display:none; padding:10px; color:var(--text-muted); font-size:0.9em;">
      ⏳ Cargando médicos…
     </div>
     <select name="medico_id" id="medicoSelect" required
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
      <option value="">Selecciona un médico…</option>
     </select>
     <div id="medicoInfo" style="margin-top:8px; padding:10px 14px; background:var(--secondary-color); border-radius:10px; font-size:0.87em; color:var(--text-muted); display:none;"></div>
    </div>

    <!-- ═══ MODALIDAD ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Modalidad de Consulta</label>
     <select name="modalidad" required
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
      <option value="presencial">Presencial (En Consultorio)</option>
      <option value="virtual">Virtual (Telemedicina via Google Meet)</option>
     </select>
    </div>

    <!-- ═══ TIPO DE CITA ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Tipo de Cita</label>
     <select name="tipo" id="tipoCita" required onchange="toggleHorarioInfo(this.value)"
             style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
      <option value="normal">Cita Normal (valida horario del médico)</option>
      <option value="emergencia">Emergencia (atención 24/7 — sin restricción de horario)</option>
     </select>
    </div>

    <!-- Avisos de tipo de cita -->
    <div id="infoHorarioNormal" style="padding:12px 16px; background:var(--secondary-color); border-left:4px solid #00ba8b; border-radius:8px; margin-bottom:16px; font-size:0.9em; color:var(--text-muted);">
     Las citas normales deben ser dentro del turno del médico y <strong>antes de las 18:00</strong>.
    </div>
    <div id="infoHorarioEmergencia" style="display:none; padding:12px 16px; background:#fff3cd; border-left:4px solid #f59e0b; border-radius:8px; margin-bottom:16px; font-size:0.9em; color:#92400e;">
     <strong>Emergencia:</strong> Se puede agendar a cualquier hora del día. El médico será contactado independientemente de su turno.
    </div>

    <!-- ═══ FECHA Y HORA ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Fecha y Hora</label>
     <?php $min_date = date('Y-m-d\T00:00', strtotime('+1 day')); ?>
     <input type="datetime-local" name="fecha_hora" id="fechaHoraInput"
            min="<?= $min_date ?>" required
            style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color);">
     <div id="alertaHorario" style="display:none; margin-top:8px; padding:10px 14px; background:#fee2e2; color:#991b1b; border-radius:8px; font-size:0.88em;"></div>
    </div>

    <!-- ═══ MOTIVO ═══ -->
    <div class="form-group" style="margin-top:18px;">
     <label>Motivo de Consulta <small style="color:var(--text-muted);">(mínimo 5 caracteres)</small></label>
     <textarea name="motivo" rows="3" minlength="5" required
               style="width:100%; padding:14px 18px; border-radius:12px; border:2px solid transparent; background:var(--secondary-color); font-family:inherit; resize:vertical;"></textarea>
    </div>

    <div style="margin-top:28px; text-align:right;">
     <button type="submit" class="btn" id="btnAgendar">Agendar Cita</button>
    </div>
   </form>
  </div>
 </main>
</div>

<!-- Datos de especialidades para JS (jerarquía) -->
<script>
const ESPECIALIDADES = <?= json_encode($todasEspecialidades) ?>;
const BASE_URL = '<?= BASE_URL ?>';

// ──────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────
function getHijos(padreId) {
    return ESPECIALIDADES.filter(e => String(e.padre_id) === String(padreId));
}

// ──────────────────────────────────────────────────────────
// Tipo de cita — info aviso
// ──────────────────────────────────────────────────────────
function toggleHorarioInfo(tipo) {
    document.getElementById('infoHorarioNormal').style.display    = (tipo === 'normal') ? 'block' : 'none';
    document.getElementById('infoHorarioEmergencia').style.display = (tipo === 'emergencia') ? 'block' : 'none';
    validarFechaHora();
}

// ──────────────────────────────────────────────────────────
// Validación de hora límite 18:00 para citas normales
// ──────────────────────────────────────────────────────────
function validarFechaHora() {
    const val  = document.getElementById('fechaHoraInput').value;
    const tipo = document.getElementById('tipoCita').value;
    const alerta = document.getElementById('alertaHorario');
    if (!val || tipo === 'emergencia') { alerta.style.display = 'none'; return; }
    const hora = parseInt(val.split('T')[1]?.split(':')[0] ?? 0, 10);
    const min  = parseInt(val.split('T')[1]?.split(':')[1] ?? 0, 10);
    if (hora > 18 || (hora === 18 && min > 0)) {
        alerta.textContent = '⚠️ Las citas normales deben ser agendadas antes de las 18:00. Cambia la hora o selecciona tipo Emergencia.';
        alerta.style.display = 'block';
    } else {
        alerta.style.display = 'none';
    }
}
document.getElementById('fechaHoraInput').addEventListener('change', validarFechaHora);
document.getElementById('tipoCita').addEventListener('change', e => toggleHorarioInfo(e.target.value));

// ──────────────────────────────────────────────────────────
// Carga de médicos por especialidad vía AJAX
// ──────────────────────────────────────────────────────────
let medicoData = []; // cache de la última respuesta

function cargarMedicos(especialidadId) {
    const select  = document.getElementById('medicoSelect');
    const loading = document.getElementById('medicoLoading');
    const info    = document.getElementById('medicoInfo');

    select.style.display  = 'none';
    loading.style.display = 'block';
    info.style.display    = 'none';
    select.innerHTML      = '<option value="">Cargando…</option>';

    const url = `${BASE_URL}/api/medicos-por-especialidad?especialidad_id=${especialidadId || 0}`;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            medicoData = data;
            select.innerHTML = '<option value="">Selecciona un médico…</option>';
            if (data.length === 0) {
                select.innerHTML += '<option value="" disabled>— Sin médicos disponibles para esta especialidad —</option>';
            } else {
                data.forEach(m => {
                    const esp  = m.especialidad ? ` (${m.especialidad})` : '';
                    select.innerHTML += `<option value="${m.id}">${m.nombre}${esp}</option>`;
                });
            }
            loading.style.display = 'none';
            select.style.display  = '';
        })
        .catch(() => {
            select.innerHTML      = '<option value="">Error al cargar médicos</option>';
            loading.style.display = 'none';
            select.style.display  = '';
        });
}

// Al cambiar médico, mostrar sus horarios
document.getElementById('medicoSelect').addEventListener('change', function() {
    const info = document.getElementById('medicoInfo');
    const med  = medicoData.find(m => String(m.id) === this.value);
    if (med && med.horarios) {
        info.textContent   = '🕐 Horarios: ' + med.horarios;
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
});

// ──────────────────────────────────────────────────────────
// Cambio en especialidad PRINCIPAL
// ──────────────────────────────────────────────────────────
document.getElementById('selectEspecialidadPrincipal').addEventListener('change', function() {
    const padreId = this.value;
    const grupoSub = document.getElementById('grupoSubespecialidad');
    const selectSub = document.getElementById('selectSubespecialidad');

    selectSub.innerHTML = '<option value="">— Ver todos los médicos de esta especialidad —</option>';

    const hijos = padreId ? getHijos(padreId) : [];
    if (hijos.length > 0) {
        hijos.forEach(h => {
            selectSub.innerHTML += `<option value="${h.id}">${h.nombre}</option>`;
        });
        grupoSub.style.display = '';
    } else {
        grupoSub.style.display = 'none';
    }

    cargarMedicos(padreId);
});

// ──────────────────────────────────────────────────────────
// Cambio en SUBESPECIALIDAD
// ──────────────────────────────────────────────────────────
document.getElementById('selectSubespecialidad').addEventListener('change', function() {
    const subId = this.value;
    const padreId = document.getElementById('selectEspecialidadPrincipal').value;
    cargarMedicos(subId || padreId);
});

// ──────────────────────────────────────────────────────────
// Carga inicial: todos los médicos
// ──────────────────────────────────────────────────────────
cargarMedicos(0);

// ──────────────────────────────────────────────────────────
// Prevenir envío si hora > 18:00 en cita normal
// ──────────────────────────────────────────────────────────
document.getElementById('formCita').addEventListener('submit', function(e) {
    const val  = document.getElementById('fechaHoraInput').value;
    const tipo = document.getElementById('tipoCita').value;
    if (val && tipo !== 'emergencia') {
        const hora = parseInt(val.split('T')[1]?.split(':')[0] ?? 0, 10);
        const min  = parseInt(val.split('T')[1]?.split(':')[1] ?? 0, 10);
        if (hora > 18 || (hora === 18 && min > 0)) {
            e.preventDefault();
            document.getElementById('alertaHorario').textContent = '⚠️ Las citas normales deben ser agendadas antes de las 18:00.';
            document.getElementById('alertaHorario').style.display = 'block';
            document.getElementById('fechaHoraInput').scrollIntoView({ behavior: 'smooth' });
        }
    }
});
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
