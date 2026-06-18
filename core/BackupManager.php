<?php
/**
 * BackupManager — Gestiona copias de seguridad de la base de datos PostgreSQL.
 * Utiliza pg_dump para generar dumps SQL completos.
 */
class BackupManager {

    /** Directorio donde se guardan los backups */
    private string $backupDir;

    /** Configuración de la BD leída del .env */
    private array $dbConfig;

    public function __construct() {
        $this->backupDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
        $this->ensureBackupDir();
        $this->dbConfig = $this->loadDbConfig();
    }

    /**
     * Genera un backup completo de la BD.
     * Retorna ['success' => bool, 'filename' => string, 'message' => string]
     */
    public function generate(): array {
        $timestamp = date('Ymd_His');
        $filename  = "backup_{$this->dbConfig['db_name']}_{$timestamp}.sql";
        $filepath  = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        // Buscar pg_dump en rutas típicas de Windows/Linux
        $pgDump = $this->findPgDump();
        if (!$pgDump) {
            AppLogger::error('pg_dump no encontrado en el sistema', [], 'database');
            return [
                'success'  => false,
                'filename' => '',
                'message'  => 'No se encontró pg_dump. Asegúrese de que PostgreSQL está instalado correctamente.'
            ];
        }

        // Setear PGPASSWORD para evitar prompt de contraseña
        $env = '';
        if (!empty($this->dbConfig['password'])) {
            if (PHP_OS_FAMILY === 'Windows') {
                $env = 'set PGPASSWORD=' . escapeshellarg($this->dbConfig['password']) . ' && ';
            } else {
                $env = 'PGPASSWORD=' . escapeshellarg($this->dbConfig['password']) . ' ';
            }
        }

        $cmd = $env . escapeshellarg($pgDump)
             . ' -h ' . escapeshellarg($this->dbConfig['host'])
             . ' -U ' . escapeshellarg($this->dbConfig['username'])
             . ' -d ' . escapeshellarg($this->dbConfig['db_name'])
             . ' --no-password'
             . ' -f ' . escapeshellarg($filepath)
             . ' 2>&1';

        $output     = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            AppLogger::info("Backup generado exitosamente: {$filename}", [
                'size' => filesize($filepath),
                'file' => $filename
            ], 'database');

            // Limpiar backups antiguos (>30 días)
            $this->cleanupOldBackups();

            return [
                'success'  => true,
                'filename' => $filename,
                'message'  => "Backup generado exitosamente: {$filename}"
            ];
        }

        $errorMsg = implode(' | ', $output);
        AppLogger::error("Error al generar backup: {$errorMsg}", ['cmd_output' => $errorMsg], 'database');
        return [
            'success'  => false,
            'filename' => '',
            'message'  => "Error al generar backup: {$errorMsg}"
        ];
    }

    /**
     * Lista todos los backups disponibles ordenados por fecha (más reciente primero).
     */
    public function list(): array {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql');
        if (!$files) return [];

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename'  => basename($file),
                'size'      => $this->formatBytes(filesize($file)),
                'size_raw'  => filesize($file),
                'created'   => date('d/m/Y H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
            ];
        }

        // Más reciente primero
        usort($backups, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        return $backups;
    }

    /**
     * Obtiene la ruta completa de un backup dado su nombre de archivo.
     * Valida que el archivo esté dentro del directorio de backups (seguridad).
     */
    public function getFilePath(string $filename): string|false {
        // Sanitizar: solo letras, números, guión bajo, guión y punto
        if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename)) {
            return false;
        }
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        return (file_exists($path) && is_file($path)) ? $path : false;
    }

    // ── Métodos privados ──────────────────────────────────────────────────────

    private function ensureBackupDir(): void {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        // Proteger directorio de acceso web directo
        $htaccess = $this->backupDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    private function loadDbConfig(): array {
        $host     = '127.0.0.1';
        $db_name  = 'caja_cordes';
        $username = 'postgres';
        $password = '';

        $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name); $value = trim($value);
                    if ($name === 'DB_HOST') $host     = $value;
                    if ($name === 'DB_NAME') $db_name  = $value;
                    if ($name === 'DB_USER') $username = $value;
                    if ($name === 'DB_PASS') $password = $value;
                }
            }
        }
        return compact('host', 'db_name', 'username', 'password');
    }

    /**
     * Busca el ejecutable pg_dump en rutas conocidas.
     */
    private function findPgDump(): string|false {
        $candidates = [
            // Windows — versiones comunes de PostgreSQL
            'C:\\Program Files\\PostgreSQL\\17\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\16\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\15\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\14\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\13\\bin\\pg_dump.exe',
            // Linux / macOS
            '/usr/bin/pg_dump',
            '/usr/local/bin/pg_dump',
            '/opt/homebrew/bin/pg_dump',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        // Último recurso: buscar en PATH del sistema
        $which = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump' : 'which pg_dump';
        $result = trim(shell_exec($which) ?? '');
        return ($result !== '') ? $result : false;
    }

    /**
     * Elimina backups con más de 30 días.
     */
    private function cleanupOldBackups(): void {
        $limit = time() - (30 * 24 * 3600);
        foreach (glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql') as $file) {
            if (filemtime($file) < $limit) {
                @unlink($file);
                AppLogger::info("Backup antiguo eliminado: " . basename($file), [], 'database');
            }
        }
    }

    private function formatBytes(int $bytes): string {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024,    2) . ' KB';
        return $bytes . ' B';
    }
}
?>
