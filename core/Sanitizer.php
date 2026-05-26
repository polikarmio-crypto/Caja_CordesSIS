<?php
class Sanitizer {
    public static function escapeHtml($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = self::escapeHtml($data);
        }
        return $data;
    }

    public static function sanitizeSuperglobals() {
        $_POST = self::sanitizeInput($_POST);
        $_GET = self::sanitizeInput($_GET);
        $_REQUEST = self::sanitizeInput($_REQUEST);
        // Note: $_COOKIE usually doesn't need global escaping in this way, but should be used carefully
    }
}
?>
