<?php
class Laboratorio {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAllExamenes() {
        return $this->conn->query("SELECT * FROM examenes_catalogo ORDER BY nombre")->fetchAll();
    }

    public function getResultadosByPaciente($paciente_id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, e.nombre as examen_nombre, e.tipo_muestra
            FROM resultados_laboratorio r
            JOIN examenes_catalogo e ON r.examen_id = e.id
            WHERE r.paciente_id = :p
            ORDER BY r.fecha_solicitud DESC
        ");
        $stmt->execute([':p' => $paciente_id]);
        return $stmt->fetchAll();
    }

    public function getAllResultados() {
        return $this->conn->query("
            SELECT r.*, e.nombre as examen_nombre, p.nombres, p.apellidos, p.ci
            FROM resultados_laboratorio r
            JOIN examenes_catalogo e ON r.examen_id = e.id
            JOIN pacientes p ON r.paciente_id = p.id
            ORDER BY r.fecha_solicitud DESC
        ")->fetchAll();
    }

    public function registrarResultado($paciente_id, $examen_id, $resultado, $valores_ref) {
        $stmt = $this->conn->prepare("
            INSERT INTO resultados_laboratorio (paciente_id, examen_id, fecha_resultado, resultado, valores_referencia, estado) 
            VALUES (:p, :e, NOW(), :r, :v, 'completado')
        ");
        return $stmt->execute([
            ':p' => $paciente_id,
            ':e' => $examen_id,
            ':r' => $resultado,
            ':v' => $valores_ref
        ]);
    }
}
?>
