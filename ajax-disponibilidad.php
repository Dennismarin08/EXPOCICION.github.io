<?php
/**
 * RUGAL - Obtener Disponibilidad de Veterinaria
 * AJAX endpoint para obtener slots disponibles
 */

require_once 'db.php';
require_once 'includes/citas_functions.php';

header('Content-Type: application/json');

$veterinariaId = $_GET['veterinaria_id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if (!$veterinariaId || !$fecha) {
    echo json_encode(['success' => false, 'error' => 'Parámetros faltantes']);
    exit;
}

try {
    $slots = obtenerDisponibilidad($veterinariaId, $fecha);
    
    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'fecha' => $fecha
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
