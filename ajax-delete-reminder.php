<?php
/**
 * RUGAL - Eliminar Recordatorio
 * AJAX endpoint para eliminar recordatorios del calendario
 */

require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$recordatorioId = $input['recordatorio_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$recordatorioId) {
    echo json_encode(['success' => false, 'error' => 'ID de recordatorio no proporcionado']);
    exit;
}

try {
    // Verificar que el recordatorio pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM recordatorios WHERE id = ? AND user_id = ?");
    $stmt->execute([$recordatorioId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Recordatorio no encontrado o no autorizado']);
        exit;
    }
    
    // Eliminar el recordatorio
    $stmt = $pdo->prepare("DELETE FROM recordatorios WHERE id = ? AND user_id = ?");
    $stmt->execute([$recordatorioId, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Recordatorio eliminado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
