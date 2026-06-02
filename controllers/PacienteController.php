<?php
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/User.php';

class PacienteController {
    public function index() {
        $pacienteModel = new Paciente();
        $pacientes = $pacienteModel->findAll();
        require_once __DIR__ . '/../views/pacientes/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombres = $_POST['nombres'] ?? '';
            $apellidos = $_POST['apellidos'] ?? '';
            $ci = $_POST['ci'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $fecha_nac = $_POST['fecha_nac'] ?? '';
            $telefono = $_POST['telefono'] ?? '';

            $conn = Database::getInstance();
            $conn->beginTransaction();

            try {
                // Obtener ID del rol 'Paciente' de forma dinámica para evitar asignaciones erróneas
                $stmtRol = $conn->prepare("SELECT id FROM roles WHERE nombre = 'Paciente' LIMIT 1");
                $stmtRol->execute();
                $rolData = $stmtRol->fetch();
                $rolIdPaciente = $rolData ? $rolData['id'] : 2;

                $stmtUser = $conn->prepare("INSERT INTO usuarios (rol_id, email, password_hash) VALUES (:rol_id, :email, :password_hash)");
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser->bindParam(':rol_id', $rolIdPaciente, PDO::PARAM_INT);
                $stmtUser->bindParam(':email', $email);
                $stmtUser->bindParam(':password_hash', $hash);
                $stmtUser->execute();
                
                $usuario_id = $conn->lastInsertId();

                // Insert Paciente
                $pacienteModel = new Paciente();
                $telefonos_array = !empty($telefono) ? [$telefono] : [];
                $pacienteModel->create($usuario_id, $ci, $nombres, $apellidos, $fecha_nac, $telefonos_array);

                log_activity($usuario_id, 'Crear Paciente', 'pacientes');

                $conn->commit();
                header('Location: ' . BASE_URL . '/pacientes?success=1');
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Error al crear paciente: " . $e->getMessage();
                require_once __DIR__ . '/../views/pacientes/create.php';
            }
        } else {
            require_once __DIR__ . '/../views/pacientes/create.php';
        }
    }

    public function search() {
        header('Content-Type: application/json');
        $query = $_GET['q'] ?? '';
        
        $conn = Database::getInstance();
        $stmt = $conn->prepare("
            SELECT p.id, p.ci, p.nombres, p.apellidos, STRING_AGG(pt.telefono, ', ') as telefono
            FROM pacientes p
            LEFT JOIN paciente_telefonos pt ON p.id = pt.paciente_id
            WHERE p.nombres LIKE :q OR p.apellidos LIKE :q OR p.ci LIKE :q
            GROUP BY p.id, p.ci, p.nombres, p.apellidos
            LIMIT 10
        ");
        $term = "%" . $query . "%";
        $stmt->bindParam(':q', $term);
        $stmt->execute();
        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }
}
?>
