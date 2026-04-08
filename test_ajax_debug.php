<?php
/**
 * Comprehensive error logging for ajax
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Start output buffering
ob_start();

echo "=== AJAX DEBUG MODE ===\n\n";

try {
    echo "Step 1: Loading files...\n";
    
    require_once 'includes/check-auth.php';
    echo "✓ check-auth.php loaded\n";
    
    require_once 'includes/plan_salud_mensual_functions.php';
    echo "✓ plan_salud_mensual_functions.php loaded\n";
    
    require_once 'puntos-functions.php';
    echo "✓ puntos-functions.php loaded\n";
    
    require_once 'db.php';
    echo "✓ db.php loaded\n";
    
    echo "\nStep 2: Checking session...\n";
    if (isset($_SESSION['user_id'])) {
        echo "✓ User logged in: " . $_SESSION['user_id'] . "\n";
    } else {
        echo "✗ NO SESSION - User not logged in!\n";
        die("ERROR: You must be logged in\n");
    }
    
    echo "\nStep 3: Checking POST data...\n";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "✓ POST request received\n";
        echo "POST data: " . print_r($_POST, true) . "\n";
    } else {
        echo "✗ Not a POST request (method: " . $_SERVER['REQUEST_METHOD'] . ")\n";
        echo "This is just a test page. Use the actual form to submit.\n";
    }
    
    echo "\nStep 4: Testing database...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "✓ Database connected\n";
    
    echo "\nStep 5: Testing functions...\n";
    $functions = [
        'generarRecomendacionesMensuales',
        'calcularNivelAlerta', 
        'guardarPlanMensual',
        'calcularPuntajeSalud',
        'registrarActividadUsuario',
        'obtenerExperienciaParaNivel'
    ];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✓ $func exists\n";
        } else {
            echo "✗ $func NOT FOUND!\n";
        }
    }
    
    echo "\n=== ALL CHECKS PASSED ===\n";
    echo "\nIf you see this, the backend is ready.\n";
    echo "Try submitting the form now.\n";
    
} catch (Exception $e) {
    echo "\n✗✗✗ FATAL ERROR ✗✗✗\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();
echo "<pre>" . htmlspecialchars($output) . "</pre>";
