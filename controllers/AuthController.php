<?php
/**
 * AuthController — Gestiona autenticación, perfil y recuperación de contraseña.
 * Las consultas SQL están delegadas a AuthRepository.
 */
class AuthController {

    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        require_once '../views/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = $_POST['email']    ?? '';
            $password = $_POST['password'] ?? '';

            $userModel = new User();
            $user      = $userModel->login($email, $password);

            if ($user === 'blocked') {
                AppLogger::security(AppLogger::WARNING, "Cuenta bloqueada por intentos fallidos", ['email' => $email]);
                $error = "Esta cuenta ha sido bloqueada temporalmente por seguridad tras 3 intentos fallidos. Inténtelo más tarde (10 min).";
                require_once '../views/auth/login.php';
                return;
            }

            if ($user) {
                $code = $userModel->generate2FACode($user['id']);

                $_SESSION['temp_user_id']  = $user['id'];
                $_SESSION['mock_2fa_code'] = $code;

                AppLogger::security(AppLogger::INFO, "Código 2FA generado para usuario", ['user_id' => $user['id']]);
                log_activity($user['id'], "Generó código 2FA: $code", 'usuarios');

                header('Location: ' . BASE_URL . '/login/2fa');
                exit;
            } else {
                $existingUser = $userModel->findByEmail($email);
                if ($existingUser) {
                    $attemptsLeft = 3 - ($existingUser['intentos_fallidos']);
                    if ($attemptsLeft <= 0) {
                        AppLogger::security(AppLogger::WARNING, "Cuenta bloqueada tras 3 intentos", ['email' => $email]);
                        $error = "Credenciales incorrectas. Cuenta bloqueada temporalmente por 3 intentos fallidos.";
                    } else {
                        AppLogger::security(AppLogger::NOTICE, "Intento de login fallido", ['email' => $email, 'intentos_restantes' => $attemptsLeft]);
                        $error = "Credenciales incorrectas. Le quedan $attemptsLeft intentos antes de bloquear su cuenta.";
                    }
                } else {
                    AppLogger::security(AppLogger::NOTICE, "Login con email inexistente", ['email' => $email]);
                    $error = "Credenciales incorrectas.";
                }
                require_once '../views/auth/login.php';
            }
        }
    }

    public function show2FA() {
        if (!isset($_SESSION['temp_user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        require_once '../views/auth/two_factor.php';
    }

    public function verify2FA() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code   = $_POST['code']  ?? '';
            $userId = $_SESSION['temp_user_id'] ?? null;

            if (!$userId) {
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $userModel = new User();
            if ($userModel->verify2FACode($userId, $code)) {
                $user = $userModel->findById($userId);

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['rol_nombre'] = $user['rol_nombre'];
                $_SESSION['email']      = $user['email'];

                if ($user['rol_nombre'] === 'Paciente') {
                    $pacModel  = new Paciente();
                    $paciente  = $pacModel->findByUsuarioId($user['id']);
                    if ($paciente) {
                        $_SESSION['paciente_id'] = $paciente['id'];
                    }
                }

                unset($_SESSION['temp_user_id'], $_SESSION['mock_2fa_code']);

                AppLogger::security(AppLogger::INFO, "Inicio de sesión exitoso con 2FA", ['user_id' => $user['id']]);
                log_activity($user['id'], 'Inicio de sesión exitoso con 2FA', 'usuarios');

                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            } else {
                AppLogger::security(AppLogger::WARNING, "Código 2FA incorrecto o expirado", ['user_id' => $userId]);
                $error = "Código de verificación incorrecto o expirado.";
                require_once '../views/auth/two_factor.php';
            }
        }
    }

    public function showReset() {
        require_once '../views/auth/reset.php';
    }

    public function sendResetLink() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email     = $_POST['email'] ?? '';
            $userModel = new User();
            $token     = $userModel->generateResetToken($email);

            if ($token) {
                $resetLink = BASE_URL . "/password/change?token=" . $token;
                $_SESSION['mock_reset_link'] = $resetLink;
                AppLogger::security(AppLogger::INFO, "Token de restablecimiento generado", ['email' => $email]);
                $success = "Se ha generado un enlace de recuperación. En producción se enviaría por correo electrónico.";
            } else {
                AppLogger::security(AppLogger::WARNING, "Reset solicitado para email no existente", ['email' => $email]);
                $error = "No existe ninguna cuenta registrada con ese correo electrónico.";
            }
            require_once '../views/auth/reset.php';
        }
    }

    public function showChangePassword() {
        if (isset($_SESSION['user_id'])) {
            $token = '';
            require_once '../views/auth/change_password.php';
        } else {
            $token     = $_GET['token'] ?? '';
            $userModel = new User();
            $user      = $userModel->verifyResetToken($token);

            if ($user) {
                require_once '../views/auth/change_password.php';
            } else {
                $error = "El enlace de recuperación es inválido o ha expirado.";
                require_once '../views/auth/reset.php';
            }
        }
    }

    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token     = $_POST['token']    ?? '';
            $password  = $_POST['password'] ?? '';
            $userModel = new User();

            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $hash   = password_hash($password, PASSWORD_BCRYPT);
                if ($userModel->updatePassword($userId, $hash)) {
                    AppLogger::security(AppLogger::INFO, "Contraseña actualizada desde perfil", ['user_id' => $userId]);
                    log_activity($userId, 'Cambió su contraseña desde su perfil', 'usuarios');
                    header('Location: ' . BASE_URL . '/dashboard?success=Contraseña+actualizada+correctamente');
                    exit;
                }
                $error = "Error al actualizar la contraseña.";
                require_once '../views/auth/change_password.php';
            } else {
                $user = $userModel->verifyResetToken($token);
                if ($user) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    if ($userModel->updatePassword($user['id'], $hash)) {
                        AppLogger::security(AppLogger::INFO, "Contraseña restablecida con token", ['user_id' => $user['id']]);
                        log_activity($user['id'], 'Restableció su contraseña exitosamente', 'usuarios');
                        header('Location: ' . BASE_URL . '/?success=Contraseña+actualizada+correctamente');
                        exit;
                    }
                }
                $error = "Error al actualizar la contraseña o el token es inválido.";
                require_once '../views/auth/reset.php';
            }
        }
    }

    public function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        AppLogger::security(AppLogger::INFO, "Cierre de sesión", ['user_id' => $userId]);
        session_destroy();
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    public function showProfile() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $repo   = new AuthRepository();

        $user = $repo->findUserWithRoleById($userId);
        if (!$user) {
            session_destroy();
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $medico   = null;
        $paciente = null;
        $horarios = [];

        if ($user['rol_nombre'] === 'Médico') {
            $medico = $repo->findMedicoByUserId($userId);
            if ($medico) {
                $medico['especialidades'] = $repo->findEspecialidadesByMedicoId($medico['id']);
                $horarios                 = $repo->findHorariosByMedicoId($medico['id']);
            }
        } elseif ($user['rol_nombre'] === 'Paciente') {
            $paciente = $repo->findPacienteByUserId($userId);
            if ($paciente) {
                $paciente['telefonos'] = $repo->findTelefonosByPacienteId($paciente['id']);
            }
        }

        require_once __DIR__ . '/../views/auth/profile.php';
    }

    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $userId    = $_SESSION['user_id'];
        $nombres   = trim($_POST['nombres']   ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        $conn = Database::getInstance();
        $repo = new AuthRepository();

        try {
            if (empty($nombres) || empty($apellidos) || empty($email)) {
                throw new Exception("Todos los campos obligatorios deben completarse.");
            }
            if (strlen($nombres) < 2 || strlen($apellidos) < 2) {
                throw new Exception("El nombre y los apellidos deben tener al menos 2 caracteres.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del correo electrónico es inválido.");
            }

            $conn->beginTransaction();

            // Manejo de avatar
            $foto_perfil = $_POST['existing_avatar'] ?? 'default_avatar.png';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath   = $_FILES['avatar']['tmp_name'];
                $fileName      = $_FILES['avatar']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName   = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../public/uploads/avatars/';
                    if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
                    $dest_path = $uploadFileDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $foto_perfil = $newFileName;
                    }
                } else {
                    throw new Exception("Extensión de imagen no permitida. Use JPG, JPEG, PNG o WEBP.");
                }
            }

            // Actualizar usuario
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 4) {
                    throw new Exception("La nueva contraseña debe tener al menos 4 caracteres.");
                }
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $repo->updateUserWithPassword($userId, $nombres, $apellidos, $email, $hash, $foto_perfil);
            } else {
                $repo->updateUserWithoutPassword($userId, $nombres, $apellidos, $email, $foto_perfil);
            }

            // Si es Paciente, actualizar tabla pacientes
            $userInfo = $repo->findUserPacienteInfo($userId);
            if ($userInfo && $userInfo['rol_nombre'] === 'Paciente' && $userInfo['id']) {
                $fecha_nac = $_POST['fecha_nac'] ?? '';
                $telefono  = trim($_POST['telefono'] ?? '');
                $repo->updatePaciente((int)$userInfo['id'], $nombres, $apellidos, $fecha_nac ?: null);
                if (!empty($telefono)) {
                    $repo->replaceTelefonoPaciente((int)$userInfo['id'], $telefono);
                }
            }

            $_SESSION['email'] = $email;
            AppLogger::info("Perfil actualizado", ['user_id' => $userId]);
            log_activity($userId, 'Actualizó su perfil de usuario', 'usuarios');

            $conn->commit();
            header('Location: ' . BASE_URL . '/perfil?success=Perfil+actualizado+con+exito');
            exit();
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            AppLogger::error("Error al actualizar perfil: " . $e->getMessage(), ['user_id' => $userId]);
            header('Location: ' . BASE_URL . '/perfil?error=' . urlencode($e->getMessage()));
            exit();
        }
    }

    public function verifyPasswordAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
            exit;
        }

        $userId   = $_SESSION['user_id'];
        $password = $_POST['password'] ?? '';
        $repo     = new AuthRepository();
        $hash     = $repo->findPasswordHashByUserId($userId);

        if ($hash && password_verify($password, $hash)) {
            echo json_encode(['success' => true, 'password' => $password]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
        }
        exit;
    }
}
?>
