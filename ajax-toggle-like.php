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
$input = json_decode(file_get_contents('php://input'), true);
$postId = $input['post_id'] ?? null;

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Post ID requerido']);
    exit;
}

try {
    // Verificar si ya dio like
    $stmt = $pdo->prepare("SELECT id FROM publicaciones_likes WHERE user_id = ? AND publicacion_id = ?");
    $stmt->execute([$userId, $postId]);
    $existingLike = $stmt->fetch();

    if ($existingLike) {
        // Quitar like
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM publicaciones_likes WHERE id = ?");
        $stmt->execute([$existingLike['id']]);
        
        $stmt = $pdo->prepare("UPDATE publicaciones SET likes = likes - 1 WHERE id = ?");
        $stmt->execute([$postId]);
        $pdo->commit();
        
        $action = 'unliked';
    } else {
        // Dar like
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO publicaciones_likes (user_id, publicacion_id) VALUES (?, ?)");
        $stmt->execute([$userId, $postId]);
        
        $stmt = $pdo->prepare("UPDATE publicaciones SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$postId]);
        $pdo->commit();
        
        $action = 'liked';
    }

    // Obtener nuevo conteo
    $stmt = $pdo->prepare("SELECT likes FROM publicaciones WHERE id = ?");
    $stmt->execute([$postId]);
    $newCount = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'action' => $action, 'likes' => $newCount]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
