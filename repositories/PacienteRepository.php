<?php
/**
 * PacienteRepository — Centraliza consultas SQL para el módulo de Pacientes.
 */
class PacienteRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Busca pacientes por nombre, apellido o CI (para AJAX).
     */
    public function search(string $query): array {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.ci, p.nombres, p.apellidos,
                   STRING_AGG(pt.telefono, ', ') as telefono
              FROM pacientes p
              LEFT JOIN paciente_telefonos pt ON p.id = pt.paciente_id
             WHERE p.nombres LIKE :q OR p.apellidos LIKE :q OR p.ci LIKE :q
             GROUP BY p.id, p.ci, p.nombres, p.apellidos
             LIMIT 10
        ");
        $term = "%" . $query . "%";
        $stmt->bindParam(':q', $term);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el ID del rol Paciente dinámicamente.
     */
    public function findRolIdPaciente(): int {
        $stmt = $this->conn->prepare("SELECT id FROM roles WHERE nombre = 'Paciente' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : 2;
    }

    /**
     * Inserta un nuevo usuario (para crear paciente).
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
     * Obtiene paciente por usuario_id (para paciente que edita su propio perfil).
     */
    public function findPacienteByUserId(int $userId): array|false {
        $stmt = $this->conn->prepare(
            "SELECT p.* FROM pacientes p JOIN usuarios u ON p.usuario_id = u.id WHERE u.id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene paciente por ID (para admin que edita).
     */
    public function findPacienteById(int $id): array|false {
        $stmt = $this->conn->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza datos del paciente.
     */
    public function updatePaciente(int $pacienteId, string $nombres, string $apellidos, string $ci, ?string $fechaNac): bool {
        $stmt = $this->conn->prepare("
            UPDATE pacientes
               SET nombres = :nombres, apellidos = :apellidos, ci = :ci, fecha_nac = :fn
             WHERE id = :id
        ");
        return $stmt->execute([
            ':nombres'   => $nombres,
            ':apellidos' => $apellidos,
            ':ci'        => $ci,
            ':fn'        => $fechaNac,
            ':id'        => $pacienteId,
        ]);
    }

    /**
     * Elimina y reinserta el teléfono principal de un paciente.
     */
    public function replaceTelefono(int $pacienteId, string $telefono): void {
        $this->conn->prepare("DELETE FROM paciente_telefonos WHERE paciente_id = :pid")
             ->execute([':pid' => $pacienteId]);
        $stmt = $this->conn->prepare(
            "INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:pid, :tel)"
        );
        $stmt->execute([':pid' => $pacienteId, ':tel' => $telefono]);
    }

    /**
     * Obtiene el teléfono principal de un paciente.
     */
    public function findTelefonoPrincipal(int $pacienteId): string {
        $stmt = $this->conn->prepare(
            "SELECT telefono FROM paciente_telefonos WHERE paciente_id = :pid LIMIT 1"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchColumn() ?: '';
    }
}
?>
