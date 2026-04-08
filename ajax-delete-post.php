<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();
require_once 'db.php';
ob_clean();
require_once 'includes/comunidad_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar permisos (Admin o Dueño)
$stmt = $pdo->prepare("SELECT user_id FROM publicaciones WHERE id = ?");
$data = json_decode(file_get_contents('php://input'), true);
$postId = $data['id'] ?? null;

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'ID de publicación no válido']);
    exit;
}

$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Publicación no encontrada']);
    exit;
}

// Verificar si es admin o si es el dueño
$stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

if ($post['user_id'] != $_SESSION['user_id'] && $currentUser['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta publicación']);
    exit;
}

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'ID de publicación no válido']);
    exit;
}

if (eliminarPublicacion($postId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la publicación']);
}
