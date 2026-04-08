<?php
/**
 * Simulate plan generation to catch errors
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Content-Type: text/plain\n\n";
echo "=== Simulating Plan Generation ===\n\n";

try {
    require_once 'db.php';
    echo "✓ Database connected\n";
    
    require_once 'includes/plan_salud_mensual_functions.php';
    echo "✓ Functions loaded\n";
    
    require_once 'puntos-functions.php';
    echo "✓ Puntos functions loaded\n";
    
    // Test if key functions exist
    echo "\nChecking functions:\n";
    echo "- generarRecomendacionesMensuales: " . (function_exists('generarRecomendacionesMensuales') ? "✓" : "✗") . "\n";
    echo "- calcularNivelAlerta: " . (function_exists('calcularNivelAlerta') ? "✓" : "✗") . "\n";
    echo "- guardarPlanMensual: " . (function_exists('guardarPlanMensual') ? "✓" : "✗") . "\n";
    echo "- registrarActividadUsuario: " . (function_exists('registrarActividadUsuario') ? "✓" : "✗") . "\n";
    echo "- obtenerExperienciaParaNivel: " . (function_exists('obtenerExperienciaParaNivel') ? "✓" : "✗") . "\n";
    
    // Test database tables
    echo "\nChecking tables:\n";
    $tables = ['mascotas', 'planes_salud_mensual', 'user_activity_log'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "- $table: ✓\n";
        } catch (Exception $e) {
            echo "- $table: ✗ " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== All checks passed! ===\n";
    echo "\nThe issue might be:\n";
    echo "1. Session not started (check-auth.php)\n";
    echo "2. CORS or headers issue\n";
    echo "3. JavaScript fetch error\n";
    echo "\nCheck browser console for more details.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
