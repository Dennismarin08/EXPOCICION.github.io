<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Handle JSON input for deletions
$input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? ($input['action'] ?? '');

if ($action === 'create') {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $paso_a_paso = $_POST['paso_a_paso'] ?? '';
    $lista_necesidades = $_POST['lista_necesidades'] ?? '';
    $categoria = $_POST['categoria'] ?? 'educacion';
    $tipo = $_POST['tipo'] ?? 'foto';
    
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/images/edu/'; // Default dir for education
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['media']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
            $mediaUrl = $targetPath;
            
            try {
                $stmt = $pdo->prepare("INSERT INTO contenido_educativo (titulo, descripcion, paso_a_paso, lista_necesidades, categoria, tipo, media_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$titulo, $descripcion, $paso_a_paso, $lista_necesidades, $categoria, $tipo, $mediaUrl]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Archivo no subido']);
    }
} elseif ($action === 'delete') {
    $id = $input['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM contenido_educativo WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
