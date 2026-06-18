<?php
/**
 * Helpers.php — Funciones globales de utilidad para Caja CordesSIS.
 *
 * Incluye:
 *  - log_activity()   : Registro de auditoría en la tabla `auditoria` de BD
 *  - app_log()        : Wrapper de AppLogger para uso rápido en cualquier parte del sistema
 */

/**
 * Registra una acción de auditoría en la tabla `auditoria` de la base de datos
 * Y además deja trazabilidad en el archivo de log del canal 'application'.
 *
 * @param int|null $usuario_id  ID del usuario que ejecuta la acción
 * @param string   $accion      Descripción de la acción realizada
 * @param string   $tabla       Tabla o módulo afectado
 */
function log_activity(?int $usuario_id, string $accion, string $tabla): void {
    // 1. Registro en base de datos (auditoría)
    try {
        $conn = Database::getInstance();
        $stmt = $conn->prepare(
            "INSERT INTO auditoria (usuario_id, accion, tabla) VALUES (:usuario_id, :accion, :tabla)"
        );
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':accion',     $accion);
        $stmt->bindParam(':tabla',      $tabla);
        $stmt->execute();
    } catch (Exception $e) {
        // Si falla la BD, al menos lo dejamos en el log de archivo
        AppLogger::error(
            "Error al registrar auditoría en BD: {$e->getMessage()}",
            ['accion' => $accion, 'tabla' => $tabla, 'usuario_id' => $usuario_id],
            'database'
        );
        return;
    }

    // 2. Registro en archivo de log
    AppLogger::info(
        "Auditoría: [{$tabla}] {$accion}",
        ['usuario_id' => $usuario_id, 'modulo' => $tabla],
        'application'
    );
}

/**
 * Wrapper de AppLogger para registro rápido desde cualquier parte del sistema.
 * 
 * @param string $level   Nivel: 'info', 'warning', 'error', 'critical', 'debug'
 * @param string $message Mensaje a registrar
 * @param array  $context Datos adicionales opcionales
 * @param string $channel Canal: 'application' (default), 'security', 'database'
 */
function app_log(string $level, string $message, array $context = [], string $channel = 'application'): void {
    $levelUpper = strtoupper($level);
    AppLogger::log($levelUpper, $message, $context, $channel);
}
?>
