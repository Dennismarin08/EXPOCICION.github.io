<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'db.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$userId = $_SESSION['user_id'];
$tareaId = $_POST['tarea_id'] ?? null;

if (!$tareaId) {
    echo json_encode(['success' => false, 'message' => 'Tarea no especificada']);
    exit;
}

$evidencia = null;

// Verificar si el POST excede el límite del servidor (post_max_size)
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $postMax = ini_get('post_max_size');
    echo json_encode(['success' => false, 'message' => "El archivo es demasiado grande. Excede el límite del servidor (post_max_size: $postMax)"]);
    exit;
}

$evidencia = null;

// Verificar si se subió un archivo (foto o video)
if (isset($_FILES['evidencia'])) {
    if ($_FILES['evidencia']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['evidencia']['error'];
        if ($uploadError !== UPLOAD_ERR_NO_FILE) {
            $message = "Error al subir archivo: ";
            switch ($uploadError) {
                case UPLOAD_ERR_INI_SIZE:
                    $uploadMax = ini_get('upload_max_filesize');
                    $message = "El archivo excede el límite del servidor ($uploadMax).";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = "El archivo excede el límite del formulario.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = "El archivo se subió parcialmente. Intenta de nuevo.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = "Falta la carpeta temporal en el servidor.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = "No se pudo escribir el archivo en el disco.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $message = "Una extensión de PHP detuvo la subida.";
                    break;
                default:
                    $message .= "Código de error: " . $uploadError;
            }
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    } else {
        $file = $_FILES['evidencia'];
        
        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm']; // Added more video types
        $fileType = $file['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            // Check specifically for video/mp4 as sometimes it's octet-stream
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','mp4','mov','avi','webm'])) {
                 echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo imágenes (JPG, PNG) o videos (MP4, MOV). Recibido: ' . $fileType]);
                 exit;
            }
        }
        
        // Validar tamaño (validamos contra el límite real o un máximo razonable)
        $maxSize = 5 * 1024 * 1024; // 5MB como solicitó el usuario
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
            exit;
        }
        
        // Crear directorio si no existe
        $uploadDir = 'uploads/evidencias/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'evidencia_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $evidencia = $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo en el servidor']);
            exit;
        }
    }
}

try {
    // Completar la tarea
    $resultado = completarTarea($userId, $tareaId, $evidencia);
} catch (Exception $e) {
    $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error fatal: " . $e->getMessage() . "\n";
    file_put_contents('debug_tasks.log', $errorMsg, FILE_APPEND);
    $resultado = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
}

// Devolver resultado como JSON
echo json_encode($resultado);
