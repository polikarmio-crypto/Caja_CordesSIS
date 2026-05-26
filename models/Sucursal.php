<?php
class Sucursal {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        return $this->conn->query("SELECT * FROM sucursales ORDER BY nombre")->fetchAll();
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
