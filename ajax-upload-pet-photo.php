<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$mascotaId = $_POST['mascota_id'] ?? null;

if (!$mascotaId) {
    echo json_encode(['success' => false, 'error' => 'Falta ID de mascota']);
    exit;
}

// Verificar que la mascota pertenece al usuario
$stmt = $pdo->prepare("SELECT id FROM mascotas WHERE id = ? AND user_id = ?");
$stmt->execute([$mascotaId, $userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Mascota no encontrada o no autorizado']);
    exit;
}

// Validar que se subió un archivo
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ninguna foto']);
    exit;
}

$file = $_FILES['photo'];

// Validar tipo de archivo
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG o GIF']);
    exit;
}

// Validar tamaño (2MB máximo)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'El archivo es muy grande. Máximo 2MB']);
    exit;
}

// Crear directorio si no existe
$uploadDir = 'uploads/pets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'pet_' . $mascotaId . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Mover archivo
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo']);
    exit;
}

// Actualizar base de datos
try {
    $pdo->beginTransaction();

    // 1. Obtener la ruta de la foto anterior para borrarla DESPUÉS de confirmar la transacción
    $stmt = $pdo->prepare("SELECT foto_perfil FROM mascotas WHERE id = ? FOR UPDATE");
    $stmt->execute([$mascotaId]);
    $oldPhoto = $stmt->fetchColumn();
    
    // 2. Actualizar la base de datos con la nueva ruta
    $stmt = $pdo->prepare("UPDATE mascotas SET foto_perfil = ? WHERE id = ?");
    $stmt->execute([$filePath, $mascotaId]);

    // 3. Confirmar la transacción
    $pdo->commit();

    // 4. Si todo fue bien, ahora sí eliminamos el archivo antiguo
    if ($oldPhoto && file_exists($oldPhoto)) {
        unlink($oldPhoto);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Foto actualizada correctamente',
        'photo_url' => $filePath
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    // Si falla la BD, eliminar el archivo subido
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    error_log('Error en ajax-upload-pet-photo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al actualizar la foto.']);
}
?>
