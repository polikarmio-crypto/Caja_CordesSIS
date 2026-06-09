<?php
require_once __DIR__ . '/../models/Medico.php';
require_once __DIR__ . '/../models/User.php';

class MedicoController {
    public function index() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $medicoModel = new Medico();
        $totalMedicos = $medicoModel->countAll();
        $totalPages = ceil($totalMedicos / $perPage);

        $medicos = $medicoModel->findAll($page, $perPage);
        require_once __DIR__ . '/../views/medicos/index.php';
    }

    public function softDelete() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medicoModel = new Medico();
            $medicoModel->softDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Médico', 'medicos');
            }
            header('Location: ' . BASE_URL . '/medicos?success=baja');
            exit();
        }
    }

    public function restore() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medicoModel = new Medico();
            $medicoModel->restore($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Médico', 'medicos');
            }
            header('Location: ' . BASE_URL . '/medicos?success=restaurado');
            exit();
        }
    }

    public function hardDelete() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $medicoModel = new Medico();
            $medicoModel->hardDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Médico', 'medicos');
            }
            header('Location: ' . BASE_URL . '/medicos/bajas?success=eliminado');
            exit();
        }
    }

    public function bajas() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $medicoModel = new Medico();
        $medicos = $medicoModel->findAllInactive();
        require_once __DIR__ . '/../views/medicos/bajas.php';
    }

    public function create() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $conn = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $licencia_medica = $_POST['licencia_medica'] ?? '';
            $especialidades = $_POST['especialidades'] ?? [];

            $conn->beginTransaction();

            try {
                // Obtener ID del rol 'Médico' dinámicamente
                $stmtRol = $conn->prepare("SELECT id FROM roles WHERE nombre = 'Médico' LIMIT 1");
                $stmtRol->execute();
                $rolData = $stmtRol->fetch();
                $rolIdMedico = $rolData ? $rolData['id'] : 3;

                // Crear usuario
                $stmtUser = $conn->prepare("INSERT INTO usuarios (rol_id, email, password_hash) VALUES (:rol_id, :email, :password_hash)");
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser->bindParam(':rol_id', $rolIdMedico, PDO::PARAM_INT);
                $stmtUser->bindParam(':email', $email);
                $stmtUser->bindParam(':password_hash', $hash);
                $stmtUser->execute();
                
                $usuario_id = $conn->lastInsertId();

                // Crear Médico
                $medicoModel = new Medico();
                if ($medicoModel->create($usuario_id, $licencia_medica, $especialidades)) {
                    log_activity($_SESSION['user_id'] ?? 1, 'Registrar Médico', 'medicos');
                    $conn->commit();
                    header('Location: ' . BASE_URL . '/medicos?success=1');
                    exit();
                } else {
                    throw new Exception("Error al insertar el registro médico.");
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Error al crear médico: " . $e->getMessage();
                
                // Cargar especialidades nuevamente para la vista
                $stmtEsp = $conn->query("SELECT * FROM especialidades ORDER BY nombre ASC");
                $especialidadesList = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
                require_once __DIR__ . '/../views/medicos/create.php';
            }
        } else {
            $stmtEsp = $conn->query("SELECT * FROM especialidades ORDER BY nombre ASC");
            $especialidadesList = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../views/medicos/create.php';
        }
    }
}
?>
