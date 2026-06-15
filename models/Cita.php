<?php
class Cita {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll($fecha_inicio = null, $fecha_fin = null, $page = null, $perPage = 30, $medico_id = null, $paciente_id = null) {
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

        if (!empty($medico_id)) {
            $conditions[] = "c.medico_id = :medico_id";
            $params[':medico_id'] = $medico_id;
        }

        if (!empty($paciente_id)) {
            $conditions[] = "c.paciente_id = :paciente_id";
            $params[':paciente_id'] = $paciente_id;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY c.fecha_hora DESC";
        
        if ($page !== null) {
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        
        if ($page !== null) {
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll($fecha_inicio = null, $fecha_fin = null, $medico_id = null, $paciente_id = null) {
        $sql = "
            SELECT COUNT(*)
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

        if (!empty($medico_id)) {
            $conditions[] = "c.medico_id = :medico_id";
            $params[':medico_id'] = $medico_id;
        }

        if (!empty($paciente_id)) {
            $conditions[] = "c.paciente_id = :paciente_id";
            $params[':paciente_id'] = $paciente_id;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM citas WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($paciente_id, $medico_id, $fecha_hora, $motivo, $modalidad = 'presencial', $link_videollamada = null, $tipo = 'normal') {
        $stmt = $this->conn->prepare("INSERT INTO citas (paciente_id, medico_id, fecha_hora, motivo, modalidad, link_videollamada, tipo) VALUES (:paciente_id, :medico_id, :fecha_hora, :motivo, :modalidad, :link, :tipo)");
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->bindParam(':medico_id', $medico_id);
        $stmt->bindParam(':fecha_hora', $fecha_hora);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':modalidad', $modalidad);
        $stmt->bindParam(':link', $link_videollamada);
        $stmt->bindParam(':tipo', $tipo);
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
