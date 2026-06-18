<?php
require_once __DIR__ . '/../core/BackupManager.php';

/**
 * BackupController — Gestiona la interfaz web para backups de la BD.
 * Solo accesible para roles Administrativo y Directivo.
 */
class BackupController {

    private function checkAccess(): void {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol_nombre'] ?? '', ['Administrativo', 'Directivo'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Panel principal de backups: lista los backups existentes.
     */
    public function index(): void {
        $this->checkAccess();

        $manager = new BackupManager();
        $backups = $manager->list();
        $success = $_GET['success'] ?? '';
        $error   = $_GET['error']   ?? '';

        require_once __DIR__ . '/../views/backups/index.php';
    }

    /**
     * Genera un backup manual al instante (llamado por botón de demo).
     */
    public function manual(): void {
        $this->checkAccess();

        $manager = new BackupManager();
        $result  = $manager->generate();

        if ($result['success']) {
            AppLogger::info(
                "Backup manual generado por usuario",
                ['filename' => $result['filename'], 'usuario_id' => $_SESSION['user_id'] ?? null],
                'database'
            );
            log_activity($_SESSION['user_id'] ?? null, 'Backup manual de BD generado', 'sistema');
            header('Location: ' . BASE_URL . '/backups?success=' . urlencode($result['message']));
        } else {
            AppLogger::error(
                "Fallo al generar backup manual",
                ['error' => $result['message'], 'usuario_id' => $_SESSION['user_id'] ?? null],
                'database'
            );
            header('Location: ' . BASE_URL . '/backups?error=' . urlencode($result['message']));
        }
        exit;
    }

    /**
     * Descarga un archivo de backup.
     */
    public function download(): void {
        $this->checkAccess();

        $filename = $_GET['file'] ?? '';
        $manager  = new BackupManager();
        $filepath = $manager->getFilePath($filename);

        if (!$filepath) {
            header('Location: ' . BASE_URL . '/backups?error=' . urlencode('Archivo no encontrado o nombre inválido.'));
            exit;
        }

        AppLogger::info(
            "Backup descargado: {$filename}",
            ['usuario_id' => $_SESSION['user_id'] ?? null],
            'database'
        );

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}
?>
