<?php
require_once 'db.php';
require_once 'puntos-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$contenido = $_POST['contenido'] ?? '';
$mascotaId = !empty($_POST['mascota_id']) ? intval($_POST['mascota_id']) : null;

if (empty($contenido)) {
    echo json_encode(['success' => false, 'message' => 'El contenido no puede estar vacío']);
    exit;
}

$mediaUrl = null;
$mediaType = 'none';

if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    $fileName = time() . '_' . basename($_FILES['media']['name']);
    $targetPath = $uploadDir . $fileName;
    
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExtensions = ['mp4', 'webm', 'mov'];
    
    if (in_array($fileType, $imageExtensions)) {
        $mediaType = 'image';
    } elseif (in_array($fileType, $videoExtensions)) {
        $mediaType = 'video';
    }
    
    if (move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
        $mediaUrl = $fileName;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO publicaciones (user_id, mascota_id, contenido, media_url, media_type, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $result = $stmt->execute([$userId, $mascotaId, $contenido, $mediaUrl, $mediaType]);
    
    if ($result) {
        // Otorgar puntos por publicar
        if (function_exists('agregarPuntos')) {
            agregarPuntos($userId, 10, 'Publicación en comunidad');
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en BD']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
