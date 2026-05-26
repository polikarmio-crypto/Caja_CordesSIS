<?php
require_once __DIR__ . '/../models/Cita.php';

class CitaController {
    public function index() {
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';
        
        $citaModel = new Cita();
        $citas = $citaModel->findAll($fecha_inicio, $fecha_fin);
        
        require_once __DIR__ . '/../views/citas/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id = $_POST['medico_id'] ?? '';
            $fecha_hora = $_POST['fecha_hora'] ?? '';
            $motivo = $_POST['motivo'] ?? '';

            $citaModel = new Cita();
            
            try {
                $conn = Database::getInstance();
                
                // 1. Validar horario de trabajo
                $dia_semana_ing = date('l', strtotime($fecha_hora));
                $dias = ['Monday'=>'lunes','Tuesday'=>'martes','Wednesday'=>'miercoles','Thursday'=>'jueves','Friday'=>'viernes','Saturday'=>'sabado','Sunday'=>'domingo'];
                $dia_es = $dias[$dia_semana_ing];
                $hora = date('H:i:s', strtotime($fecha_hora));
                
                $stmtH = $conn->prepare("SELECT * FROM horarios_medicos WHERE medico_id = :m AND dia_semana = :d AND hora_inicio <= :h AND hora_fin >= :h");
                $stmtH->execute([':m'=>$medico_id, ':d'=>$dia_es, ':h'=>$hora]);
                if($stmtH->rowCount() === 0) {
                    throw new Exception("El médico no trabaja en el horario solicitado ($dia_es a las $hora).");
                }

                // 2. Validar solapamiento (asumiendo citas de 30 mins)
                $fecha_hora_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' +30 minutes'));
                $stmtOverlap = $conn->prepare("
                    SELECT id FROM citas 
                    WHERE medico_id = :m AND estado != 'cancelada'
                    AND (fecha_hora < :fin AND (fecha_hora + INTERVAL '30 minutes') > :inicio)
                ");
                $stmtOverlap->execute([':m'=>$medico_id, ':inicio'=>$fecha_hora, ':fin'=>$fecha_hora_fin]);
                if($stmtOverlap->rowCount() > 0) {
                    throw new Exception("Solapamiento de agenda: El médico ya tiene una cita reservada a esa hora.");
                }

                if ($citaModel->create($paciente_id, $medico_id, $fecha_hora, $motivo)) {
                    $user_id = $_SESSION['user_id'] ?? null;
                    if($user_id) log_activity($user_id, 'Agendar Cita', 'citas');

                    header('Location: ' . BASE_URL . '/citas?success=1');
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al agendar cita: " . $e->getMessage();
                // Fetch patients and medics for the view again
                require_once __DIR__ . '/../views/citas/create.php';
            }
        } else {
            // Needed logic to fetch patients and doctors for select dropdowns
            require_once __DIR__ . '/../views/citas/create.php';
        }
    }

    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'])) {
            $cita_id = $_POST['cita_id'];
            $motivo_cancelacion = $_POST['motivo_cancelacion'] ?? 'No especificado';
            
            $conn = Database::getInstance();
            
            try {
                $conn->beginTransaction();
                
                // Actualizar estado de la cita
                $stmt = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = :id");
                $stmt->bindParam(':id', $cita_id);
                $stmt->execute();
                
                // Obtener datos de la cita para la notificacion
                $stmtInfo = $conn->prepare("
                    SELECT c.fecha_hora, p.nombres, p.apellidos, u.id as paciente_usuario_id, u.email as paciente_email
                    FROM citas c
                    JOIN pacientes p ON c.paciente_id = p.id
                    JOIN usuarios u ON p.usuario_id = u.id
                    WHERE c.id = :id
                ");
                $stmtInfo->bindParam(':id', $cita_id);
                $stmtInfo->execute();
                $citaInfo = $stmtInfo->fetch();
                
                if ($citaInfo) {
                    // 1. Insertar en tabla notificaciones
                    $mensaje = "Tu cita del " . $citaInfo['fecha_hora'] . " ha sido cancelada. Motivo: " . $motivo_cancelacion;
                    $stmtNotif = $conn->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje) VALUES (:usuario_id, 'cancelacion_cita', :mensaje)");
                    $stmtNotif->bindParam(':usuario_id', $citaInfo['paciente_usuario_id']);
                    $stmtNotif->bindParam(':mensaje', $mensaje);
                    $stmtNotif->execute();
                    
                    // 2. Simulación de envío de correo (escribir en log)
                    $logLine = "[" . date('Y-m-d H:i:s') . "] TO: " . $citaInfo['paciente_email'] . " | SUBJECT: Cita Cancelada | BODY: " . $mensaje . PHP_EOL;
                    file_put_contents(__DIR__ . '/../../logs/emails.log', $logLine, FILE_APPEND);
                }
                
                log_activity($_SESSION['user_id'] ?? 1, 'Cancelar Cita', 'citas');
                $conn->commit();
                
                header('Location: ' . BASE_URL . '/citas?success=Cita+cancelada+correctamente');
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                header('Location: ' . BASE_URL . '/citas?error=Error+al+cancelar+cita');
                exit();
            }
        }
    }

    public function completar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'])) {
            $conn = Database::getInstance();
            $stmt = $conn->prepare("UPDATE citas SET estado = 'completada' WHERE id = :id AND estado = 'pendiente'");
            $stmt->bindParam(':id', $_POST['cita_id']);
            $stmt->execute();
            log_activity($_SESSION['user_id'] ?? 1, 'Completar Cita', 'citas');
            header('Location: ' . BASE_URL . '/citas?success=Cita+marcada+como+completada');
            exit();
        }
    }
}
?>
