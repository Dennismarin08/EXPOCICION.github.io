<?php
require_once 'db.php';
require_once 'puntos-functions.php';

// db.php already starts the session
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$recompensaId = $_POST['recompensa_id'] ?? null;
$aliadoId = $_POST['aliado_id'] ?? null;

if (!$recompensaId) {
    echo json_encode(['success' => false, 'message' => 'ID no especificado']);
    exit;
}

// Intentar canje
$resultado = canjearRecompensa($userId, $recompensaId, $aliadoId);

// Si es exitoso, devolver también info (extra si se necesitara, 
// aunque el modal ya usa la info del frontend)
echo json_encode($resultado);
?>
