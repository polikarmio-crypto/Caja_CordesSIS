<?php
session_start();

// BASE_URL: detecta automáticamente si corre con Apache (subdirectorio) o php -S
if (php_sapi_name() === 'cli-server') {
    // Servidor de desarrollo PHP: siempre en la raíz
    define('BASE_URL', '');
} else {
    // Apache / Nginx: calcular el prefijo de ruta si está en subdirectorio
    $baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    define('BASE_URL', rtrim($baseDir, '/'));
}

require_once '../config/Database.php';
require_once '../core/Helpers.php';
require_once '../core/Router.php';

// Estructuras de Datos Personalizadas
require_once '../core/structures/CustomNode.php';
require_once '../core/structures/CustomQueue.php';
require_once '../core/structures/CustomDoublyLinkedList.php';

// Models
require_once '../models/User.php';
require_once '../models/Paciente.php';
require_once '../models/Cita.php';
require_once '../models/HistoriaClinica.php';
require_once '../models/HorarioMedico.php';
require_once '../models/Hospitalizacion.php';
require_once '../models/Laboratorio.php';
require_once '../models/Medicamento.php';
require_once '../models/AusenciaMedico.php';

// Controllers
require_once '../controllers/AuthController.php';
require_once '../controllers/DashboardController.php';
require_once '../controllers/PacienteController.php';
require_once '../controllers/CitaController.php';
require_once '../controllers/HistoriaClinicaController.php';
require_once '../controllers/ReporteController.php';
require_once '../controllers/HorarioMedicoController.php';
require_once '../controllers/HospitalizacionController.php';
require_once '../controllers/LaboratorioController.php';
require_once '../controllers/FarmaciaController.php';
require_once '../controllers/FacturacionController.php';

$router = new Router();

// Auth & Password Reset (Sprint 7 - RF-039)
$router->add('GET', '/', ['AuthController', 'showLogin']);
$router->add('POST', '/login', ['AuthController', 'login']);
$router->add('GET', '/logout', ['AuthController', 'logout']);
$router->add('GET', '/password/reset', ['AuthController', 'showReset']);
$router->add('POST', '/password/reset', ['AuthController', 'sendResetLink']);
$router->add('GET', '/password/change', ['AuthController', 'showChangePassword']);
$router->add('POST', '/password/change', ['AuthController', 'changePassword']);
$router->add('GET', '/login/2fa', ['AuthController', 'show2FA']);
$router->add('POST', '/login/2fa', ['AuthController', 'verify2FA']);

// Dashboard
$router->add('GET', '/dashboard', ['DashboardController', 'index']);
$router->add('GET', '/api/dashboard/stats', ['DashboardController', 'stats']);
$router->add('GET', '/dashboard/export_pdf', ['DashboardController', 'exportPdf']);
$router->add('GET', '/dashboard/export_csv', ['DashboardController', 'exportCsv']);

// Pacientes
$router->add('GET', '/pacientes', ['PacienteController', 'index']);
$router->add('GET', '/pacientes/search', ['PacienteController', 'search']);
$router->add('GET', '/pacientes/create', ['PacienteController', 'create']);
$router->add('POST', '/pacientes/create', ['PacienteController', 'create']);
$router->add('GET', '/pacientes/edit', ['PacienteController', 'edit']);   // RF-011
$router->add('POST', '/pacientes/edit', ['PacienteController', 'edit']);  // RF-011

// Citas
$router->add('GET', '/citas', ['CitaController', 'index']);
$router->add('GET', '/citas/create', ['CitaController', 'create']);
$router->add('POST', '/citas/create', ['CitaController', 'create']);
$router->add('POST', '/citas/cancel', ['CitaController', 'cancel']);
$router->add('POST', '/citas/completar', ['CitaController', 'completar']);
$router->add('GET', '/citas/comprobante', ['CitaController', 'comprobantePdf']); // RF-090

// Reportes
$router->add('GET', '/reportes/citas', ['ReporteController', 'export_citas']);

// Horarios Médicos
$router->add('GET', '/horarios', ['HorarioMedicoController', 'index']);
$router->add('GET', '/horarios/create', ['HorarioMedicoController', 'create']);
$router->add('POST', '/horarios/create', ['HorarioMedicoController', 'create']);
$router->add('POST', '/horarios/delete', ['HorarioMedicoController', 'delete']);

// Ausencias Médicas (RF-101)
require_once '../controllers/AusenciaMedicoController.php';
$router->add('GET', '/ausencias', ['AusenciaMedicoController', 'index']);
$router->add('GET', '/ausencias/create', ['AusenciaMedicoController', 'create']);
$router->add('POST', '/ausencias/create', ['AusenciaMedicoController', 'create']);

// Hospitalizacion
$router->add('GET', '/hospitalizacion', ['HospitalizacionController', 'index']);
$router->add('POST', '/hospitalizacion/ingresar', ['HospitalizacionController', 'ingresar']);
$router->add('POST', '/hospitalizacion/alta', ['HospitalizacionController', 'alta']);
$router->add('POST', '/hospitalizacion/limpiar', ['HospitalizacionController', 'limpiar']);

// Laboratorio
$router->add('GET', '/laboratorio', ['LaboratorioController', 'index']);
$router->add('GET', '/laboratorio/create', ['LaboratorioController', 'create']);
$router->add('POST', '/laboratorio/create', ['LaboratorioController', 'create']);

// Farmacia
$router->add('GET', '/farmacia', ['FarmaciaController', 'index']);
$router->add('POST', '/farmacia/update', ['FarmaciaController', 'update']);
$router->add('GET', '/farmacia/recetas', ['FarmaciaController', 'recetas']);
$router->add('POST', '/farmacia/despachar', ['FarmaciaController', 'despachar']);
$router->add('GET', '/farmacia/comprobante', ['FarmaciaController', 'comprobantePdf']);

// Historia Clínica
$router->add('GET', '/historia_clinica/create', ['HistoriaClinicaController', 'create']);
$router->add('POST', '/historia_clinica/create', ['HistoriaClinicaController', 'create']);
$router->add('GET', '/pacientes/{id}/historia', ['HistoriaClinicaController', 'show']);

// Facturación
$router->add('GET', '/facturacion', ['FacturacionController', 'index']);
$router->add('GET', '/facturacion/create', ['FacturacionController', 'create']);
$router->add('POST', '/facturacion/create', ['FacturacionController', 'store']);
$router->add('GET', '/facturacion/pdf', ['FacturacionController', 'downloadPdf']);
$router->add('POST', '/facturacion/status', ['FacturacionController', 'updateStatus']);

// Sucursales - adaptado de aleslisis (HU-107)
require_once '../models/Sucursal.php';
require_once '../controllers/SucursalController.php';
$router->add('GET', '/sucursal', ['SucursalController', 'index']);
$router->add('GET', '/sucursal/create', ['SucursalController', 'create']);
$router->add('POST', '/sucursal/create', ['SucursalController', 'create']);

// Inventario de Insumos - adaptado de aleslisis (HU-106)
require_once '../models/Insumo.php';
require_once '../controllers/InsumoController.php';
$router->add('GET', '/insumo', ['InsumoController', 'index']);
$router->add('GET', '/insumo/create', ['InsumoController', 'create']);
$router->add('POST', '/insumo/create', ['InsumoController', 'create']);

// Calificaciones de atención médica (Sprint 10 - HU-67)
require_once '../models/Calificacion.php';
require_once '../controllers/CalificacionController.php';
$router->add('POST', '/calificaciones/create', ['CalificacionController', 'create']);

$router->run();
?>
