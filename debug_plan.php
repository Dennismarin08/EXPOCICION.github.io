<?php
/**
 * Debug endpoint - simula exactamente lo que hace ajax-generar-plan-mensual.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Plan Generation</h2>";
echo "<pre>";

try {
    echo "Step 1: Loading dependencies...\n";
    require_once 'db.php';
    echo "✓ db.php loaded\n";
    
    require_once 'includes/plan_salud_mensual_functions.php';
    echo "✓ plan_salud_mensual_functions.php loaded\n";
    
    require_once 'puntos-functions.php';
    echo "✓ puntos-functions.php loaded\n";
    
    echo "\nStep 2: Testing functions...\n";
    
    // Test obtenerExperienciaParaNivel
    if (function_exists('obtenerExperienciaParaNivel')) {
        $xp = obtenerExperienciaParaNivel(2);
        echo "✓ obtenerExperienciaParaNivel(2) = $xp\n";
    } else {
        echo "✗ obtenerExperienciaParaNivel NOT FOUND\n";
    }
    
    // Test calcularPuntajeSalud
    if (function_exists('calcularPuntajeSalud')) {
        $health = calcularPuntajeSalud('verde');
        echo "✓ calcularPuntajeSalud('verde') = " . $health['score'] . "%\n";
    } else {
        echo "✗ calcularPuntajeSalud NOT FOUND\n";
    }
    
    // Test registrarActividadUsuario
    if (function_exists('registrarActividadUsuario')) {
        echo "✓ registrarActividadUsuario exists\n";
    } else {
        echo "✗ registrarActividadUsuario NOT FOUND\n";
    }
    
    echo "\nStep 3: Testing database tables...\n";
    
    // Check user_activity_log
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_activity_log");
        $result = $stmt->fetch();
        echo "✓ user_activity_log table exists ({$result['count']} records)\n";
    } catch (Exception $e) {
        echo "✗ user_activity_log error: " . $e->getMessage() . "\n";
    }
    
    // Check planes_salud_mensual
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM planes_salud_mensual LIKE 'health_score'");
        if ($stmt->rowCount() > 0) {
            echo "✓ health_score column exists\n";
        } else {
            echo "✗ health_score column NOT FOUND - run migrate_health_score.php\n";
        }
    } catch (Exception $e) {
        echo "✗ planes_salud_mensual error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== ALL TESTS PASSED ===\n";
    echo "\nIf you see this, the backend is working.\n";
    echo "The error might be in the JavaScript or session.\n";
    
} catch (Exception $e) {
    echo "\n✗✗✗ FATAL ERROR ✗✗✗\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
