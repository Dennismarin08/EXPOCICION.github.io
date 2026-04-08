<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$mascotaId = $_POST['mascota_id'] ?? null;
$estadoSalud = $_POST['estado_salud'] ?? null;

if (!$mascotaId || !$estadoSalud) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

// Validar que el estado es válido
$estadosValidos = ['excelente', 'regular', 'revision', 'grave'];
if (!in_array($estadoSalud, $estadosValidos)) {
    echo json_encode(['success' => false, 'error' => 'Estado de salud no válido']);
    exit;
}

// Verificar que la mascota pertenece al usuario
$stmt = $pdo->prepare("SELECT id FROM mascotas WHERE id = ? AND user_id = ?");
$stmt->execute([$mascotaId, $userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Mascota no encontrada o no autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE mascotas SET estado_salud = ? WHERE id = ?");
    $stmt->execute([$estadoSalud, $mascotaId]);
    
    echo json_encode(['success' => true, 'message' => 'Estado de salud actualizado']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en base de datos: ' . $e->getMessage()]);
}
?>
