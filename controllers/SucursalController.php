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
        $sucursalModel = new Sucursal();
        $sucursales = $sucursalModel->findAll();
        require_once __DIR__ . '/../views/sucursal/index.php';
    }

    public function create() {
        $this->checkAccess();
        $sucursalModel = new Sucursal();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $ubicacion = $_POST['ubicacion'] ?? '';
            $horarios = $_POST['horarios'] ?? '';
            $limites = $_POST['limitesgeocerca'] ?? '';
            $admin_id = $_SESSION['user_id'] ?? null;

            if ($sucursalModel->create($nombre, $ubicacion, $horarios, $limites, $admin_id)) {
                if (function_exists('log_activity')) {
                    log_activity($_SESSION['user_id'] ?? 1, 'Crear Sucursal', 'sucursales');
                }
                header('Location: ' . BASE_URL . '/sucursal?success=Sucursal+creada');
                exit();
            }
        }
        
        require_once __DIR__ . '/../views/sucursal/create.php';
    }
}
?>
