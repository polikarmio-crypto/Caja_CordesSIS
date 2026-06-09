<?php
class Insumo {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll($page = null, $perPage = 30) {
        if ($page === null) {
            return $this->conn->query("
                SELECT i.*, c.nombre as categoria_nombre 
                FROM insumos i 
                LEFT JOIN categorias_insumo c ON i.id_categoria = c.id
                WHERE i.activo = TRUE
                ORDER BY i.nombre
            ")->fetchAll();
        }
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT i.*, c.nombre as categoria_nombre 
            FROM insumos i 
            LEFT JOIN categorias_insumo c ON i.id_categoria = c.id
            WHERE i.activo = TRUE
            ORDER BY i.nombre
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM insumos WHERE activo = TRUE");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findAllInactive() {
        return $this->conn->query("
            SELECT i.*, c.nombre as categoria_nombre 
            FROM insumos i 
            LEFT JOIN categorias_insumo c ON i.id_categoria = c.id
            WHERE i.activo = FALSE
            ORDER BY i.nombre
        ")->fetchAll();
    }

    public function softDelete($id) {
        $stmt = $this->conn->prepare("UPDATE insumos SET activo = FALSE, estado = 'inactivo' WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hardDelete($id) {
        $stmt = $this->conn->prepare("DELETE FROM insumos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE insumos SET activo = TRUE, estado = 'activo' WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM insumos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($nombre, $descripcion, $precio, $cantidad, $id_categoria) {
        $stmt = $this->conn->prepare("
            INSERT INTO insumos (nombre, descripcion, precio_unitario, cantidad, id_categoria)
            VALUES (:n, :d, :p, :c, :cat)
        ");
        return $stmt->execute([
            ':n' => $nombre,
            ':d' => $descripcion,
            ':p' => $precio,
            ':c' => $cantidad,
            ':cat' => $id_categoria
        ]);
    }

    public function updateStock($id, $cantidad) {
        $stmt = $this->conn->prepare("UPDATE insumos SET cantidad = cantidad + :c WHERE id = :id");
        return $stmt->execute([':id' => $id, ':c' => $cantidad]);
    }
}
?>
