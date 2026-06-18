<?php
/**
 * MedicoRepository — Centraliza consultas SQL para el módulo de Médicos.
 */
class MedicoRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Obtiene el ID del rol Médico dinámicamente.
     */
    public function findRolIdMedico(): int {
        $stmt = $this->conn->prepare("SELECT id FROM roles WHERE nombre = 'Médico' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : 3;
    }

    /**
     * Inserta un nuevo usuario para el médico.
     */
    public function insertUsuario(int $rolId, string $email, string $passwordHash): int {
        $stmt = $this->conn->prepare(
            "INSERT INTO usuarios (rol_id, email, password_hash) VALUES (:rol_id, :email, :password_hash)"
        );
        $stmt->bindParam(':rol_id',        $rolId,        PDO::PARAM_INT);
        $stmt->bindParam(':email',         $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->execute();
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Obtiene todas las especialidades ordenadas.
     */
    public function findAllEspecialidades(): array {
        return $this->conn->query(
            "SELECT * FROM especialidades ORDER BY nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
