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
        $insumoModel = new Insumo();
        $insumos = $insumoModel->findAll();
        require_once __DIR__ . '/../views/insumo/index.php';
    }

    public function create() {
        $this->checkAccess();
        $insumoModel = new Insumo();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $desc = $_POST['descripcion'] ?? '';
            $precio = $_POST['precio_unitario'] ?? 0;
            $cant = $_POST['cantidad'] ?? 0;
            $cat = $_POST['id_categoria'] ?? null;

            if ($insumoModel->create($nombre, $desc, $precio, $cant, $cat)) {
                if (function_exists('log_activity')) {
                    log_activity($_SESSION['user_id'] ?? 1, 'Crear Insumo', 'insumos');
                }
                header('Location: ' . BASE_URL . '/insumo?success=Insumo+creado');
                exit();
            }
        }
        
        require_once __DIR__ . '/../views/insumo/create.php';
    }
}
?>
