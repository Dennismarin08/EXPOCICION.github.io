<?php
/**
 * RUGAL - Cancelar Cita
 * AJAX endpoint para cancelar una cita
 */

require_once 'db.php';
require_once 'includes/citas_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$citaId = $input['cita_id'] ?? null;

if (!$citaId) {
    echo json_encode(['success' => false, 'error' => 'ID de cita no proporcionado']);
    exit;
}

// Cancelar cita
$resultado = cancelarCita($citaId, $userId);

echo json_encode($resultado);
