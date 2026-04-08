<?php
require_once 'db.php';
require_once 'includes/seguimiento_functions.php';
require_once 'premium-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$mascota_id = $input['mascota_id'] ?? null;
$fecha = $input['fecha'] ?? date('Y-m-d');
$datos = $input['datos'] ?? [];
$observaciones = $input['observaciones'] ?? '';

if (!$mascota_id) {
    echo json_encode(['success' => false, 'error' => 'Mascota requerida']);
    exit;
}

$isPremium = esPremium($user_id);

// Sin límite de registros - tanto Free como Premium tienen acceso ilimitado
$res = guardarSeguimiento($pdo, $user_id, $mascota_id, $fecha, $datos, $observaciones);

echo json_encode(['success' => true, 'seguimiento_id' => $res['id'], 'alertas' => $res['alertas']]);
