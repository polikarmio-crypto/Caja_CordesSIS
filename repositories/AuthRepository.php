<?php
/**
 * AuthRepository — Centraliza todas las consultas SQL relacionadas con autenticación y perfil.
 * Los controllers no deben contener SQL directo; deben llamar a este repositorio.
 */
class AuthRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Obtiene usuario con su rol dado un ID.
     */
    public function findUserWithRoleById(int $userId): array|false {
        $stmt = $this->conn->prepare(
            "SELECT u.*, r.nombre as rol_nombre
               FROM usuarios u
               JOIN roles r ON u.rol_id = r.id
              WHERE u.id = :id"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las especialidades de un médico dado su medico_id.
     */
    public function findEspecialidadesByMedicoId(int $medicoId): array {
        $stmt = $this->conn->prepare(
            "SELECT e.nombre, e.parent_id, p.nombre as parent_nombre
               FROM medico_especialidades me
               JOIN especialidades e ON me.especialidad_id = e.id
               LEFT JOIN especialidades p ON e.parent_id = p.id
              WHERE me.medico_id = :mid"
        );
        $stmt->execute([':mid' => $medicoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene horarios activos de un médico.
     */
    public function findHorariosByMedicoId(int $medicoId): array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM horarios_medicos
              WHERE medico_id = :mid AND activo = TRUE
              ORDER BY id"
        );
        $stmt->execute([':mid' => $medicoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el médico asociado a un usuario.
     */
    public function findMedicoByUserId(int $userId): array|false {
        $stmt = $this->conn->prepare(
            "SELECT * FROM medicos WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el paciente asociado a un usuario.
     */
    public function findPacienteByUserId(int $userId): array|false {
        $stmt = $this->conn->prepare(
            "SELECT * FROM pacientes WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los teléfonos de un paciente.
     */
    public function findTelefonosByPacienteId(int $pacienteId): array {
        $stmt = $this->conn->prepare(
            "SELECT telefono FROM paciente_telefonos WHERE paciente_id = :pid"
        );
        $stmt->execute([':pid' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Actualiza datos del usuario (con contraseña).
     */
    public function updateUserWithPassword(int $userId, string $nombres, string $apellidos, string $email, string $hash, string $avatar): bool {
        $stmt = $this->conn->prepare(
            "UPDATE usuarios
                SET nombres = :nom, apellidos = :ape, email = :email,
                    password_hash = :hash, foto_perfil = :avatar
              WHERE id = :id"
        );
        return $stmt->execute([
            ':nom'    => $nombres,
            ':ape'    => $apellidos,
            ':email'  => $email,
            ':hash'   => $hash,
            ':avatar' => $avatar,
            ':id'     => $userId
        ]);
    }

    /**
     * Actualiza datos del usuario (sin contraseña).
     */
    public function updateUserWithoutPassword(int $userId, string $nombres, string $apellidos, string $email, string $avatar): bool {
        $stmt = $this->conn->prepare(
            "UPDATE usuarios
                SET nombres = :nom, apellidos = :ape, email = :email, foto_perfil = :avatar
              WHERE id = :id"
        );
        return $stmt->execute([
            ':nom'    => $nombres,
            ':ape'    => $apellidos,
            ':email'  => $email,
            ':avatar' => $avatar,
            ':id'     => $userId
        ]);
    }

    /**
     * Obtiene info de usuario+paciente para determinar si es Paciente activo.
     */
    public function findUserPacienteInfo(int $userId): array|false {
        $stmt = $this->conn->prepare(
            "SELECT p.id, u.rol_id, r.nombre as rol_nombre
               FROM usuarios u
               JOIN roles r ON u.rol_id = r.id
               LEFT JOIN pacientes p ON p.usuario_id = u.id
              WHERE u.id = :id"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza datos del paciente.
     */
    public function updatePaciente(int $pacienteId, string $nombres, string $apellidos, ?string $fechaNac): bool {
        $stmt = $this->conn->prepare(
            "UPDATE pacientes
                SET nombres = :nom, apellidos = :ape, fecha_nac = :fn
              WHERE id = :id"
        );
        return $stmt->execute([
            ':nom' => $nombres,
            ':ape' => $apellidos,
            ':fn'  => $fechaNac,
            ':id'  => $pacienteId
        ]);
    }

    /**
     * Elimina y reinserta teléfono del paciente.
     */
    public function replaceTelefonoPaciente(int $pacienteId, string $telefono): void {
        $this->conn->prepare("DELETE FROM paciente_telefonos WHERE paciente_id = :pid")
             ->execute([':pid' => $pacienteId]);
        $stmt = $this->conn->prepare(
            "INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:pid, :tel)"
        );
        $stmt->execute([':pid' => $pacienteId, ':tel' => $telefono]);
    }

    /**
     * Verifica la contraseña hash de un usuario.
     */
    public function findPasswordHashByUserId(int $userId): string|false {
        $stmt = $this->conn->prepare(
            "SELECT password_hash FROM usuarios WHERE id = :id"
        );
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchColumn();
    }
}
?>
