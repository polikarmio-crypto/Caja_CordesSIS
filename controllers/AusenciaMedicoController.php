<?php
require_once __DIR__ . '/../models/AusenciaMedico.php';

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
        $rol = $_SESSION['rol_nombre'];
        $user_id = $_SESSION['user_id'];
        $conn = Database::getInstance();

        $ausModel = new AusenciaMedico();

        if ($rol === 'Médico') {
            // Obtener el ID del médico actual
            $stmt = $conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $user_id]);
            $medico = $stmt->fetch();
            $medico_id = $medico ? $medico['id'] : 0;

            $ausencias = $ausModel->findByMedico($medico_id);
        } else {
            $ausencias = $ausModel->findAll();
        }

        require_once __DIR__ . '/../views/ausencias/index.php';
    }

    public function create() {
        $this->checkAccess();
        $rol = $_SESSION['rol_nombre'];
        $user_id = $_SESSION['user_id'];
        $conn = Database::getInstance();

        // Obtener lista de médicos para que el admin pueda registrarle la ausencia
        // Si es médico, solo puede registrar para sí mismo
        $medico_id = 0;
        if ($rol === 'Médico') {
            $stmt = $conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid LIMIT 1");
            $stmt->execute([':uid' => $user_id]);
            $medico = $stmt->fetch();
            $medico_id = $medico ? $medico['id'] : 0;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $target_medico_id = ($rol === 'Médico') ? $medico_id : intval($_POST['medico_id'] ?? 0);
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? '';
            $motivo = $_POST['motivo'] ?? '';

            if (!$target_medico_id || !$fecha_inicio || !$fecha_fin) {
                $error = "Todos los campos son obligatorios.";
            } else {
                try {
                    $conn->beginTransaction();

                    // 1. Guardar la ausencia
                    $ausModel = new AusenciaMedico();
                    $ausModel->create($target_medico_id, $fecha_inicio, $fecha_fin, $motivo);

                    // 2. Buscar citas en ese rango y médico para cancelarlas y notificar
                    $stmtCitas = $conn->prepare("
                        SELECT c.id, c.fecha_hora, p.nombres, p.apellidos, u.id as paciente_usuario_id, u.email as paciente_email
                        FROM citas c
                        JOIN pacientes p ON c.paciente_id = p.id
                        JOIN usuarios u ON p.usuario_id = u.id
                        WHERE c.medico_id = :mid AND c.estado = 'pendiente'
                          AND c.fecha_hora BETWEEN :inicio AND :fin
                    ");
                    $stmtCitas->execute([
                        ':mid' => $target_medico_id,
                        ':inicio' => $fecha_inicio,
                        ':fin' => $fecha_fin
                    ]);
                    $citas_afectadas = $stmtCitas->fetchAll();

                    foreach ($citas_afectadas as $cita) {
                        // Cancelar cita
                        $stmtUpd = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = :id");
                        $stmtUpd->execute([':id' => $cita['id']]);

                        // Enviar notificación al paciente
                        $mensaje = "Tu cita programada para el " . date('d/m/Y H:i', strtotime($cita['fecha_hora'])) . " ha sido cancelada por ausencia imprevista del médico. Motivo: " . $motivo;
                        $stmtNotif = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje) VALUES (:usuario_id, 'cancelacion_cita', :mensaje)");
                        $stmtNotif->execute([
                            ':usuario_id' => $cita['paciente_usuario_id'],
                            ':mensaje' => $mensaje
                        ]);

                        // Registrar log de envío de correo simulado
                        $logLine = "[" . date('Y-m-d H:i:s') . "] TO: " . $cita['paciente_email'] . " | SUBJECT: Cita Cancelada por Ausencia Medica | BODY: " . $mensaje . PHP_EOL;
                        file_put_contents(__DIR__ . '/../../logs/emails.log', $logLine, FILE_APPEND);
                    }

                    log_activity($user_id, 'Registrar Ausencia Medica', 'ausencias_medicos');
                    $conn->commit();

                    header('Location: ' . BASE_URL . '/ausencias?success=Ausencia+registrada+y+citas+notificadas');
                    exit();
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Error al registrar la ausencia: " . $e->getMessage();
                }
            }
        }

        // Obtener lista de médicos para el select (solo Administrativo/Directivo)
        $medicosList = [];
        if ($rol !== 'Médico') {
            $medicosList = $conn->query("
                SELECT m.id, u.email as medico_email 
                FROM medicos m 
                JOIN usuarios u ON m.usuario_id = u.id
            ")->fetchAll();
        }

        require_once __DIR__ . '/../views/ausencias/create.php';
    }
}
?>
