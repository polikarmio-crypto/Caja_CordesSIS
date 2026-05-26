<?php
class User {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function login($email, $password) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        // Check if blocked
        if ($this->isBlocked($user)) {
            return 'blocked';
        }

        if (password_verify($password, $user['password_hash'])) {
            // Reset attempts on successful login
            $this->resetFailedAttempts($user['id']);
            return $user;
        }

        // Increment attempts on failed login
        $this->incrementFailedAttempts($user['id']);
        return false;
    }

    public function isBlocked($user) {
        if ($user['bloqueado_hasta']) {
            $blockedUntil = new DateTime($user['bloqueado_hasta']);
            $now = new DateTime();
            if ($blockedUntil > $now) {
                return true;
            }
        }
        return false;
    }

    public function incrementFailedAttempts($userId) {
        // Find current attempts
        $stmt = $this->conn->prepare("SELECT intentos_fallidos FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch();
        
        $attempts = ($user['intentos_fallidos'] ?? 0) + 1;
        
        if ($attempts >= 3) {
            // Lock account for 10 minutes in PostgreSQL syntax
            $stmt = $this->conn->prepare("UPDATE usuarios SET intentos_fallidos = :attempts, bloqueado_hasta = NOW() + INTERVAL '10 minutes' WHERE id = :id");
        } else {
            $stmt = $this->conn->prepare("UPDATE usuarios SET intentos_fallidos = :attempts WHERE id = :id");
        }
        $stmt->bindParam(':attempts', $attempts, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function resetFailedAttempts($userId) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function generateResetToken($email) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        // Set token valid for 1 hour
        $stmt = $this->conn->prepare("UPDATE usuarios SET reset_token = :token, reset_token_expira = NOW() + INTERVAL '1 hour' WHERE id = :id");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();

        return $token;
    }

    public function verifyResetToken($token) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE reset_token = :token AND reset_token_expira > NOW() LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updatePassword($userId, $newPasswordHash) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET password_hash = :hash, reset_token = NULL, reset_token_expira = NULL, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id");
        $stmt->bindParam(':hash', $newPasswordHash);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    public function generate2FACode($userId) {
        $code = sprintf("%06d", mt_rand(0, 999999));
        // Set valid for 5 minutes
        $stmt = $this->conn->prepare("UPDATE usuarios SET two_factor_code = :code, two_factor_expira = NOW() + INTERVAL '5 minutes' WHERE id = :id");
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $code;
    }

    public function verify2FACode($userId, $code) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = :id AND two_factor_code = :code AND two_factor_expira > NOW() LIMIT 1");
        $stmt->bindParam(':id', $userId);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            // Clear code
            $stmt2 = $this->conn->prepare("UPDATE usuarios SET two_factor_code = NULL, two_factor_expira = NULL WHERE id = :id");
            $stmt2->bindParam(':id', $userId);
            $stmt2->execute();
            return true;
        }
        return false;
    }
}
?>
