<?php
/**
 * RUGAL - Crear Cita
 * AJAX endpoint para crear nueva cita
 */

require_once 'db.php';
require_once 'includes/citas_functions.php';
require_once 'includes/seguimiento_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar límite
$limite = puedeCrearCita($userId);
if (!$limite['permitido']) {
    echo json_encode([
        'success' => false,
        'error' => $limite['mensaje']
    ]);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

$datos = [
    'user_id' => $userId,
    'veterinaria_id' => $input['veterinaria_id'] ?? null,
    'mascota_id' => $input['mascota_id'] ?? null,
    'fecha_hora' => $input['fecha_hora'] ?? null,
    'tipo_cita' => $input['tipo_cita'] ?? 'consulta',
    'motivo' => $input['motivo'] ?? '',
    'precio_total' => $input['precio_total'] ?? 0,
    'porcentaje_anticipo' => $input['porcentaje_anticipo'] ?? 50
];

// Validar datos requeridos
if (!$datos['veterinaria_id'] || !$datos['mascota_id'] || !$datos['fecha_hora']) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

// Verificar que el slot esté disponible
$ocupado = verificarSlotOcupado($datos['veterinaria_id'], $datos['fecha_hora']);
if ($ocupado) {
    echo json_encode([
        'success' => false,
        'error' => 'Este horario ya no está disponible'
    ]);
    exit;
}

// Crear cita
$resultado = crearCita($datos);

// Si se creó la cita, generar preconsulta automática basada en últimos 7 días
if (!empty($resultado['success']) && $resultado['success'] === true) {
    try {
        $pre = generarPreconsulta($pdo, $datos['mascota_id'], 7, $resultado['cita_id'] ?? null);
        // agregar info de preconsulta a la respuesta
        $resultado['preconsulta'] = $pre;
    } catch (Exception $e) {
        // no detener la creación de la cita si falla la preconsulta
        $resultado['preconsulta_error'] = $e->getMessage();
    }
}

echo json_encode($resultado);
