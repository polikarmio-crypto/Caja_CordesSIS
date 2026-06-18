<?php
/**
 * CitaRepository — Centraliza todas las consultas SQL relacionadas con citas médicas.
 */
class CitaRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Obtiene el ID de médico dado su usuario_id.
     */
    public function findMedicoIdByUserId(int $userId): int|false {
        $stmt = $this->conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Verifica si el médico tiene horario activo en el día y hora dados.
     */
    public function findHorarioActivo(int $medicoId, string $diaSemana, string $hora): array|false {
        $stmt = $this->conn->prepare("
            SELECT * FROM horarios_medicos
            WHERE medico_id  = :m
              AND dia_semana = :d
              AND hora_inicio <= :h
              AND (hora_fin   >= :h
                   OR hora_fin >= CAST(:h AS TIME) - INTERVAL '30 minutes')
              AND activo = TRUE
        ");
        $stmt->execute([':m' => $medicoId, ':d' => $diaSemana, ':h' => $hora]);
        return $stmt->fetch();
    }

    /**
     * Verifica si hay ausencia médica registrada para esa fecha/hora.
     */
    public function findAusenciaEnFechaHora(int $medicoId, string $fechaHora): array|false {
        $stmt = $this->conn->prepare("
            SELECT id, motivo FROM ausencias_medicos
            WHERE medico_id = :m
              AND :fecha_hora BETWEEN fecha_inicio AND fecha_fin
        ");
        $stmt->execute([':m' => $medicoId, ':fecha_hora' => $fechaHora]);
        return $stmt->fetch();
    }

    /**
     * Verifica solapamiento de citas para un médico.
     */
    public function findSolapamiento(int $medicoId, string $fechaHoraInicio, string $fechaHoraFin): int {
        $stmt = $this->conn->prepare("
            SELECT id FROM citas
            WHERE medico_id = :m AND estado != 'cancelada'
              AND (fecha_hora < :fin AND (fecha_hora + INTERVAL '30 minutes') > :inicio)
        ");
        $stmt->execute([':m' => $medicoId, ':inicio' => $fechaHoraInicio, ':fin' => $fechaHoraFin]);
        return $stmt->rowCount();
    }

    /**
     * Obtiene medico_id de una cita.
     */
    public function findMedicoIdByCitaId(int $citaId): int|false {
        $stmt = $this->conn->prepare("SELECT medico_id FROM citas WHERE id = :id");
        $stmt->execute([':id' => $citaId]);
        return $stmt->fetchColumn();
    }

    /**
     * Obtiene paciente_id de una cita.
     */
    public function findPacienteIdByCitaId(int $citaId): int|false {
        $stmt = $this->conn->prepare("SELECT paciente_id FROM citas WHERE id = :id");
        $stmt->execute([':id' => $citaId]);
        return $stmt->fetchColumn();
    }

    /**
     * Actualiza el estado de una cita.
     */
    public function updateEstadoCita(int $citaId, string $estado): bool {
        $stmt = $this->conn->prepare("UPDATE citas SET estado = :estado WHERE id = :id");
        return $stmt->execute([':estado' => $estado, ':id' => $citaId]);
    }

    /**
     * Actualiza cita a completada (solo si estaba pendiente).
     */
    public function completarCita(int $citaId): bool {
        $stmt = $this->conn->prepare(
            "UPDATE citas SET estado = 'completada' WHERE id = :id AND estado = 'pendiente'"
        );
        $stmt->execute([':id' => $citaId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene info de paciente/usuario de una cita para notificación.
     */
    public function findCitaInfoParaNotificacion(int $citaId): array|false {
        $stmt = $this->conn->prepare("
            SELECT c.fecha_hora, p.nombres, p.apellidos,
                   u.id as paciente_usuario_id, u.email as paciente_email
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              JOIN usuarios u ON p.usuario_id = u.id
             WHERE c.id = :id
        ");
        $stmt->execute([':id' => $citaId]);
        return $stmt->fetch();
    }

    /**
     * Inserta una notificación al usuario.
     */
    public function insertNotificacion(int $usuarioId, string $tipo, string $mensaje): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO notificaciones (usuario_id, tipo, mensaje) VALUES (:usuario_id, :tipo, :mensaje)"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':tipo' => $tipo, ':mensaje' => $mensaje]);
    }

    /**
     * Obtiene datos completos de una cita para el comprobante PDF.
     */
    public function findCitaParaComprobante(int $citaId): array|false {
        $stmt = $this->conn->prepare("
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
        $stmt->execute([':id' => $citaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los médicos con sus especialidades y horarios.
     */
    public function findAllMedicosConDetalles(): array {
        $stmt = $this->conn->query("
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene médicos filtrados por especialidad_id.
     */
    public function findMedicosByEspecialidad(int $especialidadId): array {
        $stmt = $this->conn->prepare("
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
        $stmt->execute([':esp_id' => $especialidadId, ':esp_id2' => $especialidadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
