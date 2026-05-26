<?php
require_once __DIR__ . '/../config/Database.php';

class Factura {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findAll() {
        $query = "SELECT f.*, p.nombres, p.apellidos, p.ci 
                  FROM facturas f 
                  JOIN pacientes p ON f.paciente_id = p.id 
                  ORDER BY f.fecha_emision DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT f.*, p.nombres, p.apellidos, p.ci 
                  FROM facturas f 
                  JOIN pacientes p ON f.paciente_id = p.id 
                  WHERE f.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($factura) {
            $queryDetalles = "SELECT * FROM factura_detalles WHERE factura_id = :factura_id";
            $stmtD = $this->conn->prepare($queryDetalles);
            $stmtD->bindParam(':factura_id', $id);
            $stmtD->execute();
            $factura['detalles'] = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        }

        return $factura;
    }

    public function updateStatus($id, $estado) {
        $query = "UPDATE facturas SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function createFromDespacho($receta_id, $cantidades) {
        try {
            $this->conn->beginTransaction();

            // Obtener el paciente_id de la receta a través de hc_id -> historia_clinica
            $queryPaciente = "SELECT hc.paciente_id 
                              FROM recetas r 
                              JOIN historia_clinica hc ON r.hc_id = hc.id 
                              WHERE r.id = :receta_id";
            $stmtP = $this->conn->prepare($queryPaciente);
            $stmtP->bindParam(':receta_id', $receta_id);
            $stmtP->execute();
            $paciente = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$paciente) {
                throw new Exception("Receta o Historia Clínica no encontrada.");
            }

            $paciente_id = $paciente['paciente_id'];
            $total = 0;
            $detalles = [];

            // Obtener los precios de los medicamentos despachados
            foreach ($cantidades as $med_id => $cantidad) {
                if ($cantidad > 0) {
                    $queryMed = "SELECT nombre, precio_unitario FROM medicamentos WHERE id = :id";
                    $stmtM = $this->conn->prepare($queryMed);
                    $stmtM->bindParam(':id', $med_id);
                    $stmtM->execute();
                    $med = $stmtM->fetch(PDO::FETCH_ASSOC);

                    if ($med) {
                        $subtotal = $med['precio_unitario'] * $cantidad;
                        $total += $subtotal;
                        $detalles[] = [
                            'concepto' => 'Medicamento: ' . $med['nombre'],
                            'cantidad' => $cantidad,
                            'precio_unitario' => $med['precio_unitario'],
                            'subtotal' => $subtotal
                        ];
                    }
                }
            }

            if ($total > 0) {
                // Crear la cabecera de la factura
                $queryF = "INSERT INTO facturas (paciente_id, total, estado) VALUES (:paciente_id, :total, 'pendiente')";
                $stmtF = $this->conn->prepare($queryF);
                $stmtF->bindParam(':paciente_id', $paciente_id);
                $stmtF->bindParam(':total', $total);
                $stmtF->execute();
                
                $factura_id = $this->conn->lastInsertId();

                // Insertar los detalles
                $queryD = "INSERT INTO factura_detalles (factura_id, concepto, cantidad, precio_unitario, subtotal) 
                           VALUES (:factura_id, :concepto, :cantidad, :precio_unitario, :subtotal)";
                $stmtD = $this->conn->prepare($queryD);

                foreach ($detalles as $det) {
                    $stmtD->bindParam(':factura_id', $factura_id);
                    $stmtD->bindParam(':concepto', $det['concepto']);
                    $stmtD->bindParam(':cantidad', $det['cantidad']);
                    $stmtD->bindParam(':precio_unitario', $det['precio_unitario']);
                    $stmtD->bindParam(':subtotal', $det['subtotal']);
                    $stmtD->execute();
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function createManual($paciente_id, $motivo, $detalles) {
        try {
            $this->conn->beginTransaction();
            $total = 0;
            
            foreach ($detalles as $det) {
                $total += $det['subtotal'];
            }

            $queryF = "INSERT INTO facturas (paciente_id, total, estado, motivo) VALUES (:paciente_id, :total, 'pendiente', :motivo)";
            $stmtF = $this->conn->prepare($queryF);
            $stmtF->bindParam(':paciente_id', $paciente_id);
            $stmtF->bindParam(':total', $total);
            $stmtF->bindParam(':motivo', $motivo);
            $stmtF->execute();
            
            $factura_id = $this->conn->lastInsertId();

            $queryD = "INSERT INTO factura_detalles (factura_id, concepto, cantidad, precio_unitario, subtotal) 
                       VALUES (:factura_id, :concepto, :cantidad, :precio_unitario, :subtotal)";
            $stmtD = $this->conn->prepare($queryD);

            foreach ($detalles as $det) {
                $stmtD->bindParam(':factura_id', $factura_id);
                $stmtD->bindParam(':concepto', $det['concepto']);
                $stmtD->bindParam(':cantidad', $det['cantidad']);
                $stmtD->bindParam(':precio_unitario', $det['precio_unitario']);
                $stmtD->bindParam(':subtotal', $det['subtotal']);
                $stmtD->execute();
            }

            $this->conn->commit();
            return $factura_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
