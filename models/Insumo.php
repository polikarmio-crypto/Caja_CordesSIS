<?php
class Insumo {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        return $this->conn->query("
            SELECT i.*, c.nombre as categoria_nombre 
            FROM insumos i 
            LEFT JOIN categorias_insumo c ON i.id_categoria = c.id
            ORDER BY i.nombre
        ")->fetchAll();
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
