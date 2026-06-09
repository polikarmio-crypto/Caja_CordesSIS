<?php
class Medico {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll($page = null, $perPage = 30) {
        if ($page === null) {
            $stmt = $this->conn->prepare("
                SELECT m.*, u.email, STRING_AGG(e.nombre, ', ') as especialidades
                FROM medicos m
                JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN medico_especialidades me ON m.id = me.medico_id
                LEFT JOIN especialidades e ON me.especialidad_id = e.id
                WHERE m.activo = TRUE
                GROUP BY m.id, u.email
                ORDER BY m.id DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT m.*, u.email, STRING_AGG(e.nombre, ', ') as especialidades
            FROM medicos m
            JOIN usuarios u ON m.usuario_id = u.id
            LEFT JOIN medico_especialidades me ON m.id = me.medico_id
            LEFT JOIN especialidades e ON me.especialidad_id = e.id
            WHERE m.activo = TRUE
            GROUP BY m.id, u.email
            ORDER BY m.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM medicos WHERE activo = TRUE");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findAllInactive() {
        $stmt = $this->conn->prepare("
            SELECT m.*, u.email, STRING_AGG(e.nombre, ', ') as especialidades
            FROM medicos m
            JOIN usuarios u ON m.usuario_id = u.id
            LEFT JOIN medico_especialidades me ON m.id = me.medico_id
            LEFT JOIN especialidades e ON me.especialidad_id = e.id
            WHERE m.activo = FALSE
            GROUP BY m.id, u.email
            ORDER BY m.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete($id) {
        $stmt = $this->conn->prepare("UPDATE medicos SET activo = FALSE WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hardDelete($id) {
        $stmt = $this->conn->prepare("DELETE FROM medicos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE medicos SET activo = TRUE WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function create($usuario_id, $licencia_medica, $especialidades = []) {
        $stmt = $this->conn->prepare("INSERT INTO medicos (usuario_id, licencia_medica) VALUES (:usuario_id, :licencia_medica)");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':licencia_medica', $licencia_medica);
        
        if ($stmt->execute()) {
            $medico_id = $this->conn->lastInsertId();
            if (!empty($especialidades)) {
                $stmtSpec = $this->conn->prepare("INSERT INTO medico_especialidades (medico_id, especialidad_id) VALUES (:medico_id, :especialidad_id)");
                foreach ($especialidades as $esp_id) {
                    if (!empty($esp_id)) {
                        $stmtSpec->bindParam(':medico_id', $medico_id);
                        $stmtSpec->bindParam(':especialidad_id', $esp_id);
                        $stmtSpec->execute();
                    }
                }
            }
            return true;
        }
        return false;
    }
}
?>
