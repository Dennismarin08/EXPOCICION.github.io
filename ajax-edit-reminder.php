<?php
/**
 * RUGAL - Editar Recordatorio
 * AJAX endpoint para editar recordatorios del calendario
 */

require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$recordatorioId = $input['recordatorio_id'] ?? null;
$titulo = $input['titulo'] ?? null;
$fecha = $input['fecha'] ?? null;
$userId = $_SESSION['user_id'];

if (!$recordatorioId || !$titulo || !$fecha) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
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
    
    // Actualizar el recordatorio
    $stmt = $pdo->prepare("UPDATE recordatorios SET titulo = ?, fecha_programada = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$titulo, $fecha, $recordatorioId, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Recordatorio actualizado correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()]);
}
