<?php
function log_activity($usuario_id, $accion, $tabla) {
    try {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("INSERT INTO auditoria (usuario_id, accion, tabla) VALUES (:usuario_id, :accion, :tabla)");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':accion', $accion);
        $stmt->bindParam(':tabla', $tabla);
        $stmt->execute();
    } catch (Exception $e) {
        // En producción, esto debería ir a un archivo log de errores
        error_log("Error al registrar auditoría: " . $e->getMessage());
    }
}
?>
