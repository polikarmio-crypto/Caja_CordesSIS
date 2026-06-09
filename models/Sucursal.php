<?php
class Sucursal {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll($page = null, $perPage = 30) {
        if ($page === null) {
            return $this->conn->query("SELECT * FROM sucursales WHERE activo = TRUE ORDER BY nombre")->fetchAll();
        }
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT * FROM sucursales 
            WHERE activo = TRUE 
            ORDER BY nombre 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM sucursales WHERE activo = TRUE");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findAllInactive() {
        return $this->conn->query("SELECT * FROM sucursales WHERE activo = FALSE ORDER BY nombre")->fetchAll();
    }

    public function softDelete($id) {
        $stmt = $this->conn->prepare("UPDATE sucursales SET activo = FALSE, estado = 'inactivo' WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hardDelete($id) {
        $stmt = $this->conn->prepare("DELETE FROM sucursales WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE sucursales SET activo = TRUE, estado = 'activo' WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM sucursales WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($nombre, $ubicacion, $horarios, $limitesgeocerca, $id_administrador) {
        $stmt = $this->conn->prepare("
            INSERT INTO sucursales (nombre, ubicacion, horarios, limitesgeocerca, id_administrador)
            VALUES (:n, :u, :h, :l, :a)
        ");
        return $stmt->execute([
            ':n' => $nombre,
            ':u' => $ubicacion,
            ':h' => $horarios,
            ':l' => $limitesgeocerca,
            ':a' => $id_administrador
        ]);
    }

    public function update($id, $nombre, $ubicacion, $horarios, $estado) {
        $stmt = $this->conn->prepare("
            UPDATE sucursales SET nombre = :n, ubicacion = :u, horarios = :h, estado = :e WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':n' => $nombre,
            ':u' => $ubicacion,
            ':h' => $horarios,
            ':e' => $estado
        ]);
    }
}
?>
