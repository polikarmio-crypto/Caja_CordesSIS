<?php
require_once __DIR__ . '/../models/Insumo.php';

class InsumoController {
    private function checkAccess() {
        $allowed = ['Administrativo', 'Directivo', 'Farmacéutico'];
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $allowed)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $insumoModel = new Insumo();
        $totalInsumos = $insumoModel->countAll();
        $totalPages = ceil($totalInsumos / $perPage);

        $insumos = $insumoModel->findAll($page, $perPage);
        require_once __DIR__ . '/../views/insumo/index.php';
    }

    public function softDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $insumoModel = new Insumo();
            $insumoModel->softDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Insumo', 'insumos');
            }
            header('Location: ' . BASE_URL . '/insumo?success=baja');
            exit();
        }
    }

    public function restore() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $insumoModel = new Insumo();
            $insumoModel->restore($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Insumo', 'insumos');
            }
            header('Location: ' . BASE_URL . '/insumo?success=restaurado');
            exit();
        }
    }

    public function hardDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $insumoModel = new Insumo();
            $insumoModel->hardDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Insumo', 'insumos');
            }
            header('Location: ' . BASE_URL . '/insumo/bajas?success=eliminado');
            exit();
        }
    }

    public function bajas() {
        $this->checkAccess();
        $insumoModel = new Insumo();
        $insumos = $insumoModel->findAllInactive();
        require_once __DIR__ . '/../views/insumo/bajas.php';
    }

    public function create() {
        $this->checkAccess();
        $insumoModel = new Insumo();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $desc = trim($_POST['descripcion'] ?? '');
            $precio = $_POST['precio_unitario'] ?? 0;
            $cant = $_POST['cantidad'] ?? 0;
            $cat = $_POST['id_categoria'] ?? null;

            try {
                if (empty($nombre) || strlen($nombre) < 3) {
                    throw new Exception("El nombre del insumo debe tener al menos 3 caracteres.");
                }
                if (floatval($precio) <= 0) {
                    throw new Exception("El precio unitario debe ser mayor a cero.");
                }
                if (intval($cant) < 0) {
                    throw new Exception("La cantidad no puede ser negativa.");
                }

                if ($insumoModel->create($nombre, $desc, $precio, $cant, $cat)) {
                    if (function_exists('log_activity')) {
                        log_activity($_SESSION['user_id'] ?? 1, 'Crear Insumo', 'insumos');
                    }
                    header('Location: ' . BASE_URL . '/insumo?success=Insumo+creado');
                    exit();
                } else {
                    throw new Exception("Error al guardar el insumo en la base de datos.");
                }
            } catch (Exception $e) {
                $error = "Error al crear insumo: " . $e->getMessage();
            }
        }
        
        require_once __DIR__ . '/../views/insumo/create.php';
    }
}
?>
