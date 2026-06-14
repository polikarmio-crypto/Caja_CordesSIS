<?php
require_once __DIR__ . '/../models/Cita.php';

class CitaController {
    public function index() {
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 30;

        $medico_id = null;
        $paciente_id = null;

        if (($_SESSION['rol_nombre'] ?? '') === 'Médico') {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id FROM medicos WHERE usuario_id = :uid");
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $medico_id = $stmt->fetchColumn() ?: -1;
        } elseif (($_SESSION['rol_nombre'] ?? '') === 'Paciente') {
            $paciente_id = $_SESSION['paciente_id'] ?? -1;
        }

        $citaModel = new Cita();
        $totalCitas = $citaModel->countAll($fecha_inicio, $fecha_fin, $medico_id, $paciente_id);
        $totalPages = ceil($totalCitas / $perPage);

        $citas = $citaModel->findAll($fecha_inicio, $fecha_fin, $page, $perPage, $medico_id, $paciente_id);
        
        require_once __DIR__ . '/../views/citas/index.php';
    }

    public function create() {
        // Acceso: Pacientes, Administrativos, Directivos y Médicos pueden agendar citas
        if (!isset($_SESSION['rol_nombre'])) {
            header('Location: ' . BASE_URL . '/');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id   = $_POST['medico_id']   ?? '';
            $fecha_hora  = $_POST['fecha_hora']  ?? '';
            $motivo      = $_POST['motivo']      ?? '';
            $tipo        = $_POST['tipo']        ?? 'normal'; // 'normal' | 'emergencia'

            $citaModel = new Cita();
            
            try {
                $conn = Database::getInstance();

                // Validar que la cita sea programada para el futuro (no hoy ni el pasado)
                $cita_date = date('Y-m-d', strtotime($fecha_hora));
                $today_date = date('Y-m-d');
                if ($cita_date <= $today_date) {
                    throw new Exception("No se pueden programar citas para el mismo día ni para fechas pasadas. Deben agendarse con al menos un día de anticipación.");
                }

                // Validar longitud del motivo
                if (empty(trim($motivo)) || strlen(trim($motivo)) < 5) {
                    throw new Exception("El motivo de la consulta debe tener al menos 5 caracteres.");
                }

                // ── Validar límite de 18:00 para citas normales ──────────────
                if ($tipo !== 'emergencia') {
                    $hora_cita = (int)date('H', strtotime($fecha_hora));
                    $min_cita  = (int)date('i', strtotime($fecha_hora));
                    if ($hora_cita > 18 || ($hora_cita === 18 && $min_cita > 0)) {
                        throw new Exception("Las citas normales deben programarse antes de las 18:00. Para atención fuera de ese horario, seleccione tipo Emergencia.");
                    }
                }

                // ── 1. Validar horario solo para citas normales ──────────────
                if ($tipo !== 'emergencia') {
                    $dia_semana_ing = date('l', strtotime($fecha_hora));
                    $dias = ['Monday'=>'lunes','Tuesday'=>'martes','Wednesday'=>'miercoles',
                             'Thursday'=>'jueves','Friday'=>'viernes','Saturday'=>'sabado','Sunday'=>'domingo'];
                    $dia_es = $dias[$dia_semana_ing];
                    $hora   = date('H:i:s', strtotime($fecha_hora));

                    // La cita puede comenzar hasta 30 min antes del fin de turno
                    $stmtH = $conn->prepare("
                        SELECT * FROM horarios_medicos
                        WHERE medico_id  = :m
                          AND dia_semana = :d
                          AND hora_inicio <= :h
                          AND (hora_fin   >= :h
                               OR hora_fin >= CAST(:h AS TIME) - INTERVAL '30 minutes')
                          AND activo = TRUE
                    ");
                    $stmtH->execute([':m' => $medico_id, ':d' => $dia_es, ':h' => $hora]);
                    if ($stmtH->rowCount() === 0) {
                        throw new Exception(
                            "El médico no trabaja en el horario solicitado ($dia_es a las $hora). "
                          . "Por favor elige un horario dentro de su turno, o selecciona tipo Emergencia para atención 24/7."
                        );
                    }

                    // Validar ausencias médicas registradas
                    $stmtAus = $conn->prepare("
                        SELECT id, motivo FROM ausencias_medicos
                        WHERE medico_id = :m
                          AND :fecha_hora BETWEEN fecha_inicio AND fecha_fin
                    ");
                    $stmtAus->execute([':m' => $medico_id, ':fecha_hora' => $fecha_hora]);
                    if ($stmtAus->rowCount() > 0) {
                        $ausencia = $stmtAus->fetch();
                        throw new Exception(
                            "El médico no se encuentra disponible en este horario debido a una ausencia registrada ("
                          . $ausencia['motivo'] . ")."
                        );
                    }
                }

                // ── 2. Validar solapamiento (aplica a todos los tipos) ───────
                $fecha_hora_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' +30 minutes'));
                $stmtOverlap = $conn->prepare("
                    SELECT id FROM citas 
                    WHERE medico_id = :m AND estado != 'cancelada'
                    AND (fecha_hora < :fin AND (fecha_hora + INTERVAL '30 minutes') > :inicio)
                ");
                $stmtOverlap->execute([':m' => $medico_id, ':inicio' => $fecha_hora, ':fin' => $fecha_hora_fin]);
                if ($stmtOverlap->rowCount() > 0) {
                    throw new Exception("Solapamiento de agenda: El médico ya tiene una cita reservada a esa hora.");
                }

                // ── 3. Generar link de videollamada si aplica ────────────────
                $modalidad = $_POST['modalidad'] ?? 'presencial';
                $link_videollamada = null;
                if ($modalidad === 'virtual') {
                    $random_code = substr(md5(uniqid(rand(), true)), 0, 9);
                    $random_meet = substr($random_code, 0, 3) . '-' . substr($random_code, 3, 3) . '-' . substr($random_code, 6, 3);
                    $link_videollamada = "https://meet.google.com/" . $random_meet;
                }

                // ── 4. Registrar la cita ─────────────────────────────────────
                if ($citaModel->create($paciente_id, $medico_id, $fecha_hora, $motivo, $modalidad, $link_videollamada, $tipo)) {
                    $user_id = $_SESSION['user_id'] ?? null;
                    if ($user_id) log_activity($user_id, 'Agendar Cita (' . $tipo . ')', 'citas');
                    if (($_SESSION['rol_nombre'] ?? '') === 'Paciente') {
                        header('Location: ' . BASE_URL . '/dashboard?success=1');
                    } else {
                        header('Location: ' . BASE_URL . '/citas?success=1');
                    }
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error al agendar cita: " . $e->getMessage();
                require_once __DIR__ . '/../views/citas/create.php';
            }
        } else {
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

                if ($_SESSION['rol_nombre'] === 'Médico') {
                    $stmtMed = $conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid");
                    $stmtMed->execute([':uid' => $_SESSION['user_id']]);
                    $medico_id = $stmtMed->fetchColumn();

                    $stmtCheck = $conn->prepare("SELECT medico_id FROM citas WHERE id = :id");
                    $stmtCheck->execute([':id' => $cita_id]);
                    $db_med_id = $stmtCheck->fetchColumn();

                    if ($db_med_id != $medico_id) {
                        die('Acceso denegado.');
                    }
                } elseif ($_SESSION['rol_nombre'] === 'Paciente') {
                    $pid = $_SESSION['paciente_id'] ?? 0;
                    $stmtCheck = $conn->prepare("SELECT paciente_id FROM citas WHERE id = :id");
                    $stmtCheck->execute([':id' => $cita_id]);
                    $db_pac_id = $stmtCheck->fetchColumn();

                    if ($db_pac_id != $pid) {
                        die('Acceso denegado.');
                    }
                }
                
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
            $citaId = $_POST['cita_id'];

            if ($_SESSION['rol_nombre'] === 'Médico') {
                $stmtMed = $conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid");
                $stmtMed->execute([':uid' => $_SESSION['user_id']]);
                $medico_id = $stmtMed->fetchColumn();

                $stmtCheck = $conn->prepare("SELECT medico_id FROM citas WHERE id = :id");
                $stmtCheck->execute([':id' => $citaId]);
                $db_med_id = $stmtCheck->fetchColumn();

                if ($db_med_id != $medico_id) {
                    die('Acceso denegado.');
                }
            }

            $stmt = $conn->prepare("UPDATE citas SET estado = 'completada' WHERE id = :id AND estado = 'pendiente'");
            $stmt->bindParam(':id', $citaId);
            $stmt->execute();
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
        $conn = Database::getInstance();

        $stmt = $conn->prepare("
            SELECT c.id, c.fecha_hora, c.motivo, c.estado, c.paciente_id, c.medico_id,
                   p.nombres AS pac_nombres, p.apellidos AS pac_apellidos, p.ci,
                   up.email AS pac_email,
                   um.email AS med_email,
                   e.nombre AS especialidad
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN usuarios up ON p.usuario_id = up.id
            JOIN medicos m ON c.medico_id = m.id
            JOIN usuarios um ON m.usuario_id = um.id
            LEFT JOIN medico_especialidades me ON me.medico_id = m.id
            LEFT JOIN especialidades e ON me.especialidad_id = e.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $cita_id]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            die('Cita no encontrada.');
        }

        // Paciente solo puede ver sus propias citas
        if ($_SESSION['rol_nombre'] === 'Paciente') {
            $pid = $_SESSION['paciente_id'] ?? 0;
            if ($cita['paciente_id'] != $pid) {
                die('Acceso denegado.');
            }
        } elseif ($_SESSION['rol_nombre'] === 'Médico') {
            $stmtMed = $conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid");
            $stmtMed->execute([':uid' => $_SESSION['user_id']]);
            $medico_id = $stmtMed->fetchColumn();

            if ($cita['medico_id'] != $medico_id) {
                die('Acceso denegado.');
            }
        }

        require_once __DIR__ . '/../core/fpdf/fpdf.php';

        $pdf = new FPDF();
        $pdf->AddPage();

        // Encabezado
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 12, 'CAJA DE SALUD CORDES', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Comprobante de Cita Medica', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetDrawColor(0, 122, 94);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(6);

        // Numero de cita y estado
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, 'Numero de Cita:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, '#' . $cita['id'], 0, 1);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 8, 'Estado:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 8, ucfirst($cita['estado']), 0, 1);

        // Paciente
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Datos del Paciente', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Paciente:', 0, 0);
        $pdf->Cell(0, 7, utf8_decode($cita['pac_nombres'] . ' ' . $cita['pac_apellidos']), 0, 1);
        $pdf->Cell(60, 7, 'CI:', 0, 0);
        $pdf->Cell(0, 7, $cita['ci'], 0, 1);
        $pdf->Cell(60, 7, 'Correo:', 0, 0);
        $pdf->Cell(0, 7, $cita['pac_email'], 0, 1);

        // Medico
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Datos del Medico', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Medico:', 0, 0);
        $pdf->Cell(0, 7, 'Dr(a). ' . utf8_decode($cita['med_email']), 0, 1);
        $pdf->Cell(60, 7, 'Especialidad:', 0, 0);
        $pdf->Cell(0, 7, utf8_decode($cita['especialidad'] ?? 'General'), 0, 1);

        // Detalles de la cita
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Detalles de la Cita', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 7, 'Fecha y Hora:', 0, 0);
        $pdf->Cell(0, 7, date('d/m/Y H:i', strtotime($cita['fecha_hora'])), 0, 1);
        $pdf->Cell(60, 7, 'Motivo:', 0, 0);
        $pdf->MultiCell(0, 7, utf8_decode($cita['motivo']));

        // Pie
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(3);
        $pdf->Cell(0, 6, 'Documento generado el: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Cell(0, 6, 'Este comprobante es valido solo para la cita indicada.', 0, 1, 'C');

        $pdf->Output('D', 'comprobante_cita_' . $cita_id . '.pdf');
        exit();
    }
    /**
     * AJAX: devuelve médicos por especialidad_id
     * GET /api/medicos-por-especialidad?especialidad_id=X
     */
    public function medicosPorEspecialidad() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit();
        }

