<?php
require_once 'db.php';
require_once 'includes/planes_salud_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$datos = [
    'mascota_id' => $_POST['mascota_id'],
    'fecha' => $_POST['fecha'],
    'tipo' => $_POST['tipo'],
    'motivo' => $_POST['motivo'],
    'diagnostico' => $_POST['diagnostico'] ?? '',
    'tratamiento' => $_POST['tratamiento'] ?? '',
    'veterinario' => $_POST['veterinario'] ?? '',
    'clinica' => $_POST['clinica'] ?? '',
    'notas' => $_POST['notas'] ?? ''
];

try {
    if (guardarHistorialMedico($datos)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
