<?php
/**
 * RUGAL - API para obtener plan de salud
 * Retorna el plan activo de una mascota
 */

require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mascotaId = $input['mascota_id'] ?? null;

if (!$mascotaId) {
    echo json_encode(['success' => false, 'error' => 'ID de mascota no proporcionado']);
    exit;
}

try {
    // Verificar que la mascota pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM mascotas WHERE id = ? AND user_id = ?");
    $stmt->execute([$mascotaId, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Mascota no encontrada']);
        exit;
    }

    // Obtener el plan activo más reciente
    $stmt = $pdo->prepare("
        SELECT * FROM planes_salud 
        WHERE mascota_id = ? AND activo = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$mascotaId]);
    $plan = $stmt->fetch();

    if ($plan) {
        echo json_encode([
            'success' => true,
            'plan' => $plan
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No existe plan activo para esta mascota'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
