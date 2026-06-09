<?php
class HistoriaClinica {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findByPacienteId($paciente_id) {
        $stmt = $this->conn->prepare("
            SELECT hc.id, hc.fecha_registro,
                   (SELECT diagnostico FROM hc_diagnosticos WHERE hc_id = hc.id LIMIT 1) as diagnostico,
                   (SELECT indicaciones_generales FROM recetas WHERE hc_id = hc.id LIMIT 1) as receta_notas,
                   (SELECT archivo_ruta FROM hc_archivos WHERE hc_id = hc.id LIMIT 1) as archivo_ruta
            FROM historia_clinica hc
            WHERE hc.paciente_id = :paciente_id 
            ORDER BY hc.fecha_registro ASC
        ");
        $stmt->bindParam(':paciente_id', $paciente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($paciente_id, $medico_id, $diagnostico, $receta_notas, $archivo_ruta, $medicamentos = []) {
        try {
            $this->conn->beginTransaction();

            $stmtHC = $this->conn->prepare("INSERT INTO historia_clinica (paciente_id, medico_id) VALUES (:paciente_id, :medico_id)");
            $stmtHC->bindParam(':paciente_id', $paciente_id);
            $stmtHC->bindParam(':medico_id', $medico_id);
            $stmtHC->execute();
            
            $hc_id = $this->conn->lastInsertId();

            if (!empty(trim($diagnostico))) {
                $stmtDiag = $this->conn->prepare("INSERT INTO hc_diagnosticos (hc_id, diagnostico) VALUES (:hc_id, :diagnostico)");
                $stmtDiag->bindParam(':hc_id', $hc_id);
                $stmtDiag->bindParam(':diagnostico', $diagnostico);
                $stmtDiag->execute();
            }

            if (!empty(trim($receta_notas)) || !empty($medicamentos)) {
                $stmtRec = $this->conn->prepare("INSERT INTO recetas (hc_id, indicaciones_generales) VALUES (:hc_id, :indicaciones_generales)");
                $stmtRec->bindParam(':hc_id', $hc_id);
                $stmtRec->bindParam(':indicaciones_generales', $receta_notas);
                $stmtRec->execute();
                
                $receta_id = $this->conn->lastInsertId();
                
                if(!empty($medicamentos)){
                    $stmtMed = $this->conn->prepare("INSERT INTO receta_medicamentos (receta_id, medicamento_id, dosis, frecuencia, duracion_dias) VALUES (:receta_id, :medicamento_id, :dosis, :frecuencia, :duracion)");
                    foreach($medicamentos as $med) {
                        // $med es ['id' => X, 'dosis' => Y, 'frecuencia' => Z, 'duracion' => W]
                        if(!empty($med['id'])) {
                            $stmtMed->bindParam(':receta_id', $receta_id);
                            $stmtMed->bindParam(':medicamento_id', $med['id']);
                            $stmtMed->bindParam(':dosis', $med['dosis']);
                            $stmtMed->bindParam(':frecuencia', $med['frecuencia']);
                            $stmtMed->bindParam(':duracion', $med['duracion']);
                            $stmtMed->execute();
                        }
                    }
                }
            }

            if (!empty($archivo_ruta)) {
                $stmtArch = $this->conn->prepare("INSERT INTO hc_archivos (hc_id, archivo_ruta) VALUES (:hc_id, :archivo_ruta)");
                $stmtArch->bindParam(':hc_id', $hc_id);
                $stmtArch->bindParam(':archivo_ruta', $archivo_ruta);
                $stmtArch->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
?>
