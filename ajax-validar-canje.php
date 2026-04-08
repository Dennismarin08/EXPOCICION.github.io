<?php
error_reporting(0);
ob_start();
require_once 'db.php';
require_once 'puntos-functions.php';

session_start();
header('Content-Type: application/json');

// Limpiar cualquier output previo accidental
if (ob_get_length()) ob_clean();

// 1. Verificar sesión y rol
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_rol'] ?? '';

if ($userRole !== 'veterinaria' && $userRole !== 'tienda' && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// 2. Obtener ID del aliado (vincular usuario con tabla aliados)
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND activo = 1");
$stmt->execute([$userId]);
$aliado = $stmt->fetch();

if (!$aliado && $userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Perfil de aliado no encontrado']);
    exit;
}

$aliadoId = $aliado ? $aliado['id'] : null; // Admin puede validar cualquiera (id null)

// 3. Procesar acción
$action = $_POST['action'] ?? 'validar';
$codigo = $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Ingrese un código']);
    exit;
}

if ($action === 'validar') {
    echo json_encode(validarCodigoCanje($codigo, $aliadoId));
} elseif ($action === 'confirmar') {
    echo json_encode(marcarCanjeUsado($codigo, $aliadoId));
} else {
    echo json_encode(['success' => false, 'message' => 'Acción inválida']);
}
?>
