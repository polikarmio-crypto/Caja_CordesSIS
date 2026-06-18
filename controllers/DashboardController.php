<?php
require_once __DIR__ . '/../repositories/DashboardRepository.php';

/**
 * DashboardController — Estadísticas y reportes del dashboard.
 * Las consultas SQL están delegadas a DashboardRepository.
 */
class DashboardController {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        $rol = $_SESSION['rol_nombre'];
        require_once '../views/dashboard/index.php';
    }

    public function stats() {
        header('Content-Type: application/json');
        $repo = new DashboardRepository();

        echo json_encode([
            'citas'                 => $repo->getCitasPorMes(),
            'camas'                 => $repo->getOcupacionCamas(),
            'pacientes_total'       => $repo->getTotalPacientes(),
            'crecimiento_pacientes' => $repo->getCrecimientoPacientes(),
            'interanual'            => $repo->getCrecimientoInteranualCitas(),
        ]);
        exit();
    }

    public function exportPdf() {
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['Administrativo', 'Directivo'])) {
            die("Acceso denegado");
        }

        require_once __DIR__ . '/../core/fpdf/fpdf.php';
        $repo  = new DashboardRepository();
        $stats = $repo->getEstadisticasPdf();

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, 'REPORTE GERENCIAL - CAJA CORDES', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(100, 10, utf8_decode('Total de Pacientes Registrados: ' . $stats['total_pacientes']),    0, 1);
        $pdf->Cell(100, 10, utf8_decode('Total Histórico de Citas: '      . $stats['citas_total']),         0, 1);
        $pdf->Cell(100, 10, utf8_decode('Tasa de Efectividad (Completadas): ' . $stats['efectividad'] . '%'), 0, 1);
        $pdf->Cell(100, 10, utf8_decode('Tasa de Cancelación: '           . $stats['tasa_cancelacion'] . '%'), 0, 1);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(190, 10, utf8_decode('Reporte generado el: ' . date('Y-m-d H:i')), 0, 1, 'C');
        $pdf->Output('D', 'reporte_gerencial_cordes.pdf');
        exit();
    }

    public function exportCsv() {
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['Administrativo', 'Directivo'])) {
            die("Acceso denegado");
        }

        $repo  = new DashboardRepository();
        $datos = $repo->getDatosCsvTendencias();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tendencias_citas.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Mes', 'Estado', 'Total']);
        foreach ($datos as $d) {
            fputcsv($output, [$d['mes'], $d['estado'], $d['total']]);
        }
        fclose($output);
        exit();
    }
}
?>
