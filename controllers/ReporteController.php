<?php
require_once __DIR__ . '/../models/Cita.php';

class ReporteController {
    public function export_citas() {
        // Solo administracion/directivos pueden exportar
        if (!isset($_SESSION['rol_nombre']) || ($_SESSION['rol_nombre'] !== 'Administrativo' && $_SESSION['rol_nombre'] !== 'Directivo')) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }

        $citaModel = new Cita();
        $citas = $citaModel->findAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_citas_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');

        // UTF-8 BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['ID Cita', 'Fecha', 'Hora', 'Paciente CI', 'Paciente Nombres', 'Médico Email', 'Estado', 'Motivo']);

        foreach ($citas as $c) {
            fputcsv($output, [
                $c['id'],
                date('Y-m-d', strtotime($c['fecha_hora'])),
                date('H:i:s', strtotime($c['fecha_hora'])),
                $c['paciente_ci'] ?? 'N/A', // Ojo: La query actual no trae el CI, pero podemos dejar N/A o cruzar
                $c['paciente_nombres'] . ' ' . $c['paciente_apellidos'],
                $c['medico_email'],
                $c['estado'],
                $c['motivo']
            ]);
        }

        fclose($output);
        
        log_activity($_SESSION['user_id'] ?? 1, 'Exportar CSV Citas', 'citas');
        exit();
    }
}
?>
