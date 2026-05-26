<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        // Cargar variables desde el archivo .env si existe
        $host = '127.0.0.1';
        $db_name = 'caja_cordes';
        $username = 'postgres';
        $password = '';

        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === 'DB_HOST') $host = $value;
                    if ($name === 'DB_NAME') $db_name = $value;
                    if ($name === 'DB_USER') $username = $value;
                    if ($name === 'DB_PASS') $password = $value;
                }
            }
        }

        try {
            $this->conn = new PDO(
                "pgsql:host=" . $host . ";dbname=" . $db_name, 
                $username, 
                $password,
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            $this->conn->exec("SET client_encoding TO 'UTF8';");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
            exit();
        }
    }

    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
?>
