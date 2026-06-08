<?php
class AusenciaMedico {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function create($medico_id, $fecha_inicio, $fecha_fin, $motivo) {
        $stmt = $this->conn->prepare("
            INSERT INTO ausencias_medicos (medico_id, fecha_inicio, fecha_fin, motivo)
            VALUES (:medico_id, :fecha_inicio, :fecha_fin, :motivo)
        ");
        return $stmt->execute([
            ':medico_id' => $medico_id,
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin,
            ':motivo' => $motivo
        ]);
    }

    public function findByMedico($medico_id) {
        $stmt = $this->conn->prepare("
            SELECT am.*, um.email as medico_email 
            FROM ausencias_medicos am
            JOIN medicos m ON am.medico_id = m.id
            JOIN usuarios um ON m.usuario_id = um.id
            WHERE am.medico_id = :medico_id
            ORDER BY am.fecha_inicio DESC
        ");
        $stmt->execute([':medico_id' => $medico_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll() {
        return $this->conn->query("
            SELECT am.*, um.email as medico_email 
            FROM ausencias_medicos am
            JOIN medicos m ON am.medico_id = m.id
            JOIN usuarios um ON m.usuario_id = um.id
            ORDER BY am.fecha_inicio DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna verdadero si hay un conflicto de ausencia para el médico y fecha indicados.
     */
    public function checkConflict($medico_id, $fecha_hora) {
        $stmt = $this->conn->prepare("
            SELECT id FROM ausencias_medicos 
            WHERE medico_id = :medico_id 
              AND :fecha_hora BETWEEN fecha_inicio AND fecha_fin
        ");
        $stmt->execute([
            ':medico_id' => $medico_id,
            ':fecha_hora' => $fecha_hora
        ]);
        return $stmt->rowCount() > 0;
    }
}
?>
