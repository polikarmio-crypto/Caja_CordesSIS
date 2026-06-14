<?php
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../core/fpdf/fpdf.php';

class FacturacionController {
    private function checkAccess() {
        if (!isset($_SESSION['rol_nombre']) || (!in_array($_SESSION['rol_nombre'], ['Administrativo', 'Directivo']) && strpos($_SESSION['rol_nombre'], 'Farmac') === false)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();

        $facturaModel = new Factura();
        $facturas = $facturaModel->findAll();
        require_once __DIR__ . '/../views/facturacion/index.php';
    }

    public function downloadPdf() {
        if (!isset($_SESSION['rol_nombre'])) {
            header('Location: ' . BASE_URL . '/');
            exit();
        }
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID de factura no especificado.');

        $facturaModel = new Factura();
        $factura = $facturaModel->findById($id);

        if (!$factura) {
            die("Factura no encontrada.");
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Configurar fuente
        $pdf->SetFont('Arial', 'B', 16);
        
        // Cabecera
        $pdf->Cell(190, 10, 'COMPROBANTE DE FACTURACION - CAJA CORDES', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(190, 8, utf8_decode('Factura #: ' . str_pad($factura['id'], 6, '0', STR_PAD_LEFT)), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Fecha Emision: ' . date('d/m/Y H:i', strtotime($factura['fecha_emision']))), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Paciente: ' . $factura['nombres'] . ' ' . $factura['apellidos']), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('CI: ' . $factura['ci']), 0, 1);
        $pdf->Cell(190, 8, utf8_decode('Estado: ' . strtoupper($factura['estado'])), 0, 1);
        $motivo = isset($factura['motivo']) && !empty($factura['motivo']) ? $factura['motivo'] : 'No especificado';
        $pdf->Cell(190, 8, utf8_decode('Motivo: ' . $motivo), 0, 1);
        
        $pdf->Ln(10);
        
        // Tabla Detalles - Cabecera
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(90, 10, 'Concepto', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(35, 10, 'P. Unitario', 1, 0, 'C');
        $pdf->Cell(35, 10, 'Subtotal', 1, 1, 'C');
        
        // Tabla Detalles - Filas
        $pdf->SetFont('Arial', '', 11);
        foreach ($factura['detalles'] as $d) {
            $pdf->Cell(90, 10, utf8_decode($d['concepto']), 1);
            $pdf->Cell(30, 10, $d['cantidad'], 1, 0, 'C');
            $pdf->Cell(35, 10, 'Bs. ' . number_format($d['precio_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(35, 10, 'Bs. ' . number_format($d['subtotal'], 2), 1, 1, 'R');
        }
        
        // Total
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(155, 10, 'TOTAL A PAGAR', 1, 0, 'R');
        $pdf->Cell(35, 10, 'Bs. ' . number_format($factura['total'], 2), 1, 1, 'R');
        
        $pdf->Output('I', 'factura_' . $factura['id'] . '.pdf');
        exit();
    }

    public function create() {
        $this->checkAccess();
        require_once __DIR__ . '/../models/Paciente.php';
        $pacienteModel = new Paciente();
        $pacientes = $pacienteModel->findAll();
        require_once __DIR__ . '/../views/facturacion/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'];
            $motivo = $_POST['motivo'] ?? 'Paciente Externo';
            $conceptos = $_POST['concepto'];
            $cantidades = $_POST['cantidad'];
            $precios = $_POST['precio'];
            
            $detalles = [];
            for($i = 0; $i < count($conceptos); $i++) {
                if (!empty($conceptos[$i]) && $cantidades[$i] > 0) {
                    $detalles[] = [
                        'concepto' => $conceptos[$i],
                        'cantidad' => $cantidades[$i],
                        'precio_unitario' => $precios[$i],
                        'subtotal' => $cantidades[$i] * $precios[$i]
                    ];
                }
            }

            if (count($detalles) > 0) {
                $facturaModel = new Factura();
                $factura_id = $facturaModel->createManual($paciente_id, $motivo, $detalles);
                if ($factura_id) {
                    log_activity($_SESSION['user_id'] ?? 1, "Crear Factura Manual $factura_id", 'facturas');
                    header('Location: ' . BASE_URL . '/facturacion?success=Factura+creada+con+exito');
                    exit();
                }
            }
            
            header('Location: ' . BASE_URL . '/facturacion/create?error=Error+al+crear+la+factura');
            exit();
        }
    }

    public function updateStatus() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['factura_id'];
            $estado = $_POST['estado'];

            $facturaModel = new Factura();
            if ($facturaModel->updateStatus($id, $estado)) {
                log_activity($_SESSION['user_id'] ?? 1, "Actualizar Estado Factura $id", 'facturas');
                header('Location: ' . BASE_URL . '/facturacion?success=Estado+actualizado');
            } else {
                header('Location: ' . BASE_URL . '/facturacion?error=Error+al+actualizar');
            }
            exit();
        }
    }
}
?>
