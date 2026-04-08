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
$content = $input['content'] ?? null;

if (!$postId || empty(trim($content))) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comentarios (user_id, publicacion_id, contenido, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$userId, $postId, $content]);
    
    // Obtener datos del usuario para devolver el comentario renderizado (o solo éxito)
    $stmtUser = $pdo->prepare("SELECT nombre, foto_perfil FROM usuarios WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    echo json_encode([
        'success' => true, 
        'comment' => [
            'author' => $user['nombre'],
            'photo' => $user['foto_perfil'],
            'content' => $content,
            'date' => 'Hace un momento'
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
