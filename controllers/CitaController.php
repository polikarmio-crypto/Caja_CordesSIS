<?php
require_once __DIR__ . '/../models/Cita.php';
require_once __DIR__ . '/../repositories/CitaRepository.php';

/**
 * CitaController — Gestiona citas médicas.
 * Las consultas SQL están delegadas a CitaRepository.
 */
class CitaController {

    public function index() {
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin    = $_GET['fecha_fin']    ?? '';

        $page    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $medico_id  = null;
        $paciente_id = null;

        $repo = new CitaRepository();

        if (($_SESSION['rol_nombre'] ?? '') === 'Médico') {
            $medico_id = $repo->findMedicoIdByUserId($_SESSION['user_id']) ?: -1;
        } elseif (($_SESSION['rol_nombre'] ?? '') === 'Paciente') {
            $paciente_id = $_SESSION['paciente_id'] ?? -1;
        }

        $citaModel  = new Cita();
        $totalCitas = $citaModel->countAll($fecha_inicio, $fecha_fin, $medico_id, $paciente_id);
        $totalPages = ceil($totalCitas / $perPage);
        $citas      = $citaModel->findAll($fecha_inicio, $fecha_fin, $page, $perPage, $medico_id, $paciente_id);

        require_once __DIR__ . '/../views/citas/index.php';
    }

