<?php
require_once __DIR__ . '/../models/Calificacion.php';

class CalificacionController {
    public function create() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol_nombre'] !== 'Paciente') {
            header('Location: ' . BASE_URL . '/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cita_id = $_POST['cita_id'] ?? null;
            $medico_id = $_POST['medico_id'] ?? null;
            $puntuacion = (int)($_POST['puntuacion'] ?? 5);
            $comentarios = $_POST['comentarios'] ?? '';
            $paciente_id = $_SESSION['paciente_id'] ?? null;

            if ($cita_id && $medico_id && $paciente_id) {
                $califModel = new Calificacion();
                $califModel->create($cita_id, $paciente_id, $medico_id, $puntuacion, $comentarios);
                if (function_exists('log_activity')) {
                    log_activity($_SESSION['user_id'], 'Calificar Cita', 'calificaciones');
                }
                header('Location: ' . BASE_URL . '/citas?success=Calificación+guardada');
                exit();
            }
        }
    }
}
?>
