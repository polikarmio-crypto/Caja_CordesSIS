<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Mi Perfil</h1>
                <p>Gestiona tu información personal y de seguridad.</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="padding: 15px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
            
            <!-- Columna Izquierda: Tarjeta de Avatar y Rol -->
            <div class="card" style="text-align: center; padding: 30px;">
                <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                    <?php 
                    $avatarPath = !empty($user['foto_perfil']) ? $user['foto_perfil'] : 'default_avatar.png';
                    ?>
                    <img id="avatarPreview" src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($avatarPath) ?>" 
                         alt="Foto de perfil" 
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: var(--shadow-md);">
                </div>
                <h2 style="margin: 0 0 5px; color: var(--primary-dark);"><?= htmlspecialchars(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?></h2>
                <p style="margin: 0 0 15px; color: var(--text-muted); font-size: 0.95em;"><?= htmlspecialchars($user['email']) ?></p>
                <span style="padding: 6px 16px; border-radius: 20px; background: var(--primary-color); color: white; font-weight: 600; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?= htmlspecialchars($user['rol_nombre']) ?>
                </span>

                <div style="margin-top: 30px; text-align: left; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="font-size: 1.05em; margin-bottom: 12px; color: var(--text-color);">Información del Sistema</h3>
                    <div style="font-size: 0.88em; color: var(--text-muted); display: grid; gap: 8px;">
                        <div><strong>ID de Usuario:</strong> #<?= htmlspecialchars($user['id']) ?></div>
                        <div><strong>Creado el:</strong> <?= date('d/m/Y H:i', strtotime($user['creado_en'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Información y Detalle de Rol -->
            <div class="card" style="padding: 30px;">
                <form action="<?= BASE_URL ?>/perfil/edit" method="POST" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="existing_avatar" value="<?= htmlspecialchars($user['foto_perfil']) ?>">
                    
                    <h2 style="margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; font-size: 1.3em;">
                        Información de Cuenta
                    </h2>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label style="font-weight: 600;">Nombres *</label>
                            <input type="text" name="nombres" value="<?= htmlspecialchars($user['nombres'] ?? '') ?>" required
                                   style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600;">Apellidos *</label>
                            <input type="text" name="apellidos" value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>" required
                                   style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label style="font-weight: 600;">Correo Electrónico *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                                   style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600;">Cambiar Foto de Perfil</label>
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" onchange="previewImage(this)"
                                   style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; align-items: end;">
                        <div class="form-group">
                            <label style="font-weight: 600;">Contraseña *</label>
                            <div style="position: relative; display: flex; gap: 10px;">
                                <input type="password" id="current_password_display" value="••••••••" readonly
                                       style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: #e2e8f0; color: #475569; pointer-events: none;">
                                <button type="button" class="btn btn-outline" onclick="openVerifyModal()" style="white-space: nowrap; padding: 0 15px;">
                                    Revelar
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600;">Nueva Contraseña (Opcional)</label>
                            <input type="password" name="new_password" id="new_password_input" placeholder="Min. 4 caracteres" disabled
                                   style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: #f1f5f9; color: #475569;">
                        </div>
                    </div>

                    <!-- Datos Específicos por Rol -->
                    <?php if ($user['rol_nombre'] === 'Paciente' && $paciente): ?>
                        <h2 style="margin-top: 35px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; font-size: 1.3em;">
                            Información de Paciente
                        </h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="font-weight: 600;">Cédula de Identidad (CI)</label>
                                <input type="text" value="<?= htmlspecialchars($paciente['ci']) ?>" readonly
                                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: #e2e8f0; color: #475569;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600;">Fecha de Nacimiento *</label>
                                <input type="date" name="fecha_nac" value="<?= htmlspecialchars($paciente['fecha_nac']) ?>" required
                                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px; max-width: 50%;">
                            <label style="font-weight: 600;">Número de Teléfono principal</label>
                            <input type="text" name="telefono" value="<?= htmlspecialchars(!empty($paciente['telefonos']) ? $paciente['telefonos'][0] : '') ?>"
                                   style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-color);">
                        </div>
                    
                    <?php elseif ($user['rol_nombre'] === 'Médico' && $medico): ?>
                        <h2 style="margin-top: 35px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; font-size: 1.3em;">
                            Información Profesional del Médico
                        </h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="font-weight: 600;">Licencia Médica</label>
                                <input type="text" value="<?= htmlspecialchars($medico['licencia_medica']) ?>" readonly
                                       style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: #e2e8f0; color: #475569;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600;">Especialidades Asignadas</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                    <?php if (empty($medico['especialidades'])): ?>
                                        <span style="font-style: italic; color: var(--text-muted);">Sin especialidad asignada</span>
                                    <?php else: ?>
                                        <?php foreach ($medico['especialidades'] as $esp): ?>
                                            <span style="padding: 4px 10px; border-radius: 6px; background: #e0f2fe; color: #0369a1; font-size: 0.85em; font-weight: 600;">
                                                <?= htmlspecialchars($esp['nombre']) ?> 
                                                <?= $esp['parent_nombre'] ? ' (Sub de ' . htmlspecialchars($esp['parent_nombre']) . ')' : ' (Raíz)' ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-weight: 600;">Horarios de Turno Asignados</label>
                            <table style="width: 100%; border-collapse: collapse; margin-top: 8px; text-align: left; font-size: 0.9em;">
                                <thead>
                                    <tr style="border-bottom: 1.5px solid var(--border-color);">
                                        <th style="padding: 8px 4px;">Día</th>
                                        <th style="padding: 8px 4px;">Hora Inicio</th>
                                        <th style="padding: 8px 4px;">Hora Fin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($horarios)): ?>
                                        <tr><td colspan="3" style="padding: 10px 4px; font-style: italic; color: var(--text-muted);">Sin turnos asignados (Solo disponible para Emergencias)</td></tr>
                                    <?php else: ?>
                                        <?php foreach($horarios as $h): ?>
                                            <tr style="border-bottom: 1px solid var(--border-color);">
                                                <td style="padding: 8px 4px; text-transform: capitalize;"><strong><?= htmlspecialchars($h['dia_semana']) ?></strong></td>
                                                <td style="padding: 8px 4px;"><?= date('H:i', strtotime($h['hora_inicio'])) ?></td>
                                                <td style="padding: 8px 4px;"><?= date('H:i', strtotime($h['hora_fin'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 35px; text-align: right;">
                        <button type="submit" class="btn" style="padding: 12px 30px; font-size: 1.05em;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Modal de Verificación de Contraseña -->
<div id="verifyPasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
    <div style="background:white; padding:30px; border-radius:16px; width:400px; box-shadow: var(--shadow-lg); color: #1e293b;">
        <h2 style="margin-top:0; margin-bottom:12px; color: var(--primary-dark);">Verificar Identidad</h2>
        <p style="margin-bottom:20px; font-size: 0.9em; color: #64748b;">Para poder visualizar y modificar tu contraseña, por favor ingresa tu contraseña actual:</p>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Contraseña Actual</label>
            <input type="password" id="modal_verify_password" placeholder="••••••••" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
            <div id="verify_error_msg" style="color: #dc2626; font-size: 0.85em; margin-top: 5px; display: none;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-outline" onclick="closeVerifyModal()">Cancelar</button>
            <button type="button" class="btn" onclick="submitVerifyPassword()">Verificar</button>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function openVerifyModal() {
    document.getElementById('modal_verify_password').value = '';
    document.getElementById('verify_error_msg').style.display = 'none';
    document.getElementById('verifyPasswordModal').style.display = 'flex';
}

function closeVerifyModal() {
    document.getElementById('verifyPasswordModal').style.display = 'none';
}

function submitVerifyPassword() {
    var password = document.getElementById('modal_verify_password').value;
    var errorMsgDiv = document.getElementById('verify_error_msg');
    
    if (password === '') {
        errorMsgDiv.innerText = 'Por favor ingrese su contraseña.';
        errorMsgDiv.style.display = 'block';
        return;
    }

    var formData = new FormData();
    formData.append('password', password);

    fetch('<?= BASE_URL ?>/perfil/verify', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Verificación exitosa: revelar contraseña y habilitar nueva contraseña
            var displayPass = document.getElementById('current_password_display');
            displayPass.value = data.password;
            displayPass.type = 'text';
            displayPass.style.background = '#f8fafc';
            displayPass.style.color = '#0f172a';

            var newPassInput = document.getElementById('new_password_input');
            newPassInput.disabled = false;
            newPassInput.style.background = 'var(--secondary-color)';
            newPassInput.style.color = 'var(--text-color)';
            
            closeVerifyModal();
        } else {
            errorMsgDiv.innerText = data.message || 'Contraseña incorrecta.';
            errorMsgDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorMsgDiv.innerText = 'Error al conectar con el servidor.';
        errorMsgDiv.style.display = 'block';
    });
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