    public function create() {
        if (!isset($_SESSION['rol_nombre'])) {
            header('Location: ' . BASE_URL . '/');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id   = $_POST['medico_id']   ?? '';
            $fecha_hora  = $_POST['fecha_hora']  ?? '';
            $motivo      = $_POST['motivo']      ?? '';
            $tipo        = $_POST['tipo']        ?? 'normal';

            $citaModel = new Cita();
            $repo      = new CitaRepository();

            try {
                // Validar que la cita sea para el futuro
                $cita_date  = date('Y-m-d', strtotime($fecha_hora));
                $today_date = date('Y-m-d');
                if ($cita_date <= $today_date) {
                    throw new Exception("No se pueden programar citas para el mismo día ni para fechas pasadas. Deben agendarse con al menos un día de anticipación.");
                }

                // Validar motivo
                if (empty(trim($motivo)) || strlen(trim($motivo)) < 5) {
                    throw new Exception("El motivo de la consulta debe tener al menos 5 caracteres.");
                }

                // Validar límite de 18:00 para citas normales
                if ($tipo !== 'emergencia') {
                    $hora_cita = (int)date('H', strtotime($fecha_hora));
                    $min_cita  = (int)date('i', strtotime($fecha_hora));
                    if ($hora_cita > 18 || ($hora_cita === 18 && $min_cita > 0)) {
                        throw new Exception("Las citas normales deben programarse antes de las 18:00. Para atención fuera de ese horario, seleccione tipo Emergencia.");
                    }
                }

                // Validar horario médico (solo citas normales)
                if ($tipo !== 'emergencia') {
                    $dia_semana_ing = date('l', strtotime($fecha_hora));
                    $dias = [
                        'Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miercoles',
                        'Thursday' => 'jueves', 'Friday' => 'viernes', 'Saturday' => 'sabado', 'Sunday' => 'domingo'
                    ];
                    $dia_es = $dias[$dia_semana_ing];
                    $hora   = date('H:i:s', strtotime($fecha_hora));

                    $horario = $repo->findHorarioActivo((int)$medico_id, $dia_es, $hora);
                    if (!$horario) {
                        throw new Exception(
                            "El médico no trabaja en el horario solicitado ($dia_es a las $hora). "
                          . "Por favor elige un horario dentro de su turno, o selecciona tipo Emergencia para atención 24/7."
                        );
                    }

                    // Validar ausencias
                    $ausencia = $repo->findAusenciaEnFechaHora((int)$medico_id, $fecha_hora);
                    if ($ausencia) {
                        throw new Exception(
                            "El médico no se encuentra disponible en este horario debido a una ausencia registrada ("
                          . $ausencia['motivo'] . ")."
                        );
                    }
                }

                // Validar solapamiento
                $fecha_hora_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' +30 minutes'));
                if ($repo->findSolapamiento((int)$medico_id, $fecha_hora, $fecha_hora_fin) > 0) {
                    throw new Exception("Solapamiento de agenda: El médico ya tiene una cita reservada a esa hora.");
                }

                // Generar link de videollamada si aplica
                $modalidad        = $_POST['modalidad'] ?? 'presencial';
                $link_videollamada = null;
                if ($modalidad === 'virtual') {
                    $random_code      = substr(md5(uniqid(rand(), true)), 0, 9);
                    $random_meet      = substr($random_code, 0, 3) . '-' . substr($random_code, 3, 3) . '-' . substr($random_code, 6, 3);
                    $link_videollamada = "https://meet.google.com/" . $random_meet;
                }

                // Registrar la cita
                if ($citaModel->create($paciente_id, $medico_id, $fecha_hora, $motivo, $modalidad, $link_videollamada, $tipo)) {
                    $user_id = $_SESSION['user_id'] ?? null;
                    AppLogger::info("Cita agendada ({$tipo})", ['medico_id' => $medico_id, 'paciente_id' => $paciente_id]);
                    if ($user_id) log_activity($user_id, 'Agendar Cita (' . $tipo . ')', 'citas');

                    if (($_SESSION['rol_nombre'] ?? '') === 'Paciente') {
                        header('Location: ' . BASE_URL . '/dashboard?success=1');
                    } else {
                        header('Location: ' . BASE_URL . '/citas?success=1');
                    }
                    exit();
                }
            } catch (Exception $e) {
                AppLogger::warning("Error al agendar cita: " . $e->getMessage(), ['medico_id' => $medico_id]);
                $error = "Error al agendar cita: " . $e->getMessage();
                require_once __DIR__ . '/../views/citas/create.php';
            }
        } else {
            require_once __DIR__ . '/../views/citas/create.php';
        }
    }

    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'])) {
            $cita_id            = $_POST['cita_id'];
            $motivo_cancelacion = $_POST['motivo_cancelacion'] ?? 'No especificado';
            $repo               = new CitaRepository();
            $conn               = Database::getInstance();

            try {
                $conn->beginTransaction();

                // Verificar permisos por rol
                if ($_SESSION['rol_nombre'] === 'Médico') {
                    $medico_id    = $repo->findMedicoIdByUserId($_SESSION['user_id']);
                    $db_med_id    = $repo->findMedicoIdByCitaId((int)$cita_id);
                    if ($db_med_id != $medico_id) die('Acceso denegado.');

                } elseif ($_SESSION['rol_nombre'] === 'Paciente') {
                    $pid       = $_SESSION['paciente_id'] ?? 0;
                    $db_pac_id = $repo->findPacienteIdByCitaId((int)$cita_id);
                    if ($db_pac_id != $pid) die('Acceso denegado.');
                }

                $repo->updateEstadoCita((int)$cita_id, 'cancelada');

                // Notificar al paciente
                $citaInfo = $repo->findCitaInfoParaNotificacion((int)$cita_id);
                if ($citaInfo) {
                    $mensaje = "Tu cita del " . $citaInfo['fecha_hora'] . " ha sido cancelada. Motivo: " . $motivo_cancelacion;
                    $repo->insertNotificacion($citaInfo['paciente_usuario_id'], 'cancelacion_cita', $mensaje);

                    $logLine = "[" . date('Y-m-d H:i:s') . "] TO: " . $citaInfo['paciente_email']
                             . " | SUBJECT: Cita Cancelada | BODY: " . $mensaje . PHP_EOL;
                    file_put_contents(__DIR__ . '/../../logs/emails.log', $logLine, FILE_APPEND);
                }

                AppLogger::info("Cita #{$cita_id} cancelada", ['motivo' => $motivo_cancelacion]);
                log_activity($_SESSION['user_id'] ?? 1, 'Cancelar Cita', 'citas');
                $conn->commit();

                header('Location: ' . BASE_URL . '/citas?success=Cita+cancelada+correctamente');
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                AppLogger::error("Error al cancelar cita #{$cita_id}: " . $e->getMessage());
                header('Location: ' . BASE_URL . '/citas?error=Error+al+cancelar+cita');
                exit();
            }
        }
    }

    public function completar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'])) {
            $citaId = (int)$_POST['cita_id'];
            $repo   = new CitaRepository();

            if ($_SESSION['rol_nombre'] === 'Médico') {
                $medico_id = $repo->findMedicoIdByUserId($_SESSION['user_id']);
                $db_med_id = $repo->findMedicoIdByCitaId($citaId);
                if ($db_med_id != $medico_id) die('Acceso denegado.');
            }

            $repo->completarCita($citaId);
            AppLogger::info("Cita #{$citaId} marcada como completada");
            log_activity($_SESSION['user_id'] ?? 1, 'Completar Cita', 'citas');
            header('Location: ' . BASE_URL . '/citas?success=Cita+marcada+como+completada');
            exit();
        }
    }

    // RF-090: Comprobante PDF de cita
    public function comprobantePdf() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $cita_id = intval($_GET['id'] ?? 0);
        $repo    = new CitaRepository();
        $cita    = $repo->findCitaParaComprobante($cita_id);

        if (!$cita) die('Cita no encontrada.');

        // Verificar acceso según rol
        if ($_SESSION['rol_nombre'] === 'Paciente') {
            $pid = $_SESSION['paciente_id'] ?? 0;
            if ($cita['paciente_id'] != $pid) die('Acceso denegado.');
        } elseif ($_SESSION['rol_nombre'] === 'Médico') {
            $medico_id = $repo->findMedicoIdByUserId($_SESSION['user_id']);
            if ($cita['medico_id'] != $medico_id) die('Acceso denegado.');
        }

        require_once __DIR__ . '/../core/fpdf/fpdf.php';

        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, 'CAJA DE SALUD CORDES', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Comprobante de Cita Medica', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetDrawColor(0, 122, 94);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(60, 8, 'Numero de Cita:', 0, 0);
        $pdf->SetFont('Arial', '', 11);  $pdf->Cell(0, 8, '#' . $cita['id'], 0, 1);
        $pdf->SetFont('Arial', 'B', 11); $pdf->Cell(60, 8, 'Estado:', 0, 0);
        $pdf->SetFont('Arial', '', 11);  $pdf->Cell(0, 8, ucfirst($cita['estado']), 0, 1);

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, 'Datos del Paciente', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Paciente:', 0, 0);
        $pdf->Cell(0, 7, utf8_decode($cita['pac_nombres'] . ' ' . $cita['pac_apellidos']), 0, 1);
        $pdf->Cell(60, 7, 'CI:', 0, 0);     $pdf->Cell(0, 7, $cita['ci'],        0, 1);
        $pdf->Cell(60, 7, 'Correo:', 0, 0); $pdf->Cell(0, 7, $cita['pac_email'], 0, 1);

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, 'Datos del Medico', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Medico:', 0, 0);
        $pdf->Cell(0, 7, 'Dr(a). ' . utf8_decode($cita['med_email']), 0, 1);
        $pdf->Cell(60, 7, 'Especialidad:', 0, 0);
        $pdf->Cell(0, 7, utf8_decode($cita['especialidad'] ?? 'General'), 0, 1);

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(0, 8, 'Detalles de la Cita', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Fecha y Hora:', 0, 0);
        $pdf->Cell(0, 7, date('d/m/Y H:i', strtotime($cita['fecha_hora'])), 0, 1);
        $pdf->Cell(60, 7, 'Motivo:', 0, 0);
        $pdf->MultiCell(0, 7, utf8_decode($cita['motivo']));

        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); $pdf->Ln(3);
        $pdf->Cell(0, 6, 'Documento generado el: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Cell(0, 6, 'Este comprobante es valido solo para la cita indicada.', 0, 1, 'C');

        $pdf->Output('D', 'comprobante_cita_' . $cita_id . '.pdf');
        exit();
    }

    /**
     * AJAX: devuelve médicos por especialidad_id
     */
    public function medicosPorEspecialidad() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit();
        }

        $especialidad_id = intval($_GET['especialidad_id'] ?? 0);
        $repo = new CitaRepository();

        $medicos = ($especialidad_id === 0)
            ? $repo->findAllMedicosConDetalles()
            : $repo->findMedicosByEspecialidad($especialidad_id);

        header('Content-Type: application/json');
        echo json_encode($medicos);
        exit();
    }
}
?>
