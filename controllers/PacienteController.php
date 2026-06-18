<?php
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repositories/PacienteRepository.php';

/**
 * PacienteController — Gestión de pacientes.
 * Las consultas SQL están delegadas a PacienteRepository.
 */
class PacienteController {

    public function index() {
        $pacienteModel = new Paciente();
        $perPage       = 30;
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $totalPacientes = $pacienteModel->countAll();
        $totalPages    = (int) ceil($totalPacientes / $perPage);
        $pacientes     = $pacienteModel->findAll($page, $perPage);
        require_once __DIR__ . '/../views/pacientes/index.php';
    }

    public function softDelete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $pacienteModel = new Paciente();
            $pacienteModel->softDelete($_POST['id']);
            AppLogger::info("Baja lógica de paciente", ['paciente_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Paciente', 'pacientes');
            header('Location: ' . BASE_URL . '/pacientes?success=baja');
            exit();
        }
    }

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $pacienteModel = new Paciente();
            $pacienteModel->restore($_POST['id']);
            AppLogger::info("Restaurar paciente", ['paciente_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Paciente', 'pacientes');
            header('Location: ' . BASE_URL . '/pacientes?success=restaurado');
            exit();
        }
    }

    public function hardDelete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $pacienteModel = new Paciente();
            $pacienteModel->hardDelete($_POST['id']);
            AppLogger::warning("Eliminación permanente de paciente", ['paciente_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Paciente', 'pacientes');
            header('Location: ' . BASE_URL . '/pacientes?success=eliminado');
            exit();
        }
    }

    public function bajas() {
        $pacienteModel = new Paciente();
        $pacientes = $pacienteModel->findAllInactive();
        require_once __DIR__ . '/../views/pacientes/bajas.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombres   = $_POST['nombres']   ?? '';
            $apellidos = $_POST['apellidos'] ?? '';
            $ci        = $_POST['ci']        ?? '';
            $email     = $_POST['email']     ?? '';
            $password  = $_POST['password']  ?? '';
            $fecha_nac = $_POST['fecha_nac'] ?? '';
            $telefono  = $_POST['telefono']  ?? '';

            $conn = Database::getInstance();
            $repo = new PacienteRepository();

            try {
                $nombres   = trim($nombres);
                $apellidos = trim($apellidos);
                $ci        = trim($ci);
                $email     = trim($email);

                if (empty($nombres) || strlen($nombres) < 2 || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-'\.]+$/u", $nombres)) {
                    throw new Exception("El nombre debe tener al menos 2 caracteres y contener solo letras.");
                }
                if (empty($apellidos) || strlen($apellidos) < 2 || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-'\.]+$/u", $apellidos)) {
                    throw new Exception("El apellido debe tener al menos 2 caracteres y contener solo letras.");
                }
                if (empty($ci) || strlen($ci) < 5 || !preg_match("/^[a-zA-Z0-9\-]+$/", $ci)) {
                    throw new Exception("El documento de identidad (CI) debe tener al menos 5 caracteres alfanuméricos.");
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("El correo electrónico no es válido.");
                }
                if (empty($password) || strlen($password) < 4) {
                    throw new Exception("La contraseña debe tener al menos 4 caracteres.");
                }
                if (!empty($telefono) && (strlen($telefono) < 7 || !preg_match("/^\+?[0-9\s\-]{7,15}$/", $telefono))) {
                    throw new Exception("El número de teléfono debe tener entre 7 y 15 dígitos.");
                }
                if (empty($fecha_nac)) {
                    throw new Exception("La fecha de nacimiento es obligatoria.");
                }
                $birthDate = strtotime($fecha_nac);
                if ($birthDate === false || $birthDate > time() || $birthDate < strtotime('1900-01-01')) {
                    throw new Exception("La fecha de nacimiento no es coherente.");
                }

                $conn->beginTransaction();

                $rolIdPaciente = $repo->findRolIdPaciente();
                $hash          = password_hash($password, PASSWORD_DEFAULT);
                $usuario_id    = $repo->insertUsuario($rolIdPaciente, $email, $hash);

                $pacienteModel   = new Paciente();
                $telefonos_array = !empty($telefono) ? [$telefono] : [];
                $pacienteModel->create($usuario_id, $ci, $nombres, $apellidos, $fecha_nac, $telefonos_array);

                AppLogger::info("Nuevo paciente registrado", ['email' => $email, 'usuario_id' => $usuario_id]);
                log_activity($usuario_id, 'Crear Paciente', 'pacientes');

                $conn->commit();
                header('Location: ' . BASE_URL . '/pacientes?success=1');
                exit();
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                AppLogger::error("Error al crear paciente: " . $e->getMessage(), ['email' => $email]);
                $error = "Error al crear paciente: " . $e->getMessage();
                require_once __DIR__ . '/../views/pacientes/create.php';
            }
        } else {
            require_once __DIR__ . '/../views/pacientes/create.php';
        }
    }

    public function search() {
        header('Content-Type: application/json');
        $query = $_GET['q'] ?? '';
        $repo  = new PacienteRepository();
        echo json_encode($repo->search($query));
        exit();
    }

    // RF-011 / RF-106: Editar perfil del paciente
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $rol     = $_SESSION['rol_nombre'] ?? '';
        $repo    = new PacienteRepository();

        if ($rol === 'Paciente') {
            $paciente = $repo->findPacienteByUserId($user_id);
        } elseif (in_array($rol, ['Administrativo', 'Directivo'])) {
            $target_id = intval($_GET['id'] ?? $_POST['paciente_id'] ?? 0);
            $paciente  = $repo->findPacienteById($target_id);
        } else {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if (!$paciente) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombres   = trim($_POST['nombres']   ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $ci        = trim($_POST['ci']        ?? '');
            $fecha_nac = $_POST['fecha_nac']      ?? '';
            $telefono  = trim($_POST['telefono']  ?? '');

            $conn = Database::getInstance();

            try {
                $nombres   = trim($nombres);
                $apellidos = trim($apellidos);
                $ci        = trim($ci);

                if (empty($nombres) || strlen($nombres) < 2 || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-'\.]+$/u", $nombres)) {
                    throw new Exception("El nombre debe tener al menos 2 caracteres y contener solo letras.");
                }
                if (empty($apellidos) || strlen($apellidos) < 2 || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-'\.]+$/u", $apellidos)) {
                    throw new Exception("El apellido debe tener al menos 2 caracteres y contener solo letras.");
                }
                if (empty($ci) || strlen($ci) < 5 || !preg_match("/^[a-zA-Z0-9\-]+$/", $ci)) {
                    throw new Exception("El documento de identidad (CI) debe tener al menos 5 caracteres alfanuméricos.");
                }
                if (!empty($telefono) && (strlen($telefono) < 7 || !preg_match("/^\+?[0-9\s\-]{7,15}$/", $telefono))) {
                    throw new Exception("El número de teléfono debe tener entre 7 y 15 dígitos.");
                }
                if (empty($fecha_nac)) {
                    throw new Exception("La fecha de nacimiento es obligatoria.");
                }
                $birthDate = strtotime($fecha_nac);
                if ($birthDate === false || $birthDate > time() || $birthDate < strtotime('1900-01-01')) {
                    throw new Exception("La fecha de nacimiento no es coherente.");
                }

                $conn->beginTransaction();
                $repo->updatePaciente((int)$paciente['id'], $nombres, $apellidos, $ci, $fecha_nac ?: null);
                if (!empty($telefono)) {
                    $repo->replaceTelefono((int)$paciente['id'], $telefono);
                }

                AppLogger::info("Perfil de paciente actualizado", ['paciente_id' => $paciente['id']]);
                log_activity($user_id, 'Actualizar Perfil Paciente', 'pacientes');
                $conn->commit();

                $success  = 'Perfil actualizado correctamente.';
                $paciente = $repo->findPacienteById((int)$paciente['id']);
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                AppLogger::error("Error al actualizar paciente: " . $e->getMessage(), ['paciente_id' => $paciente['id']]);
                $error = 'Error al actualizar: ' . $e->getMessage();
            }
        }

        $telefono_actual = $repo->findTelefonoPrincipal((int)$paciente['id']);
        require_once __DIR__ . '/../views/pacientes/edit.php';
    }
}
?>
