<?php
require_once __DIR__ . '/../models/Hospitalizacion.php';
require_once __DIR__ . '/../models/Paciente.php';

class HospitalizacionController {
    public function index() {
        $hospModel = new Hospitalizacion();
        $matriz = $hospModel->getCamasMatrix();

        $pacienteModel = new Paciente();
        $pacientes = $pacienteModel->findAll();

        require_once __DIR__ . '/../views/hospitalizacion/index.php';
    }

    public function ingresar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cama_id = $_POST['cama_id'];
            $paciente_id = $_POST['paciente_id'];
            $motivo = $_POST['motivo_ingreso'];

            $hospModel = new Hospitalizacion();
            if($hospModel->ingresarPaciente($paciente_id, $cama_id, $motivo)) {
                log_activity($_SESSION['user_id'] ?? 1, 'Ingreso Hospitalario', 'hospitalizaciones');
                header('Location: ' . BASE_URL . '/hospitalizacion?success=Paciente+ingresado+correctamente');
            } else {
                header('Location: ' . BASE_URL . '/hospitalizacion?error=No+se+pudo+ingresar+al+paciente');
            }
            exit();
        }
    }

    public function alta() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cama_id = $_POST['cama_id'];
            $notas = $_POST['notas_alta'];

            $hospModel = new Hospitalizacion();
            if($hospModel->darDeAlta($cama_id, $notas)) {
                log_activity($_SESSION['user_id'] ?? 1, 'Alta Hospitalaria', 'hospitalizaciones');
                header('Location: ' . BASE_URL . '/hospitalizacion?success=Paciente+dado+de+alta+exitosamente');
            } else {
                header('Location: ' . BASE_URL . '/hospitalizacion?error=No+se+pudo+dar+el+alta');
            }
            exit();
        }
    }

    public function limpiar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cama_id = $_POST['cama_id'];
            $hospModel = new Hospitalizacion();
            $hospModel->liberarCama($cama_id);
            header('Location: ' . BASE_URL . '/hospitalizacion?success=Cama+marcada+como+libre+y+lista+para+nuevo+ingreso');
            exit();
        }
    }
}
?>
