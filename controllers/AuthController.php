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
}
?>
