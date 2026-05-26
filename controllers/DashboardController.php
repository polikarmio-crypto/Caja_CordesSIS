<?php
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
        $conn = Database::getInstance();
        
        // Citas de los ultimos 6 meses
        $citas = $conn->query("
            SELECT TO_CHAR(fecha_hora, 'YYYY-MM') as mes, estado, COUNT(*) as total
            FROM citas 
            WHERE fecha_hora >= NOW() - INTERVAL '6 MONTH'
            GROUP BY TO_CHAR(fecha_hora, 'YYYY-MM'), estado
            ORDER BY mes ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Ocupacion de Camas
        $camas = $conn->query("
            SELECT estado, COUNT(*) as total FROM camas GROUP BY estado
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Pacientes Registrados
        $pacientes_total = $conn->query("SELECT COUNT(*) as total FROM pacientes")->fetchColumn();

        // Pacientes Registrados (Crecimiento ultimos 6 meses)
        $crecimiento_pacientes = $conn->query("
            SELECT TO_CHAR(u.creado_en, 'YYYY-MM') as mes, COUNT(p.id) as total 
            FROM pacientes p
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE u.creado_en >= NOW() - INTERVAL '6 MONTH'
            GROUP BY TO_CHAR(u.creado_en, 'YYYY-MM')
            ORDER BY mes ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Crecimiento Interanual Citas
        $interanual = $conn->query("
            SELECT EXTRACT(YEAR FROM fecha_hora) as anio, COUNT(*) as total
            FROM citas
            WHERE EXTRACT(YEAR FROM fecha_hora) >= EXTRACT(YEAR FROM CURRENT_DATE) - 1
            GROUP BY EXTRACT(YEAR FROM fecha_hora)
            ORDER BY anio ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'citas' => $citas,
            'camas' => $camas,
            'pacientes_total' => $pacientes_total,
            'crecimiento_pacientes' => $crecimiento_pacientes,
            'interanual' => $interanual
        ]);
        exit();
    }

    public function exportPdf() {
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['Administrativo', 'Directivo'])) {
            die("Acceso denegado");
        }
        require_once __DIR__ . '/../core/fpdf/fpdf.php';
        $conn = Database::getInstance();
        
        $pacientes_total = $conn->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
        $citas_total = $conn->query("SELECT COUNT(*) FROM citas")->fetchColumn();
        $citas_completadas = $conn->query("SELECT COUNT(*) FROM citas WHERE estado='completada'")->fetchColumn();
        $citas_canceladas = $conn->query("SELECT COUNT(*) FROM citas WHERE estado='cancelada'")->fetchColumn();
        
        $efectividad = $citas_total > 0 ? round(($citas_completadas / $citas_total) * 100, 2) : 0;
        $tasa_cancelacion = $citas_total > 0 ? round(($citas_canceladas / $citas_total) * 100, 2) : 0;

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, 'REPORTE GERENCIAL - CAJA CORDES', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(100, 10, utf8_decode('Total de Pacientes Registrados: ' . $pacientes_total), 0, 1);
        $pdf->Cell(100, 10, utf8_decode('Total Histórico de Citas: ' . $citas_total), 0, 1);
        $pdf->Cell(100, 10, utf8_decode('Tasa de Efectividad (Completadas): ' . $efectividad . '%'), 0, 1);
        $pdf->Cell(100, 10, utf8_decode('Tasa de Cancelación: ' . $tasa_cancelacion . '%'), 0, 1);
        
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
        $conn = Database::getInstance();
        $datos = $conn->query("
            SELECT TO_CHAR(fecha_hora, 'YYYY-MM') as mes, estado, COUNT(*) as total
            FROM citas GROUP BY TO_CHAR(fecha_hora, 'YYYY-MM'), estado ORDER BY mes DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tendencias_citas.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Mes', 'Estado', 'Total']);
        foreach($datos as $d) {
            fputcsv($output, [$d['mes'], $d['estado'], $d['total']]);
        }
        fclose($output);
        exit();
    }
}
?>
