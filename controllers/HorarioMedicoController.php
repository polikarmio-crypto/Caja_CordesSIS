<?php
require_once __DIR__ . '/../models/HorarioMedico.php';

class HorarioMedicoController {
    public function index() {
        $hmModel = new HorarioMedico();
        $horarios = $hmModel->findAll();
        require_once __DIR__ . '/../views/horarios/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $medico_id = $_POST['medico_id'] ?? '';
            $dia_semana = $_POST['dia_semana'] ?? '';
            $hora_inicio = $_POST['hora_inicio'] ?? '';
            $hora_fin = $_POST['hora_fin'] ?? '';

            $hmModel = new HorarioMedico();
            try {
                if (empty($medico_id)) {
                    throw new Exception("El médico es obligatorio.");
                }
                if (empty($dia_semana)) {
                    throw new Exception("El día de la semana es obligatorio.");
                }
                if (empty($hora_inicio) || empty($hora_fin)) {
                    throw new Exception("Las horas de inicio y fin son obligatorias.");
                }
                if ($hora_inicio === $hora_fin) {
                    throw new Exception("La hora de inicio y fin no pueden ser iguales.");
                }

                if ($hmModel->create($medico_id, $dia_semana, $hora_inicio, $hora_fin)) {
                    log_activity($_SESSION['user_id'] ?? 1, 'Crear Horario', 'horarios_medicos');
                    header('Location: ' . BASE_URL . '/horarios?success=1');
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al guardar horario: " . $e->getMessage();
                require_once __DIR__ . '/../views/horarios/create.php';
            }
        } else {
            require_once __DIR__ . '/../views/horarios/create.php';
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $hmModel = new HorarioMedico();
            $hmModel->softDelete($_POST['id']);
            log_activity($_SESSION['user_id'] ?? 1, 'Baja Lógica Horario', 'horarios_medicos');
            header('Location: ' . BASE_URL . '/horarios?success=baja');
            exit();
        }
    }

    public function hardDelete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['confirm_delete'])) {
            $hmModel = new HorarioMedico();
            $hmModel->hardDelete($_POST['id']);
            log_activity($_SESSION['user_id'] ?? 1, 'Eliminación Permanente Horario', 'horarios_medicos');
            header('Location: ' . BASE_URL . '/horarios/bajas?success=eliminado');
            exit();
        }
    }

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $hmModel = new HorarioMedico();
            $hmModel->restore($_POST['id']);
            log_activity($_SESSION['user_id'] ?? 1, 'Restaurar Horario', 'horarios_medicos');
            header('Location: ' . BASE_URL . '/horarios?success=restaurado');
            exit();
        }
    }

    public function bajas() {
        $hmModel = new HorarioMedico();
        $horarios = $hmModel->findAllInactive();
        require_once __DIR__ . '/../views/horarios/bajas.php';
    }
}
?>
