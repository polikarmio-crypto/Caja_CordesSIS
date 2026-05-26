<?php
// Script de diagnóstico - Borrar después de solucionar el problema
echo "<h2>🔍 Diagnóstico del Sistema Caja Cordes</h2>";
echo "<hr>";

// 1. Verificar extensiones PHP necesarias
echo "<h3>1. Extensiones PHP</h3><ul>";
$exts = ['pdo', 'pdo_pgsql', 'pgsql', 'mbstring', 'openssl', 'json'];
foreach ($exts as $ext) {
    $ok = extension_loaded($ext);
    echo "<li style='color:" . ($ok ? 'green' : 'red') . "'>";
    echo ($ok ? '✅' : '❌') . " $ext";
    echo "</li>";
}
echo "</ul>";

// 2. Intentar conectar a PostgreSQL
echo "<h3>2. Conexión a PostgreSQL</h3>";

// Leer .env
$env = [];
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value);
        }
    }
    echo "<p>✅ Archivo .env encontrado</p>";
    echo "<ul>";
    echo "<li>Host: <b>" . ($env['DB_HOST'] ?? 'NO DEFINIDO') . "</b></li>";
    echo "<li>Base de datos: <b>" . ($env['DB_NAME'] ?? 'NO DEFINIDO') . "</b></li>";
    echo "<li>Usuario: <b>" . ($env['DB_USER'] ?? 'NO DEFINIDO') . "</b></li>";
    echo "<li>Contraseña: <b>" . (isset($env['DB_PASS']) ? str_repeat('*', strlen($env['DB_PASS'])) : 'NO DEFINIDA') . "</b></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Archivo .env NO encontrado en: $envFile</p>";
}

try {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $db   = $env['DB_NAME'] ?? 'caja_cordes';
    $user = $env['DB_USER'] ?? 'postgres';
    $pass = $env['DB_PASS'] ?? '';
    
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ <b>Conexión a PostgreSQL EXITOSA</b></p>";
    
    // Verificar tablas principales
    echo "<h3>3. Tablas en la base de datos</h3><ul>";
    $tablas = ['usuarios', 'roles', 'pacientes', 'medicos', 'citas', 'sucursales', 'insumos', 'calificaciones'];
    $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
    $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tablas as $tabla) {
        $existe = in_array($tabla, $existentes);
        echo "<li style='color:" . ($existe ? 'green' : 'orange') . "'>";
        echo ($existe ? '✅' : '⚠️') . " $tabla";
        echo "</li>";
    }
    echo "</ul>";
    
    // Verificar usuario admin
    echo "<h3>4. Usuarios de prueba</h3><ul>";
    $stmt = $pdo->query("SELECT u.email, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)) {
        echo "<li style='color:orange'>⚠️ No hay usuarios en la base de datos</li>";
    }
    foreach ($users as $u) {
        echo "<li>👤 <b>{$u['email']}</b> — Rol: {$u['rol']}</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color:red; background:#fee; padding:15px; border-radius:8px'>";
    echo "❌ <b>Error de conexión:</b> " . htmlspecialchars($e->getMessage());
    echo "</p>";
    
    echo "<h3>💡 Posibles soluciones:</h3><ul>";
    echo "<li>Verificar que PostgreSQL esté corriendo: <code>sudo systemctl status postgresql</code></li>";
    echo "<li>Verificar credenciales en el archivo <code>.env</code></li>";
    echo "<li>Verificar que la base de datos exista: <code>sudo -u postgres psql -l</code></li>";
    echo "<li>Si la extensión pdo_pgsql no está activa, editar <code>/etc/php/php.ini</code> y descomentar <code>extension=pdo_pgsql</code></li>";
    echo "</ul>";
}

echo "<hr><p><small>Borrar este archivo cuando termines el diagnóstico.</small></p>";