        $especialidad_id = intval($_GET['especialidad_id'] ?? 0);
        $conn = Database::getInstance();

        if ($especialidad_id === 0) {
            // Devuelve todos los médicos
            $stmt = $conn->query("
                SELECT m.id,
                       COALESCE(u.nombres || ' ' || u.apellidos, u.email) AS nombre,
                       STRING_AGG(DISTINCT e.nombre, ', ') AS especialidad,
                       (
                           SELECT STRING_AGG(hm.dia_semana || ' (' ||
                               TO_CHAR(hm.hora_inicio,'HH24:MI') || '-' ||
                               TO_CHAR(hm.hora_fin,'HH24:MI') || ')', ', '
                               ORDER BY CASE hm.dia_semana
                                   WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2
                                   WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4
                                   WHEN 'viernes' THEN 5 WHEN 'sabado' THEN 6
                                   ELSE 7 END)
                           FROM horarios_medicos hm WHERE hm.medico_id = m.id AND hm.activo = TRUE
                       ) AS horarios
                FROM medicos m
                JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN medico_especialidades me ON m.id = me.medico_id
                LEFT JOIN especialidades e ON me.especialidad_id = e.id
                WHERE (m.activo IS NULL OR m.activo = TRUE)
                GROUP BY m.id, u.email, u.nombres, u.apellidos
                ORDER BY nombre
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT m.id,
                       COALESCE(u.nombres || ' ' || u.apellidos, u.email) AS nombre,
                       STRING_AGG(DISTINCT e.nombre, ', ') AS especialidad,
                       (
                           SELECT STRING_AGG(hm.dia_semana || ' (' ||
                               TO_CHAR(hm.hora_inicio,'HH24:MI') || '-' ||
                               TO_CHAR(hm.hora_fin,'HH24:MI') || ')', ', '
                               ORDER BY CASE hm.dia_semana
                                   WHEN 'lunes' THEN 1 WHEN 'martes' THEN 2
                                   WHEN 'miercoles' THEN 3 WHEN 'jueves' THEN 4
                                   WHEN 'viernes' THEN 5 WHEN 'sabado' THEN 6
                                   ELSE 7 END)
                           FROM horarios_medicos hm WHERE hm.medico_id = m.id AND hm.activo = TRUE
                       ) AS horarios
                FROM medicos m
                JOIN usuarios u ON m.usuario_id = u.id
                JOIN medico_especialidades me ON m.id = me.medico_id
                LEFT JOIN especialidades e ON me.especialidad_id = e.id
                WHERE (me.especialidad_id = :esp_id OR EXISTS (
                    SELECT 1 FROM especialidades sub
                    WHERE sub.id = me.especialidad_id AND sub.parent_id = :esp_id2
                ))
                AND (m.activo IS NULL OR m.activo = TRUE)
                GROUP BY m.id, u.email, u.nombres, u.apellidos
                ORDER BY nombre
            ");
            $stmt->execute([':esp_id' => $especialidad_id, ':esp_id2' => $especialidad_id]);
        }

        if ($especialidad_id === 0) {
            $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        header('Content-Type: application/json');
        echo json_encode($medicos);
        exit();
    }
}
?>
