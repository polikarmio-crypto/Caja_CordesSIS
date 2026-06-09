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

    // RF-011 / RF-106: Editar perfil del paciente
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $conn = Database::getInstance();
        $user_id = $_SESSION['user_id'];
        $rol = $_SESSION['rol_nombre'] ?? '';

        // El paciente edita su propio perfil; el admin puede editar el de otro pasando ?id=
        if ($rol === 'Paciente') {
            $stmt = $conn->prepare("SELECT p.* FROM pacientes p JOIN usuarios u ON p.usuario_id = u.id WHERE u.id = :uid LIMIT 1");
            $stmt->execute([':uid' => $user_id]);
        } elseif (in_array($rol, ['Administrativo', 'Directivo'])) {
            $target_id = intval($_GET['id'] ?? $_POST['paciente_id'] ?? 0);
            $stmt = $conn->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $target_id]);
        } else {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$paciente) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombres   = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $ci        = trim($_POST['ci'] ?? '');
            $fecha_nac = $_POST['fecha_nac'] ?? '';
            $telefono  = trim($_POST['telefono'] ?? '');

            try {
                $conn->beginTransaction();

                // Actualizar datos en tabla pacientes
                $stmtUpd = $conn->prepare("
                    UPDATE pacientes
                       SET nombres = :nombres, apellidos = :apellidos, ci = :ci, fecha_nacimiento = :fn
                     WHERE id = :id
                ");
                $stmtUpd->execute([
                    ':nombres'   => $nombres,
                    ':apellidos' => $apellidos,
                    ':ci'        => $ci,
                    ':fn'        => $fecha_nac ?: null,
                    ':id'        => $paciente['id'],
                ]);

                // Actualizar teléfono principal (borra y reinserta el primero)
                if (!empty($telefono)) {
                    $conn->prepare("DELETE FROM paciente_telefonos WHERE paciente_id = :pid")->execute([':pid' => $paciente['id']]);
                    $stmtTel = $conn->prepare("INSERT INTO paciente_telefonos (paciente_id, telefono) VALUES (:pid, :tel)");
                    $stmtTel->execute([':pid' => $paciente['id'], ':tel' => $telefono]);
                }

                log_activity($user_id, 'Actualizar Perfil Paciente', 'pacientes');
                $conn->commit();

                $success = 'Perfil actualizado correctamente.';
                // Recargar datos actualizados
                $stmt2 = $conn->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
                $stmt2->execute([':id' => $paciente['id']]);
                $paciente = $stmt2->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Error al actualizar: ' . $e->getMessage();
            }
        }

        // Obtener teléfono actual para prellenado
        $stmtTel = $conn->prepare("SELECT telefono FROM paciente_telefonos WHERE paciente_id = :pid LIMIT 1");
        $stmtTel->execute([':pid' => $paciente['id']]);
        $telefono_actual = $stmtTel->fetchColumn() ?: '';

        require_once __DIR__ . '/../views/pacientes/edit.php';
    }
}
?>
