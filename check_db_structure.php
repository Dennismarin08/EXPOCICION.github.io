<?php
require_once 'db.php';

// Check planes_salud_mensual table structure
$stmt = $pdo->query("DESCRIBE planes_salud_mensual");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Estructura de planes_salud_mensual ===\n";
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . " - " . $col['Null'] . " - " . $col['Key'] . "\n";
}

// Check if user_activity_log table exists
try {
    $stmt = $pdo->query("DESCRIBE user_activity_log");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== Estructura de user_activity_log ===\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "\n=== user_activity_log NO EXISTE ===\n";
    echo "Necesitamos crear esta tabla.\n";
}
