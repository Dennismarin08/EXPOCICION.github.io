<?php
/**
 * RUGAL - Comunidad Functions
 * Helpers for community posts and social features
 */

require_once 'db.php';

/**
 * Get featured posts for the community highlight
 */
function obtenerPublicacionesDestacadas($limit = 4) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.nombre as autor_nombre, u.foto_perfil as autor_foto
            FROM publicaciones p
            JOIN usuarios u ON p.user_id = u.id
            ORDER BY p.likes DESC, p.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in obtenerPublicacionesDestacadas: " . $e->getMessage());
        return [];
    }
}

/**
 * Get posts associated with a specific pet
 */
function obtenerPublicacionesMascota($mascota_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.nombre as autor_nombre, u.foto_perfil as autor_foto
            FROM publicaciones p
            JOIN usuarios u ON p.user_id = u.id
            WHERE p.mascota_id = ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $mascota_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in obtenerPublicacionesMascota: " . $e->getMessage());
        return [];
    }
}

/**
 * Render post media (images or video)
 */
function renderPostMedia($post) {
    if (!$post['media_url']) return '';
    
    $url = htmlspecialchars($post['media_url']);
    if (strpos($url, 'http') !== 0 && strpos($url, 'uploads/') !== 0) {
        $url = 'uploads/' . $url;
    }
    
    if ($post['media_type'] === 'video') {
        return "<video src='{$url}' controls class='community-img'></video>";
    } else {
        return "<img src='{$url}' class='community-img' alt='Post'>";
    }
}

/**
 * Delete a publication and its media file
 */
function eliminarPublicacion($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT media_url FROM publicaciones WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success && $media) {
            $file = __DIR__ . '/../uploads/' . $media;
            if (file_exists($file) && !is_dir($file)) {
                unlink($file);
            }
        }
        return $success;
    } catch (Exception $e) {
        error_log("Error in eliminarPublicacion: " . $e->getMessage());
        return false;
    }
}

/**
 * Get comments for a post
 */
function obtenerComentarios($postId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nombre as autor_nombre, u.foto_perfil as autor_foto
            FROM comentarios c
            JOIN usuarios u ON c.user_id = u.id
            WHERE c.publicacion_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Check if user liked a post
 */
function usuarioDioLike($userId, $postId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM publicaciones_likes WHERE user_id = ? AND publicacion_id = ?");
    $stmt->execute([$userId, $postId]);
    return (bool)$stmt->fetch();
}
