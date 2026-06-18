<?php
/**
 * AusenciaMedicoRepository — Centraliza consultas SQL para ausencias médicas.
 */
class AusenciaMedicoRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Obtiene el ID de médico dado su usuario_id.
     */
    public function findMedicoIdByUserId(int $userId): int|false {
        $stmt = $this->conn->prepare("SELECT id FROM medicos WHERE usuario_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Obtiene lista de médicos para el select (para admin/directivo).
     */
    public function findAllMedicosParaSelect(): array {
        return $this->conn->query("
            SELECT m.id, u.email as medico_email
              FROM medicos m
              JOIN usuarios u ON m.usuario_id = u.id
        ")->fetchAll();
    }

    /**
     * Busca citas pendientes del médico en un rango de fechas (para cancelarlas).
     */
    public function findCitasPendientesEnRango(int $medicoId, string $fechaInicio, string $fechaFin): array {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.fecha_hora, p.nombres, p.apellidos,
                   u.id as paciente_usuario_id, u.email as paciente_email
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              JOIN usuarios u ON p.usuario_id = u.id
             WHERE c.medico_id = :mid AND c.estado = 'pendiente'
               AND c.fecha_hora BETWEEN :inicio AND :fin
        ");
        $stmt->execute([
            ':mid'    => $medicoId,
            ':inicio' => $fechaInicio,
            ':fin'    => $fechaFin
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Cancela una cita por ID.
     */
    public function cancelarCita(int $citaId): void {
        $stmt = $this->conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = :id");
        $stmt->execute([':id' => $citaId]);
    }

    /**
     * Inserta notificación para un usuario.
     */
    public function insertNotificacion(int $usuarioId, string $mensaje): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO notificaciones (usuario_id, tipo, mensaje)
             VALUES (:usuario_id, 'cancelacion_cita', :mensaje)"
        );
        $stmt->execute([':usuario_id' => $usuarioId, ':mensaje' => $mensaje]);
    }
}
?>
