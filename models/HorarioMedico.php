<?php
class HorarioMedico {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        $stmt = $this->conn->prepare("
            SELECT hm.*, u.email as medico_email 
            FROM horarios_medicos hm
            JOIN medicos m ON hm.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE hm.activo = TRUE
            ORDER BY hm.medico_id, 
                CASE hm.dia_semana
                    WHEN 'lunes' THEN 1
                    WHEN 'martes' THEN 2
                    WHEN 'miercoles' THEN 3
                    WHEN 'jueves' THEN 4
                    WHEN 'viernes' THEN 5
                    WHEN 'sabado' THEN 6
                    WHEN 'domingo' THEN 7
                    ELSE 8
                END
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllInactive() {
        $stmt = $this->conn->prepare("
            SELECT hm.*, u.email as medico_email 
            FROM horarios_medicos hm
            JOIN medicos m ON hm.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE hm.activo = FALSE
            ORDER BY hm.medico_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByMedicoId($medico_id) {
        $stmt = $this->conn->prepare("SELECT * FROM horarios_medicos WHERE medico_id = :medico_id");
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($medico_id, $dia_semana, $hora_inicio, $hora_fin) {
        $stmt = $this->conn->prepare("INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin) VALUES (:medico_id, :dia_semana, :hora_inicio, :hora_fin)");
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':dia_semana', $dia_semana);
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->bindParam(':hora_fin', $hora_fin);
        return $stmt->execute();
    }

    /**
     * Baja lógica: oculta el registro sin eliminarlo de la BD.
     */
    public function softDelete($id) {
        $stmt = $this->conn->prepare("UPDATE horarios_medicos SET activo = FALSE WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Eliminación permanente: SOLO tras confirmar explícitamente.
     */
    public function hardDelete($id) {
        $stmt = $this->conn->prepare("DELETE FROM horarios_medicos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Restaurar un registro dado de baja lógicamente.
     */
    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE horarios_medicos SET activo = TRUE WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
