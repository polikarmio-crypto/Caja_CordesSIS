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
            $hmModel->delete($_POST['id']);
            log_activity($_SESSION['user_id'] ?? 1, 'Eliminar Horario', 'horarios_medicos');
            header('Location: ' . BASE_URL . '/horarios?success=1');
            exit();
        }
    }
}
?>
