<?php
/**
 * AppLogger — Sistema de logging centralizado para Caja CordesSIS.
 * 
 * Implementación nativa PSR-3 compatible (sin dependencias externas).
 * Genera archivos de log rotados diariamente en el directorio /logs/.
 *
 * Canales disponibles:
 *   - application : eventos generales del sistema
 *   - security    : autenticación, 2FA, bloqueos de cuenta
 *   - database    : errores y operaciones críticas de BD
 *
 * Niveles (orden de criticidad):
 *   DEBUG < INFO < NOTICE < WARNING < ERROR < CRITICAL < ALERT < EMERGENCY
 */
class AppLogger {

    const DEBUG     = 'DEBUG';
    const INFO      = 'INFO';
    const NOTICE    = 'NOTICE';
    const WARNING   = 'WARNING';
    const ERROR     = 'ERROR';
    const CRITICAL  = 'CRITICAL';
    const ALERT     = 'ALERT';
    const EMERGENCY = 'EMERGENCY';

    /** Directorio donde se guardan los logs */
    private static string $logDir = '';

    /**
     * Inicializa el directorio de logs (se llama automáticamente al primer uso).
     */
    private static function init(): void {
        if (self::$logDir !== '') return;

        // Subir dos niveles desde /core/ → raíz del proyecto → /logs/
        self::$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        // Crear .htaccess para proteger los logs de acceso directo
        $htaccess = self::$logDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    /**
     * Escribe un mensaje en el canal y nivel especificados.
     *
     * @param string $level   Nivel (use las constantes de esta clase)
     * @param string $message Mensaje descriptivo
     * @param array  $context Datos adicionales (ej: ['user_id' => 5])
     * @param string $channel Canal de log: 'application', 'security', 'database'
     */
    public static function log(string $level, string $message, array $context = [], string $channel = 'application'): void {
        self::init();

        $date     = date('Y-m-d');
        $time     = date('Y-m-d H:i:s');
        $filename = self::$logDir . DIRECTORY_SEPARATOR . "{$channel}_{$date}.log";

        // Interpolar contexto en el mensaje (estilo PSR-3)
        $interpolated = self::interpolate($message, $context);

        // Agregar IP y usuario de sesión al contexto si están disponibles
        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userId = $_SESSION['user_id'] ?? 'guest';
        $extra  = empty($context) ? '' : ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);

        $line = "[{$time}] [{$level}] [{$channel}] [user:{$userId}] [ip:{$ip}] {$interpolated}{$extra}" . PHP_EOL;

        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);

        // Limpiar logs antiguos (más de 30 días) periódicamente (1% de probabilidad)
        if (rand(1, 100) === 1) {
            self::cleanup();
        }
    }

    // ── Métodos de conveniencia por nivel ─────────────────────────────────────

    public static function debug(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::DEBUG, $message, $context, $channel);
    }

    public static function info(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::INFO, $message, $context, $channel);
    }

    public static function notice(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::NOTICE, $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::WARNING, $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::ERROR, $message, $context, $channel);
    }

    public static function critical(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::CRITICAL, $message, $context, $channel);
    }

    public static function alert(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::ALERT, $message, $context, $channel);
    }

    public static function emergency(string $message, array $context = [], string $channel = 'application'): void {
        self::log(self::EMERGENCY, $message, $context, $channel);
    }

    // ── Métodos de canal específicos ──────────────────────────────────────────

    /** Log de seguridad (auth, 2FA, bloqueos) */
    public static function security(string $level, string $message, array $context = []): void {
        self::log($level, $message, $context, 'security');
    }

    /** Log de base de datos */
    public static function database(string $level, string $message, array $context = []): void {
        self::log($level, $message, $context, 'database');
    }

    // ── Helpers internos ──────────────────────────────────────────────────────

    /**
     * Interpola variables {key} del contexto en el mensaje (PSR-3 §1.2).
     */
    private static function interpolate(string $message, array $context): string {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * Elimina logs con más de 30 días de antigüedad.
     */
    private static function cleanup(): void {
        self::init();
        $limit = time() - (30 * 24 * 3600);
        foreach (glob(self::$logDir . DIRECTORY_SEPARATOR . '*.log') as $file) {
            if (filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }

    /**
     * Obtiene los últimos N registros del canal especificado (para vistas de admin).
     */
    public static function getRecentLines(string $channel = 'application', int $lines = 50): array {
        self::init();
        $filename = self::$logDir . DIRECTORY_SEPARATOR . "{$channel}_" . date('Y-m-d') . ".log";
        if (!file_exists($filename)) return [];

        $all = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_reverse(array_slice($all, -$lines));
    }
}
?>
