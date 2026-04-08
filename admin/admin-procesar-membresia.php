<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../premium-functions.php';

// Verificar sesión y rol
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user = getUserRole($_SESSION['user_id']); // Usar getUserRole de db.php
if (!$user || $user['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_POST['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Usuario no especificado']);
    exit;
}

try {
    if ($action === 'activar') {
        $planId = $_POST['plan_id'] ?? null;
        $referencia = $_POST['referencia'] ?? 'Manual Admin';
        
        if (!$planId) {
            echo json_encode(['success' => false, 'message' => 'Plan no especificado']);
            exit;
        }
        
        // Obtener plan para precio
        $plan = obtenerPlan($planId);
        if (!$plan) {
            echo json_encode(['success' => false, 'message' => 'Plan inválido']);
            exit;
        }
        
        // Crear suscripción
        $resultado = crearSuscripcion($userId, $planId, 'manual_admin', $plan['precio']);
        
        echo json_encode($resultado);
        
    } elseif ($action === 'cancelar') {
        $resultado = cancelarSuscripcion($userId);
        echo json_encode($resultado);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
