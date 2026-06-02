<?php
require_once __DIR__ . '/../models/HistoriaClinica.php';

class HistoriaClinicaController {
    public function show($paciente_id) {
        $hcModel = new HistoriaClinica();
        $registros = $hcModel->findByPacienteId($paciente_id);
        
        require_once __DIR__ . '/../models/Laboratorio.php';
        $labModel = new Laboratorio();
        $resultados_lab = $labModel->getResultadosByPaciente($paciente_id);

        require_once __DIR__ . '/../views/historia_clinica/show.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id = $_POST['medico_id'] ?? '';
            $diagnostico = $_POST['diagnostico'] ?? '';
            $receta_notas = $_POST['receta_notas'] ?? '';
            
            $archivo_ruta = null;

            // Procesar array de medicamentos
            $medicamentos = [];
            if(isset($_POST['medicamentos_id']) && is_array($_POST['medicamentos_id'])){
                for($i=0; $i<count($_POST['medicamentos_id']); $i++){
                    if(!empty($_POST['medicamentos_id'][$i])){
                        $medicamentos[] = [
                            'id' => $_POST['medicamentos_id'][$i],
                            'dosis' => $_POST['medicamentos_dosis'][$i] ?? '',
                            'frecuencia' => $_POST['medicamentos_frecuencia'][$i] ?? '',
                            'duracion' => $_POST['medicamentos_duracion'][$i] ?? ''
                        ];
                    }
                }
            }

            // Manejo de subida de archivos (PB-06)
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../public/uploads/seguro/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $tmpName = $_FILES['archivo']['tmp_name'];
                $fileName = time() . '_' . basename($_FILES['archivo']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $archivo_ruta = '/uploads/seguro/' . $fileName;
                }
            }

            $hcModel = new HistoriaClinica();
            
            try {
                if ($hcModel->create($paciente_id, $medico_id, $diagnostico, $receta_notas, $archivo_ruta, $medicamentos)) {
                    $user_id = $_SESSION['user_id'] ?? null;
                    if($user_id) log_activity($user_id, 'Registrar Historia Clinica', 'historia_clinica');

                    header("Location: " . BASE_URL . "/pacientes/" . $paciente_id . "/historia?success=1");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al guardar registro: " . $e->getMessage();
                require_once __DIR__ . '/../views/historia_clinica/create.php';
            }
        } else {
            $paciente_id = $_GET['paciente_id'] ?? '';
            require_once __DIR__ . '/../views/historia_clinica/create.php';
        }
    }
}
?>
