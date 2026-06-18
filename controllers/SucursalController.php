<?php
require_once __DIR__ . '/../models/Sucursal.php';

class SucursalController {
    private function checkAccess() {
        $allowed = ['Administrativo', 'Directivo'];
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $allowed)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $sucursalModel = new Sucursal();
        $totalSucursales = $sucursalModel->countAll();
        $totalPages = ceil($totalSucursales / $perPage);

        $sucursales = $sucursalModel->findAll($page, $perPage);
        require_once __DIR__ . '/../views/sucursal/index.php';
    }

    public function softDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $sucursalModel = new Sucursal();
            $sucursalModel->softDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Sucursal', 'sucursales');
            }
            header('Location: ' . BASE_URL . '/sucursal?success=baja');
            exit();
        }
    }

    public function restore() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $sucursalModel = new Sucursal();
            $sucursalModel->restore($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Sucursal', 'sucursales');
            }
            header('Location: ' . BASE_URL . '/sucursal?success=restaurada');
            exit();
        }
    }

    public function hardDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $sucursalModel = new Sucursal();
            $sucursalModel->hardDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Sucursal', 'sucursales');
            }
            header('Location: ' . BASE_URL . '/sucursal/bajas?success=eliminado');
            exit();
        }
    }

    public function bajas() {
        $this->checkAccess();
        $sucursalModel = new Sucursal();
        $sucursales = $sucursalModel->findAllInactive();
        require_once __DIR__ . '/../views/sucursal/bajas.php';
    }

    public function create() {
        $this->checkAccess();
        $sucursalModel = new Sucursal();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            $horarios = trim($_POST['horarios'] ?? '');
            $admin_id = $_SESSION['user_id'] ?? null;

            try {
                if (empty($nombre) || strlen($nombre) < 3) {
                    throw new Exception("El nombre de la sucursal debe tener al menos 3 caracteres.");
                }
                if (empty($ubicacion) || strlen($ubicacion) < 3) {
                    throw new Exception("La ubicación de la sucursal debe tener al menos 3 caracteres.");
                }

                if ($sucursalModel->create($nombre, $ubicacion, $horarios, $admin_id)) {
                    if (function_exists('log_activity')) {
                        log_activity($_SESSION['user_id'] ?? 1, 'Crear Sucursal', 'sucursales');
                    }
                    header('Location: ' . BASE_URL . '/sucursal?success=Sucursal+creada');
                    exit();
                } else {
                    throw new Exception("Error al guardar la sucursal en la base de datos.");
                }
            } catch (Exception $e) {
                $error = "Error al crear sucursal: " . $e->getMessage();
            }
        }
        
        require_once __DIR__ . '/../views/sucursal/create.php';
    }
}
?>
