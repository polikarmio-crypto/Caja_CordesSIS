<?php
class Hospitalizacion {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function getCamasMatrix() {
        // Devuelve las habitaciones con sus camas
        $stmt = $this->conn->prepare("
            SELECT h.id as habitacion_id, h.numero as habitacion_numero, h.tipo as habitacion_tipo,
                   c.id as cama_id, c.numero_cama, c.estado as cama_estado,
                   (SELECT p.nombres FROM hospitalizaciones hos JOIN pacientes p ON hos.paciente_id = p.id WHERE hos.cama_id = c.id AND hos.fecha_alta IS NULL LIMIT 1) as paciente_nombres,
                   (SELECT p.apellidos FROM hospitalizaciones hos JOIN pacientes p ON hos.paciente_id = p.id WHERE hos.cama_id = c.id AND hos.fecha_alta IS NULL LIMIT 1) as paciente_apellidos
            FROM habitaciones h
            LEFT JOIN camas c ON h.id = c.habitacion_id
            ORDER BY h.numero, c.numero_cama
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por habitacion
        $matriz = [];
        foreach($result as $row) {
            $hab_id = $row['habitacion_id'];
            if(!isset($matriz[$hab_id])) {
                $matriz[$hab_id] = [
                    'numero' => $row['habitacion_numero'],
                    'tipo' => $row['habitacion_tipo'],
                    'camas' => []
                ];
            }
            if($row['cama_id']) {
                $matriz[$hab_id]['camas'][] = [
                    'id' => $row['cama_id'],
                    'numero' => $row['numero_cama'],
                    'estado' => $row['cama_estado'],
                    'paciente' => $row['paciente_nombres'] ? $row['paciente_nombres'] . ' ' . $row['paciente_apellidos'] : null
                ];
            }
        }
        return $matriz;
    }

    public function ingresarPaciente($paciente_id, $cama_id, $motivo) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("INSERT INTO hospitalizaciones (paciente_id, cama_id, fecha_ingreso, motivo_ingreso) VALUES (:p, :c, NOW(), :m)");
            $stmt->execute([':p' => $paciente_id, ':c' => $cama_id, ':m' => $motivo]);

            $stmtUpdate = $this->conn->prepare("UPDATE camas SET estado = 'ocupada' WHERE id = :c");
            $stmtUpdate->execute([':c' => $cama_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function darDeAlta($cama_id, $notas) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("UPDATE hospitalizaciones SET fecha_alta = NOW(), notas_alta = :n WHERE cama_id = :c AND fecha_alta IS NULL");
            $stmt->execute([':n' => $notas, ':c' => $cama_id]);

            $stmtUpdate = $this->conn->prepare("UPDATE camas SET estado = 'limpieza' WHERE id = :c");
            $stmtUpdate->execute([':c' => $cama_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function liberarCama($cama_id) {
        $stmtUpdate = $this->conn->prepare("UPDATE camas SET estado = 'libre' WHERE id = :c");
        return $stmtUpdate->execute([':c' => $cama_id]);
    }
}
?>
