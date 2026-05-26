<?php
require_once __DIR__ . '/../models/Laboratorio.php';
require_once __DIR__ . '/../models/Paciente.php';

class LaboratorioController {
    private function checkAccess($allowMedico = false) {
        $allowed = ['Administrativo', 'Directivo', 'Laboratorista'];
        if ($allowMedico) $allowed[] = 'Médico';
        
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $allowed)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess(true);
        $labModel = new Laboratorio();
        $resultados = $labModel->getAllResultados();
        require_once __DIR__ . '/../views/laboratorio/index.php';
    }

    public function create() {
        $this->checkAccess(false);
        $labModel = new Laboratorio();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'];
            $examen_id = $_POST['examen_id'];
            $resultado = $_POST['resultado'];
            $valores_ref = $_POST['valores_referencia'];

            if ($labModel->registrarResultado($paciente_id, $examen_id, $resultado, $valores_ref)) {
                log_activity($_SESSION['user_id'] ?? 1, 'Subir Resultado Lab', 'resultados_laboratorio');
                header('Location: ' . BASE_URL . '/laboratorio?success=Resultado+registrado');
                exit();
            } else {
                $error = "Error al guardar el resultado.";
            }
        }

        $examenes = $labModel->findAllExamenes();
        $pacienteModel = new Paciente();
        $pacientes = $pacienteModel->findAll();
        
        require_once __DIR__ . '/../views/laboratorio/create.php';
    }
}
?>
