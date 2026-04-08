<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();
require_once 'db.php';
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$historialId = $data['id'] ?? null;

if (!$historialId) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

try {
    // Verificar que el registro pertenezca a una mascota del usuario
    // Hacemos JOIN con mascotas para validar propiedad
    $stmt = $pdo->prepare("
        SELECT h.id 
        FROM historial_medico h
        JOIN mascotas m ON h.mascota_id = m.id
        WHERE h.id = ? AND m.user_id = ? AND (h.tipo = 'IA' OR h.tipo = 'comportamiento_ia')
    ");
    $stmt->execute([$historialId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado o no autorizado']);
        exit;
    }

    // Eliminar
    $stmt = $pdo->prepare("DELETE FROM historial_medico WHERE id = ?");
    $stmt->execute([$historialId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
