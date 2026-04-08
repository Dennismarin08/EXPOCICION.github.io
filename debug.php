<?php
// debug.php - Script para diagnosticar el Error 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de RUGAL</h1>";

// 1. Verificar archivos base
$archivos = [
    'config.php',
    'db.php',
    'puntos-functions.php',
    'premium-functions.php',
    'includes/dashboard_data_functions.php',
    'includes/planes_salud_functions.php',
    'includes/comunidad_functions.php',
    'includes/salud_functions.php',
    'includes/plan_salud_mensual_functions.php',
    'includes/seguimiento_functions.php',
    'includes/calendario_functions.php'
];

echo "<h3>1. Verificando archivos requeridos:</h3>";
echo "<ul>";
foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "<li style='color:green'>✓ $archivo (Existe)</li>";
    } else {
        echo "<li style='color:red'>✗ $archivo (No se encuentra)</li>";
    }
}
echo "</ul>";

// 2. Intentar cargar config y db
echo "<h3>2. Probando conexión a Base de Datos:</h3>";
try {
    require_once 'config.php';
    require_once 'db.php';
    echo "<p style='color:green'>✓ Archivos de configuración cargados.</p>";
    
    if (isset($pdo)) {
        echo "<p style='color:green'>✓ Conexión PDO establecida correctamente.</p>";
        $stmt = $pdo->query("SELECT VERSION()");
        $version = $stmt->fetchColumn();
        echo "<p>Versión de MySQL: $version</p>";
    } else {
        echo "<p style='color:red'>✗ La variable \$pdo no está definida.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// 3. Probar sesión
echo "<h3>3. Probando sesión:</h3>";
if (session_status() === PHP_SESSION_NONE) session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<pre>Contenido de \$_SESSION: ";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Recomendación:</h3>";
echo "<p>Si ves algún archivo en ROJO aquí arriba, debes subirlo por FTP o File Manager.</p>";
?>
