<?php
require_once 'db.php';

// Verificar acceso de admin
if (!isAdmin($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $allyId = (int)($_POST['ally_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'toggle_premium':
                if (!$userId) throw new Exception('ID de usuario inválido');
                $stmt = $pdo->prepare("UPDATE usuarios SET premium = 1 - premium WHERE id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true]);
                break;
                
            case 'delete_user':
                if (!$userId) throw new Exception('ID de usuario inválido');
                // Al eliminar un usuario, la tabla aliados debería tener ON DELETE CASCADE, 
                // pero por si acaso lo manejamos o confiamos en la DB.
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true]);
                break;

            case 'approve_ally':
                if (!$allyId) throw new Exception('ID de aliado inválido');
                $stmt = $pdo->prepare("UPDATE aliados SET activo = 1, pendiente_verificacion = 0 WHERE id = ?");
                $stmt->execute([$allyId]);
                echo json_encode(['success' => true, 'message' => 'Aliado aprobado y activado']);
                break;

            case 'reject_ally':
                if (!$allyId) throw new Exception('ID de aliado inválido');
                $stmt = $pdo->prepare("UPDATE aliados SET activo = 0, pendiente_verificacion = 0 WHERE id = ?");
                $stmt->execute([$allyId]);
                echo json_encode(['success' => true, 'message' => 'Aliado rechazado']);
                break;

            case 'toggle_ally_status':
                if (!$allyId) throw new Exception('ID de aliado inválido');
                $stmt = $pdo->prepare("UPDATE aliados SET activo = 1 - activo WHERE id = ?");
                $stmt->execute([$allyId]);
                echo json_encode(['success' => true]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
