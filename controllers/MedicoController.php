<?php
require_once __DIR__ . '/../models/Medico.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../repositories/MedicoRepository.php';

/**
 * MedicoController — Gestión de médicos.
 * Las consultas SQL están delegadas a MedicoRepository.
 */
class MedicoController {

    private function checkAdmin(): void {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index() {
        $this->checkAdmin();
        $page    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $medicoModel  = new Medico();
        $totalMedicos = $medicoModel->countAll();
        $totalPages   = ceil($totalMedicos / $perPage);
        $medicos      = $medicoModel->findAll($page, $perPage);
        require_once __DIR__ . '/../views/medicos/index.php';
    }

    public function softDelete() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medicoModel = new Medico();
            $medicoModel->softDelete($_POST['id']);
            AppLogger::info("Baja lógica de médico", ['medico_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Médico', 'medicos');
            header('Location: ' . BASE_URL . '/medicos?success=baja');
            exit();
        }
    }

    public function restore() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medicoModel = new Medico();
            $medicoModel->restore($_POST['id']);
            AppLogger::info("Restaurar médico", ['medico_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Médico', 'medicos');
            header('Location: ' . BASE_URL . '/medicos?success=restaurado');
            exit();
        }
    }

    public function hardDelete() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $medicoModel = new Medico();
            $medicoModel->hardDelete($_POST['id']);
            AppLogger::warning("Eliminación permanente de médico", ['medico_id' => $_POST['id']]);
            log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Médico', 'medicos');
            header('Location: ' . BASE_URL . '/medicos/bajas?success=eliminado');
            exit();
        }
    }

    public function bajas() {
        $this->checkAdmin();
        $medicoModel = new Medico();
        $medicos = $medicoModel->findAllInactive();
        require_once __DIR__ . '/../views/medicos/bajas.php';
    }

    public function create() {
        $this->checkAdmin();
        $repo = new MedicoRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email          = $_POST['email']          ?? '';
            $password       = $_POST['password']       ?? '';
            $licencia_medica = $_POST['licencia_medica'] ?? '';
            $especialidades = $_POST['especialidades'] ?? [];

            $conn = Database::getInstance();

            try {
                $email           = trim($email);
                $licencia_medica = trim($licencia_medica);

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("El correo electrónico no es válido.");
                }
                if (empty($password) || strlen($password) < 4) {
                    throw new Exception("La contraseña debe tener al menos 4 caracteres.");
                }
                if (empty($licencia_medica) || strlen($licencia_medica) < 3) {
                    throw new Exception("La licencia médica debe tener al menos 3 caracteres.");
                }

                $conn->beginTransaction();

                $rolIdMedico = $repo->findRolIdMedico();
                $hash        = password_hash($password, PASSWORD_DEFAULT);
                $usuario_id  = $repo->insertUsuario($rolIdMedico, $email, $hash);

                $medicoModel = new Medico();
                if ($medicoModel->create($usuario_id, $licencia_medica, $especialidades)) {
                    AppLogger::info("Nuevo médico registrado", ['email' => $email, 'usuario_id' => $usuario_id]);
                    log_activity($_SESSION['user_id'] ?? 1, 'Registrar Médico', 'medicos');
                    $conn->commit();
                    header('Location: ' . BASE_URL . '/medicos?success=1');
                    exit();
                } else {
                    throw new Exception("Error al insertar el registro médico.");
                }
            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                AppLogger::error("Error al crear médico: " . $e->getMessage(), ['email' => $email]);
                $error = "Error al crear médico: " . $e->getMessage();
                $especialidadesList = $repo->findAllEspecialidades();
                require_once __DIR__ . '/../views/medicos/create.php';
            }
        } else {
            $especialidadesList = $repo->findAllEspecialidades();
            require_once __DIR__ . '/../views/medicos/create.php';
        }
    }
}
?>
