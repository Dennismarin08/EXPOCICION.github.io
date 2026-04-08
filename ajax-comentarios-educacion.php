<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no activa']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentId = $_POST['content_id'] ?? 0;
    $comentario = $_POST['comentario'] ?? '';
    $userId = $_SESSION['user_id'];

    if (!$contentId || empty(trim($comentario))) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comentarios_educacion (contenido_id, user_id, comentario) VALUES (?, ?, ?)");
        $stmt->execute([$contentId, $userId, $comentario]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $contentId = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nombre as autor_nombre 
            FROM comentarios_educacion c 
            JOIN usuarios u ON c.user_id = u.id 
            WHERE c.contenido_id = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$contentId]);
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}
