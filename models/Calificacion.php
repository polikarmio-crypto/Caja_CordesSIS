<?php
class Calificacion {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function create($cita_id, $paciente_id, $medico_id, $puntuacion, $comentarios) {
        $stmt = $this->conn->prepare("
            INSERT INTO calificaciones (cita_id, paciente_id, medico_id, puntuacion, comentarios)
            VALUES (:c, :p, :m, :score, :com)
        ");
        return $stmt->execute([
            ':c' => $cita_id,
            ':p' => $paciente_id,
            ':m' => $medico_id,
            ':score' => $puntuacion,
            ':com' => $comentarios
        ]);
    }

    public function getPromedioByMedico($medico_id) {
        $stmt = $this->conn->prepare("SELECT AVG(puntuacion) as promedio, COUNT(id) as total FROM calificaciones WHERE medico_id = :m");
        $stmt->execute([':m' => $medico_id]);
        return $stmt->fetch();
    }
}
?>
