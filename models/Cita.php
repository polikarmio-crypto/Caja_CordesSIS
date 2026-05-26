<?php
class Cita {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll($fecha_inicio = null, $fecha_fin = null) {
        $sql = "
            SELECT c.*, 
                   p.nombres as paciente_nombres, p.apellidos as paciente_apellidos,
                   u.email as medico_email
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            JOIN medicos m ON c.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
        ";
        
        $params = [];
        $conditions = [];
        
        if (!empty($fecha_inicio)) {
            $conditions[] = "DATE(c.fecha_hora) >= :fecha_inicio";
            $params[':fecha_inicio'] = $fecha_inicio;
        }
        
        if (!empty($fecha_fin)) {
            $conditions[] = "DATE(c.fecha_hora) <= :fecha_fin";
            $params[':fecha_fin'] = $fecha_fin;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY c.fecha_hora DESC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM citas WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($paciente_id, $medico_id, $fecha_hora, $motivo) {
        $stmt = $this->conn->prepare("INSERT INTO citas (paciente_id, medico_id, fecha_hora, motivo) VALUES (:paciente_id, :medico_id, :fecha_hora, :motivo)");
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':fecha_hora', $fecha_hora);
        $stmt->bindParam(':motivo', $motivo);
        return $stmt->execute();
    }

    public function updateEstado($id, $estado) {
        $stmt = $this->conn->prepare("UPDATE citas SET estado = :estado WHERE id = :id");
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
