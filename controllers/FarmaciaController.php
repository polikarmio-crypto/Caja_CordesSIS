<?php
require_once __DIR__ . '/../models/Medicamento.php';

class FarmaciaController {
    private function checkAccess() {
        if (!isset($_SESSION['rol_nombre']) || (!in_array($_SESSION['rol_nombre'], ['Administrativo', 'Directivo']) && strpos($_SESSION['rol_nombre'], 'Farmac') === false)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $q = trim($_GET['q'] ?? '');
        $medModel = new Medicamento();
        if ($q !== '') {
            $medicamentos = $medModel->search($q);
        } else {
            $medicamentos = $medModel->findAll();
        }
        require_once __DIR__ . '/../views/farmacia/index.php';
    }

    public function create() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $stock = (int)($_POST['stock'] ?? 0);
            $precio = (float)($_POST['precio_unitario'] ?? 0.0);
            $vencimiento = $_POST['vencimiento'] ?? null;
            $codigo = trim($_POST['codigo_identificacion'] ?? '');

            try {
                if (empty($nombre) || strlen($nombre) < 3) {
                    throw new Exception("El nombre del medicamento debe tener al menos 3 caracteres.");
                }
                if (empty($tipo)) {
                    throw new Exception("El tipo de medicamento es obligatorio.");
                }
                if ($stock < 0) {
                    throw new Exception("El stock no puede ser negativo.");
                }
                if ($precio <= 0) {
                    throw new Exception("El precio unitario debe ser mayor a cero.");
                }
                if (empty($codigo) || strlen($codigo) < 3) {
                    throw new Exception("El código de identificación del fármaco es obligatorio y debe tener al menos 3 caracteres.");
                }

                $medModel = new Medicamento();
                $existing = $medModel->findByCodigo($codigo);
                if ($existing) {
                    throw new Exception("Ya existe un medicamento registrado con el código de identificación $codigo.");
                }

                if ($medModel->create($nombre, $tipo, $stock, $precio, $vencimiento, $codigo)) {
                    log_activity($_SESSION['user_id'] ?? 1, "Crear Medicamento: $nombre ($codigo)", 'medicamentos');
                    header('Location: ' . BASE_URL . '/farmacia?success=Medicamento+creado+con+exito');
                } else {
                    throw new Exception("Error al insertar el medicamento en la base de datos.");
                }
            } catch (Exception $e) {
                header('Location: ' . BASE_URL . '/farmacia?error=' . urlencode($e->getMessage()));
            }
            exit();
        }
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $stock = (int)$_POST['stock'];
            $precio = (float)$_POST['precio_unitario'];

            $medModel = new Medicamento();
            if ($medModel->updateStockAndPrice($id, $stock, $precio)) {
                log_activity($_SESSION['user_id'] ?? 1, 'Actualizar Stock', 'medicamentos');
                header('Location: ' . BASE_URL . '/farmacia?success=Inventario+actualizado');
            } else {
                header('Location: ' . BASE_URL . '/farmacia?error=Error+al+actualizar');
            }
            exit();
        }
    }

    public function recetas() {
        $this->checkAccess();
        $medModel = new Medicamento();
        
        $recetasPendientes = $medModel->getRecetasPendientes();
        $recetaQueue = new CustomQueue();
        foreach($recetasPendientes as $r) {
            $r['medicamentos'] = $medModel->getDetallesReceta($r['receta_id']);
            $recetaQueue->enqueue($r);
        }

        $despachadas = $medModel->getRecetasDespachadas();
        foreach($despachadas as &$r) {
            $r['medicamentos'] = $medModel->getDetallesReceta($r['receta_id']);
        }

        require_once __DIR__ . '/../views/farmacia/recetas.php';
    }

    public function despachar() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receta_id = $_POST['receta_id'];
            
            // Reconstruir el array de cantidades {med_id => cant}
            $cantidades = [];
            if(isset($_POST['cantidades']) && is_array($_POST['cantidades'])) {
                foreach($_POST['cantidades'] as $med_id => $cant) {
                    $cantidades[$med_id] = (int)$cant;
                }
            }

            $medModel = new Medicamento();
            if ($medModel->despacharReceta($receta_id, $cantidades)) {
                log_activity($_SESSION['user_id'] ?? 1, "Despachar Receta $receta_id", 'farmacia');
                header('Location: ' . BASE_URL . '/farmacia/recetas?success=Receta+despachada+con+exito');
            } else {
                header('Location: ' . BASE_URL . '/farmacia/recetas?error=Error+al+despachar+receta');
            }
            exit();
        }
    }

    public function comprobantePdf() {
        $this->checkAccess();
        $receta_id = $_GET['id'] ?? null;
        if (!$receta_id) die('ID de receta no especificado.');

        require_once __DIR__ . '/../core/fpdf/fpdf.php';
        
        $medModel = new Medicamento();
        $receta = $medModel->getRecetaById($receta_id);
        $detalles = $medModel->getDetallesReceta($receta_id);

        if (!$receta) {
            die("Receta no encontrada.");
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Cabecera
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, 'COMPROBANTE DE DESPACHO - CAJA CORDES', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(190, 8, utf8_decode('Receta #: ' . str_pad($receta['receta_id'], 6, '0', STR_PAD_LEFT)), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Fecha Emisión Receta: ' . date('d/m/Y H:i', strtotime($receta['fecha_creacion']))), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Fecha Impresión: ' . date('d/m/Y H:i')), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Paciente: ' . $receta['nombres'] . ' ' . $receta['apellidos']), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('CI: ' . $receta['ci']), 0, 1);
        
        $pdf->Ln(10);
        
        // Tabla Detalles
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(100, 10, 'Medicamento', 1, 0, 'C');
        $pdf->Cell(45, 10, 'Dosis', 1, 0, 'C');
        $pdf->Cell(45, 10, utf8_decode('Duración'), 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 11);
        foreach ($detalles as $d) {
            $pdf->Cell(100, 10, utf8_decode($d['nombre']), 1);
            $pdf->Cell(45, 10, utf8_decode($d['dosis']), 1, 0, 'C');
            $pdf->Cell(45, 10, $d['duracion_dias'] . ' dias', 1, 1, 'C');
        }
        
        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(190, 10, utf8_decode('Este documento es una constancia de entrega de medicamentos para el seguro Caja Cordes.'), 0, 1, 'C');
        $pdf->Cell(190, 5, utf8_decode('No válido como factura comercial.'), 0, 1, 'C');

        $pdf->Output('I', 'comprobante_despacho_' . $receta['receta_id'] . '.pdf');
        exit();
    }

    public function softDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medModel = new Medicamento();
            $medModel->softDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Medicamento', 'medicamentos');
            }
            header('Location: ' . BASE_URL . '/farmacia?success=Medicamento+dado+de+baja+con+exito');
            exit();
        }
    }

    public function restore() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $medModel = new Medicamento();
            $medModel->restore($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Medicamento', 'medicamentos');
            }
            header('Location: ' . BASE_URL . '/farmacia?success=Medicamento+restaurado+con+exito');
            exit();
        }
    }

    public function hardDelete() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $medModel = new Medicamento();
            $medModel->hardDelete($_POST['id']);
            if (function_exists('log_activity')) {
                log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Medicamento', 'medicamentos');
            }
            header('Location: ' . BASE_URL . '/farmacia/bajas?success=Medicamento+eliminado+permanentemente');
            exit();
        }
    }

    public function bajas() {
        $this->checkAccess();
        $medModel = new Medicamento();
        $medicamentos = $medModel->findAllInactive();
        require_once __DIR__ . '/../views/farmacia/bajas.php';
    }
}
?>
