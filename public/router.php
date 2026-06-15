<?php
/**
 * Router para el servidor de desarrollo built-in de PHP (php -S).
 * Lanzar SIEMPRE desde la carpeta public/:
 *   cd /ruta/al/proyecto/public
 *   php -S localhost:8000 router.php
 *
 * Sirve archivos estáticos (CSS, JS, imágenes) directamente
 * y envía el resto a index.php para que el enrutador MVC los procese.
 */

if (php_sapi_name() === 'cli-server') {
    // Construir la ruta física del archivo solicitado
    $requestUri = $_SERVER['REQUEST_URI'];

    // Quitar la query string para obtener solo el path
    if (($pos = strpos($requestUri, '?')) !== false) {
        $requestUri = substr($requestUri, 0, $pos);
    }

    $filePath = __DIR__ . $requestUri;

    // Si el archivo físico existe (CSS, JS, img, fuentes, etc.), servirlo directamente
    if (is_file($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'woff2' => 'font/woff2',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon'
        ];
        
        if (isset($mimes[$ext])) {
            header("Content-Type: " . $mimes[$ext]);
        }
        
        header("Cache-Control: public, max-age=31536000, immutable");
        readfile($filePath);
        exit;
    }
}

// Todo lo demás pasa por el enrutador MVC
require_once __DIR__ . '/index.php';
return true;
