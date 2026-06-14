<?php
class Medicamento {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        $stmt = $this->conn->prepare("SELECT * FROM medicamentos WHERE activo = TRUE ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findAllInactive() {
        $stmt = $this->conn->prepare("SELECT * FROM medicamentos WHERE activo = FALSE ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM medicamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function findByCodigo($codigo) {
        $stmt = $this->conn->prepare("SELECT * FROM medicamentos WHERE codigo_identificacion = :cod AND activo = TRUE");
        $stmt->execute([':cod' => $codigo]);
        return $stmt->fetch();
    }

    public function search($query) {
        $stmt = $this->conn->prepare("
            SELECT * FROM medicamentos 
            WHERE (nombre ILIKE :q OR codigo_identificacion ILIKE :q) AND activo = TRUE 
            ORDER BY nombre
        ");
        $stmt->execute([':q' => "%" . $query . "%"]);
        return $stmt->fetchAll();
    }

    public function updateStockAndPrice($id, $stock, $precio) {
        $stmt = $this->conn->prepare("UPDATE medicamentos SET stock = :s, precio_unitario = :p WHERE id = :id");
        return $stmt->execute([':s' => $stock, ':p' => $precio, ':id' => $id]);
    }

    public function create($nombre, $tipo, $stock, $precio, $vencimiento, $codigo) {
        $stmt = $this->conn->prepare("INSERT INTO medicamentos (nombre, tipo, stock, precio_unitario, vencimiento, codigo_identificacion) VALUES (:n, :t, :s, :p, :v, :c)");
        return $stmt->execute([':n' => $nombre, ':t' => $tipo, ':s' => $stock, ':p' => $precio, ':v' => $vencimiento, ':c' => $codigo]);
    }

    public function softDelete($id) {
        $stmt = $this->conn->prepare("UPDATE medicamentos SET activo = FALSE WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE medicamentos SET activo = TRUE WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function hardDelete($id) {
        $stmt = $this->conn->prepare("DELETE FROM medicamentos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Para despacho
    public function getRecetasPendientes() {
        $stmt = $this->conn->prepare("
            SELECT r.id as receta_id, r.fecha_creacion, r.estado_despacho,
                   p.nombres, p.apellidos, p.ci,
                   u.email as medico_email
            FROM recetas r
            JOIN historia_clinica hc ON r.hc_id = hc.id
            JOIN pacientes p ON hc.paciente_id = p.id
            JOIN medicos m ON hc.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE r.estado_despacho = 'pendiente'
            ORDER BY r.fecha_creacion ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecetasDespachadas() {
        $stmt = $this->conn->prepare("
            SELECT r.id as receta_id, r.fecha_creacion, r.estado_despacho,
                   p.nombres, p.apellidos, p.ci,
                   u.email as medico_email
            FROM recetas r
            JOIN historia_clinica hc ON r.hc_id = hc.id
            JOIN pacientes p ON hc.paciente_id = p.id
            JOIN medicos m ON hc.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE r.estado_despacho = 'entregado'
            ORDER BY r.fecha_creacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecetaById($receta_id) {
        $stmt = $this->conn->prepare("
            SELECT r.id as receta_id, r.fecha_creacion, r.estado_despacho,
                   p.nombres, p.apellidos, p.ci,
                   u.email as medico_email
            FROM recetas r
            JOIN historia_clinica hc ON r.hc_id = hc.id
            JOIN pacientes p ON hc.paciente_id = p.id
            JOIN medicos m ON hc.medico_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE r.id = :id
        ");
        $stmt->execute([':id' => $receta_id]);
        return $stmt->fetch();
    }

    public function getDetallesReceta($receta_id) {
        $stmt = $this->conn->prepare("
            SELECT rm.*, m.nombre, m.stock, m.precio_unitario 
            FROM receta_medicamentos rm
            JOIN medicamentos m ON rm.medicamento_id = m.id
            WHERE rm.receta_id = :r
        ");
        $stmt->execute([':r' => $receta_id]);
        return $stmt->fetchAll();
    }

    public function despacharReceta($receta_id, $cantidades_a_descontar) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Actualizar stock
            foreach($cantidades_a_descontar as $med_id => $cantidad) {
                $stmt = $this->conn->prepare("UPDATE medicamentos SET stock = stock - :c WHERE id = :id AND stock >= :c");
                $stmt->execute([':c' => $cantidad, ':id' => $med_id]);
                if($stmt->rowCount() == 0) {
                    throw new Exception("Stock insuficiente para el medicamento ID $med_id");
                }
            }

            // 2. Marcar receta como entregada
            $stmtR = $this->conn->prepare("UPDATE recetas SET estado_despacho = 'entregado' WHERE id = :r");
            $stmtR->execute([':r' => $receta_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
