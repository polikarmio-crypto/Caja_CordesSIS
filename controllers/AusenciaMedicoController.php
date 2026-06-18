<?php
require_once __DIR__ . '/../models/AusenciaMedico.php';
require_once __DIR__ . '/../repositories/AusenciaMedicoRepository.php';

/**
 * AusenciaMedicoController — Gestiona ausencias de médicos.
 * Las consultas SQL están delegadas a AusenciaMedicoRepository.
 */
class AusenciaMedicoController {

    private function checkAccess() {
        $allowed = ['Administrativo', 'Directivo', 'Médico'];
        if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $allowed)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $rol     = $_SESSION['rol_nombre'];
        $user_id = $_SESSION['user_id'];
        $repo    = new AusenciaMedicoRepository();
        $ausModel = new AusenciaMedico();

        if ($rol === 'Médico') {
            $medico_id = $repo->findMedicoIdByUserId($user_id) ?: 0;
            $ausencias = $ausModel->findByMedico($medico_id);
        } else {
            $ausencias = $ausModel->findAll();
        }

        require_once __DIR__ . '/../views/ausencias/index.php';
    }

    public function create() {
        $this->checkAccess();
        $rol     = $_SESSION['rol_nombre'];
        $user_id = $_SESSION['user_id'];
        $repo    = new AusenciaMedicoRepository();

        $medico_id = 0;
        if ($rol === 'Médico') {
            $medico_id = $repo->findMedicoIdByUserId($user_id) ?: 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $target_medico_id = ($rol === 'Médico') ? $medico_id : intval($_POST['medico_id'] ?? 0);
            $fecha_inicio     = $_POST['fecha_inicio'] ?? '';
            $fecha_fin        = $_POST['fecha_fin']    ?? '';
            $motivo           = $_POST['motivo']       ?? '';

            if (!$target_medico_id || !$fecha_inicio || !$fecha_fin) {
                $error = "Todos los campos son obligatorios.";
            } else {
                try {
                    $conn = Database::getInstance();
                    $conn->beginTransaction();

                    // 1. Guardar la ausencia
                    $ausModel = new AusenciaMedico();
                    $ausModel->create($target_medico_id, $fecha_inicio, $fecha_fin, $motivo);

                    // 2. Buscar citas en el rango y cancelarlas con notificación
                    $citas_afectadas = $repo->findCitasPendientesEnRango($target_medico_id, $fecha_inicio, $fecha_fin);

                    foreach ($citas_afectadas as $cita) {
                        $repo->cancelarCita($cita['id']);

                        $mensaje = "Tu cita programada para el "
                                 . date('d/m/Y H:i', strtotime($cita['fecha_hora']))
                                 . " ha sido cancelada por ausencia imprevista del médico. Motivo: " . $motivo;

                        $repo->insertNotificacion($cita['paciente_usuario_id'], $mensaje);

                        // Log simulado de correo
                        $logLine = "[" . date('Y-m-d H:i:s') . "] TO: " . $cita['paciente_email']
                                 . " | SUBJECT: Cita Cancelada por Ausencia Medica | BODY: " . $mensaje . PHP_EOL;
                        file_put_contents(__DIR__ . '/../../logs/emails.log', $logLine, FILE_APPEND);
                    }

                    AppLogger::info(
                        "Ausencia médica registrada. Citas canceladas: " . count($citas_afectadas),
                        ['medico_id' => $target_medico_id, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin],
                        'application'
                    );
                    log_activity($user_id, 'Registrar Ausencia Medica', 'ausencias_medicos');
                    $conn->commit();

                    header('Location: ' . BASE_URL . '/ausencias?success=Ausencia+registrada+y+citas+notificadas');
                    exit();
                } catch (Exception $e) {
                    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
                    AppLogger::error("Error al registrar ausencia: " . $e->getMessage(), ['medico_id' => $target_medico_id]);
                    $error = "Error al registrar la ausencia: " . $e->getMessage();
                }
            }
        }

        // Obtener lista de médicos para el select (solo Administrativo/Directivo)
        $medicosList = [];
        if ($rol !== 'Médico') {
            $medicosList = $repo->findAllMedicosParaSelect();
        }

        require_once __DIR__ . '/../views/ausencias/create.php';
    }
}
?>
