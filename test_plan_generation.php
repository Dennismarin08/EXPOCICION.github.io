<?php
/**
 * Test script to debug plan generation
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Plan Generation Dependencies ===\n\n";

// Test 1: Check if files exist
echo "1. Checking required files...\n";
$files = [
    'db.php',
    'includes/check-auth.php',
    'includes/plan_salud_mensual_functions.php',
    'puntos-functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file NOT FOUND\n";
    }
}

echo "\n2. Testing includes...\n";
try {
    require_once 'db.php';
    echo "   ✓ db.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ db.php error: " . $e->getMessage() . "\n";
}

try {
    require_once 'includes/plan_salud_mensual_functions.php';
    echo "   ✓ plan_salud_mensual_functions.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ plan_salud_mensual_functions.php error: " . $e->getMessage() . "\n";
}

try {
    require_once 'puntos-functions.php';
    echo "   ✓ puntos-functions.php loaded\n";
} catch (Exception $e) {
    echo "   ✗ puntos-functions.php error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing database connection...\n";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "   ✓ Database connection OK\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing key functions...\n";
$functions = [
    'generarRecomendacionesMensuales',
    'calcularNivelAlerta',
    'guardarPlanMensual',
    'calcularPuntajeSalud',
    'obtenerExperienciaParaNivel'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ $func() exists\n";
    } else {
        echo "   ✗ $func() NOT FOUND\n";
    }
}

echo "\n=== Test Complete ===\n";
