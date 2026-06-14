<?php
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
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $userModel = new User();
            $user = $userModel->login($email, $password);

            if ($user === 'blocked') {
                $error = "Esta cuenta ha sido bloqueada temporalmente por seguridad tras 3 intentos fallidos. Inténtelo más tarde (10 min).";
                require_once '../views/auth/login.php';
                return;
            }

            if ($user) {
                // Sprint 7: Generate 2FA code and redirect to 2FA verification
                $code = $userModel->generate2FACode($user['id']);
                
                // Save in session temp user id
                $_SESSION['temp_user_id'] = $user['id'];
                
                // MOCK EMAIL: save to session for visual mock display
                $_SESSION['mock_2fa_code'] = $code;
                
                if (function_exists('log_activity')) {
                    log_activity($user['id'], "Generó código 2FA: $code", 'usuarios');
                }
                
                header('Location: ' . BASE_URL . '/login/2fa');
                exit;
            } else {
                // Check if user exists to tell if attempts were incremented
                $existingUser = $userModel->findByEmail($email);
                if ($existingUser) {
                    $attemptsLeft = 3 - ($existingUser['intentos_fallidos']);
                    if ($attemptsLeft <= 0) {
                        $error = "Credenciales incorrectas. Cuenta bloqueada temporalmente por 3 intentos fallidos.";
                    } else {
                        $error = "Credenciales incorrectas. Le quedan $attemptsLeft intentos antes de bloquear su cuenta.";
                    }
                } else {
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
            $code = $_POST['code'] ?? '';
            $userId = $_SESSION['temp_user_id'] ?? null;

            if (!$userId) {
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $userModel = new User();
            if ($userModel->verify2FACode($userId, $code)) {
                $user = $userModel->findById($userId);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['rol_nombre'] = $user['rol_nombre'];
                $_SESSION['email'] = $user['email'];
                
                if ($user['rol_nombre'] === 'Paciente') {
                    $pacModel = new Paciente();
                    $paciente = $pacModel->findByUsuarioId($user['id']);
                    if ($paciente) {
                        $_SESSION['paciente_id'] = $paciente['id'];
                    }
                }
                
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['mock_2fa_code']);

                if (function_exists('log_activity')) {
                    log_activity($user['id'], 'Inicio de sesión exitoso con 2FA', 'usuarios');
                }

                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            } else {
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
            $email = $_POST['email'] ?? '';
            $userModel = new User();
            $token = $userModel->generateResetToken($email);

            if ($token) {
                $resetLink = BASE_URL . "/password/change?token=" . $token;
                $_SESSION['mock_reset_link'] = $resetLink; // for easy visual testing!
                $success = "Se ha generado un enlace de recuperación. En producción se enviaría por correo electrónico.";
            } else {
                $error = "No existe ninguna cuenta registrada con ese correo electrónico.";
            }
            require_once '../views/auth/reset.php';
        }
    }

    public function showChangePassword() {
        $token = $_GET['token'] ?? '';
        $userModel = new User();
        $user = $userModel->verifyResetToken($token);

        if ($user) {
            require_once '../views/auth/change_password.php';
        } else {
            $error = "El enlace de recuperación es inválido o ha expirado.";
            require_once '../views/auth/reset.php';
        }
    }

    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $userModel = new User();
            $user = $userModel->verifyResetToken($token);

            if ($user) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                if ($userModel->updatePassword($user['id'], $hash)) {
                    if (function_exists('log_activity')) {
                        log_activity($user['id'], 'Restableció su contraseña exitosamente', 'usuarios');
                    }
                    header('Location: ' . BASE_URL . '/?success=Contraseña+actualizada+correctamente');
                    exit;
                }
            }
            $error = "Error al actualizar la contraseña o el token es inválido.";
            require_once '../views/auth/reset.php';
        }
    }

    public function logout() {
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
        $conn = Database::getInstance();

        $stmtUser = $conn->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id = :id");
        $stmtUser->execute([':id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            session_destroy();
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $medico = null;
        $paciente = null;
        $horarios = [];

        if ($user['rol_nombre'] === 'Médico') {
            $stmtMed = $conn->prepare("SELECT * FROM medicos WHERE usuario_id = :uid");
            $stmtMed->execute([':uid' => $userId]);
            $medico = $stmtMed->fetch(PDO::FETCH_ASSOC);

            if ($medico) {
                $stmtEsp = $conn->prepare("
                    SELECT e.nombre, e.parent_id, p.nombre as parent_nombre
                    FROM medico_especialidades me
                    JOIN especialidades e ON me.especialidad_id = e.id
                    LEFT JOIN especialidades p ON e.parent_id = p.id
                    WHERE me.medico_id = :mid
                ");
                $stmtEsp->execute([':mid' => $medico['id']]);
                $medico['especialidades'] = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

                $stmtHor = $conn->prepare("SELECT * FROM horarios_medicos WHERE medico_id = :mid AND activo = TRUE ORDER BY id");
                $stmtHor->execute([':mid' => $medico['id']]);
                $horarios = $stmtHor->fetchAll(PDO::FETCH_ASSOC);
            }
        } elseif ($user['rol_nombre'] === 'Paciente') {
            $stmtPac = $conn->prepare("SELECT * FROM pacientes WHERE usuario_id = :uid");
            $stmtPac->execute([':uid' => $userId]);
            $paciente = $stmtPac->fetch(PDO::FETCH_ASSOC);

            if ($paciente) {
                $stmtTel = $conn->prepare("SELECT telefono FROM paciente_telefonos WHERE paciente_id = :pid");
                $stmtTel->execute([':pid' => $paciente['id']]);
                $paciente['telefonos'] = $stmtTel->fetchAll(PDO::FETCH_COLUMN);
            }
        }

        require_once __DIR__ . '/../views/auth/profile.php';
    }

    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        $conn = Database::getInstance();

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

            $foto_perfil = $_POST['existing_avatar'] ?? 'default_avatar.png';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../public/uploads/avatars/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    $dest_path = $uploadFileDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $foto_perfil = $newFileName;
                    }
                } else {
                    throw new Exception("Extensión de imagen no permitida. Use JPG, JPEG, PNG o WEBP.");
                }
            }

            if (!empty($newPassword)) {
                if (strlen($newPassword) < 4) {
                    throw new Exception("La nueva contraseña debe tener al menos 4 caracteres.");
                }
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombres = :nom, apellidos = :ape, email = :email, password_hash = :hash, foto_perfil = :avatar WHERE id = :id");
                $stmt->execute([
                    ':nom' => $nombres,
                    ':ape' => $apellidos,
                    ':email' => $email,
                    ':hash' => $hash,
                    ':avatar' => $foto_perfil,
                    ':id' => $userId
                ]);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombres = :nom, apellidos = :ape, email = :email, foto_perfil = :avatar WHERE id = :id");
                $stmt->execute([
                    ':nom' => $nombres,
                    ':ape' => $apellidos,
                    ':email' => $email,
                    ':avatar' => $foto_perfil,
                    ':id' => $userId
                ]);
            }

            $stmtCheck = $conn->prepare("SELECT p.id, u.rol_id, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id LEFT JOIN pacientes p ON p.usuario_id = u.id WHERE u.id = :id");
            $stmtCheck->execute([':id' => $userId]);
            $userInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($userInfo && $userInfo['rol_nombre'] === 'Paciente' && $userInfo['id']) {
                $fecha_nac = $_POST['fecha_nac'] ?? '';
                $telefono = trim($_POST['telefono'] ?? '');

                $stmtPac = $conn->prepare("UPDATE pacientes SET nombres = :nom, apellidos = :ape, fecha_nac = :fn WHERE id = :id");
                $stmtPac->execute([
                    ':nom' => $nombres,
                    ':ape' => $apellidos,
                    ':fn' => $fecha_nac ?: null,
                    ':id' => $userInfo['id']
                ]);

                if (!empty($telefono)) {
                    $conn->prepare("DELETE FROM paciente_telefonos WHERE paciente_id = :pid")->execute([':pid' => $userInfo['id']]);
                    $stmtTel = $conn->prepare("INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:pid, :tel)");
                    $stmtTel->execute([':pid' => $userInfo['id'], ':tel' => $telefono]);
                }
            }

            $_SESSION['email'] = $email;

            if (function_exists('log_activity')) {
                log_activity($userId, 'Actualizó su perfil de usuario', 'usuarios');
            }

            $conn->commit();
            header('Location: ' . BASE_URL . '/perfil?success=Perfil+actualizado+con+exito');
            exit();
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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

        $userId = $_SESSION['user_id'];
        $password = $_POST['password'] ?? '';

        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $hash = $stmt->fetchColumn();

        if ($hash && password_verify($password, $hash)) {
            echo json_encode(['success' => true, 'password' => $password]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
        }
        exit;
    }
}
?>
