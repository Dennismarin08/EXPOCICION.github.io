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
$canjeId = $_POST['canje_id'] ?? null;
$aliadoId = $_POST['aliado_id'] ?? null;

if (!$canjeId || !$aliadoId) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos para activación.']);
    exit;
}

$resultado = activarCanje($canjeId, $aliadoId, $userId);
echo json_encode($resultado);
?>
