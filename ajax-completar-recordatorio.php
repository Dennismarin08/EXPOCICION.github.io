<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['recordatorio_id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Falta ID de recordatorio']);
    exit;
}

try {
    // **CORRECCIÓN DE SEGURIDAD**
    // Se une con la tabla de mascotas para asegurar que el recordatorio
    // pertenece a una mascota del usuario actual.
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare(
        "UPDATE recordatorios_plan r
         JOIN mascotas m ON r.mascota_id = m.id
         SET r.completado = 1 
         WHERE r.id = ? AND m.user_id = ?"
    );
    $stmt->execute([$id, $userId]);
    
    // Si no se afectó ninguna fila, es porque el recordatorio no existe o no pertenece al usuario
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Recordatorio no encontrado o no autorizado.']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // En producción, es mejor registrar el error y no mostrarlo al usuario.
    error_log('Error en ajax-completar-recordatorio: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error en el servidor.']);
}
?>
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
