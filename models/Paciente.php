<?php
class Paciente {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.email, STRING_AGG(pt.telefono, ', ') as telefono
            FROM pacientes p 
            JOIN usuarios u ON p.usuario_id = u.id 
            LEFT JOIN paciente_telefonos pt ON p.id = pt.paciente_id 
            GROUP BY p.id, u.email
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.email, STRING_AGG(pt.telefono, ', ') as telefono
            FROM pacientes p 
            JOIN usuarios u ON p.usuario_id = u.id 
            LEFT JOIN paciente_telefonos pt ON p.id = pt.paciente_id 
            WHERE p.id = :id 
            GROUP BY p.id, u.email LIMIT 1
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($usuario_id, $ci, $nombres, $apellidos, $fecha_nac, $telefonos = []) {
        $stmt = $this->conn->prepare("INSERT INTO pacientes (usuario_id, CI, nombres, apellidos, fecha_nac) VALUES (:usuario_id, :ci, :nombres, :apellidos, :fecha_nac)");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':fecha_nac', $fecha_nac);
        
        if ($stmt->execute()) {
            $paciente_id = $this->conn->lastInsertId();
            if (!empty($telefonos)) {
                $stmtTel = $this->conn->prepare("INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:paciente_id, :telefono)");
                foreach($telefonos as $tel) {
                    if (!empty(trim($tel))) {
                        $stmtTel->bindParam(':paciente_id', $paciente_id);
                        $stmtTel->bindParam(':telefono', $tel);
                        $stmtTel->execute();
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function update($id, $ci, $nombres, $apellidos, $fecha_nac, $telefonos = []) {
        $stmt = $this->conn->prepare("UPDATE pacientes SET CI = :ci, nombres = :nombres, apellidos = :apellidos, fecha_nac = :fecha_nac WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':ci', $ci);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':fecha_nac', $fecha_nac);
        
        if ($stmt->execute()) {
            // Reemplazar telefonos
            $stmtDel = $this->conn->prepare("DELETE FROM paciente_telefonos WHERE paciente_id = :id");
            $stmtDel->bindParam(':id', $id);
            $stmtDel->execute();
            
            if (!empty($telefonos)) {
                $stmtTel = $this->conn->prepare("INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:paciente_id, :telefono)");
                foreach($telefonos as $tel) {
                    if (!empty(trim($tel))) {
                        $stmtTel->bindParam(':paciente_id', $id);
                        $stmtTel->bindParam(':telefono', $tel);
                        $stmtTel->execute();
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function findByUsuarioId($usuario_id) {
        $stmt = $this->conn->prepare("SELECT * FROM pacientes WHERE usuario_id = :uid LIMIT 1");
        $stmt->bindParam(':uid', $usuario_id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
