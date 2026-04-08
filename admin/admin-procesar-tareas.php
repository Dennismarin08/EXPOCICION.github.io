<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../puntos-functions.php';

// Verificar sesión y rol
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user = getUserRole($_SESSION['user_id']);
if (!$user || $user['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ==========================================
    // VALIDACIÓN DE EVIDENCIAS
    // ==========================================
    if ($action === 'aprobar') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID no especificado');
        
        $resultado = aprobarTarea($id);
        echo json_encode($resultado);
        
    } elseif ($action === 'rechazar') {
        $id = $_POST['id'] ?? null;
        $comentario = $_POST['comentario'] ?? '';
        
        if (!$id) throw new Exception('ID no especificado');
        
        $resultado = rechazarTarea($id, $comentario);
        echo json_encode($resultado);
        
    } elseif ($action === 'revocar') {
        $id = $_POST['id'] ?? null;
        $comentario = $_POST['comentario'] ?? '';
        
        if (!$id) throw new Exception('ID no especificado');
        
        $resultado = revocarTarea($id, $comentario);
        echo json_encode($resultado);
        
    } 
    // ==========================================
    // GESTIÓN DE TAREAS (CRUD)
    // ==========================================
    elseif ($action === 'crear_tarea') {
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $puntos = $_POST['puntos'] ?? 0;
        $tipo = $_POST['tipo'] ?? 'diaria';
        $categoria = $_POST['categoria'] ?? 'otros';
        $detalles = $_POST['detalles'] ?? null;
        $icono = $_POST['icono'] ?? 'fas fa-star';
        $requiereEvidencia = isset($_POST['requiere_evidencia']) ? 1 : 0;
        $tipoEvidencia = $_POST['tipo_evidencia'] ?? 'foto';
        $tipoAcceso = $_POST['tipo_acceso'] ?? 'free';
        $fechaLimite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;

        if (empty($titulo)) throw new Exception('Título es requerido');

        // Manejo de subida de video (opcional)
        $videoPath = null;
        if (!empty($_FILES['video']) && $_FILES['video']['name'] !== '') {
            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['video']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $maxSrv = ini_get('upload_max_filesize');
                        throw new Exception("El video guía excede el límite del servidor ($maxSrv).");
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception("El video se subió parcialmente.");
                    default:
                        throw new Exception("Error al subir el video guía (Código: " . $_FILES['video']['error'] . ")");
                }
            }
            
            // Validar capacidad de 5MB solicitada
            if ($_FILES['video']['size'] > 5 * 1024 * 1024) {
                throw new Exception("El video guía es demasiado grande. El límite es de 5MB.");
            }

            $uploadDir = __DIR__ . '/uploads/task_videos';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $tmp = $_FILES['video']['tmp_name'];
            $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['video']['name']);
            $dest = $uploadDir . '/' . $name;
            if (move_uploaded_file($tmp, $dest)) {
                $videoPath = 'uploads/task_videos/' . $name;
            } else {
                throw new Exception("No se pudo guardar el video en el servidor.");
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tareas_comunidad (titulo, descripcion, puntos, tipo, tipo_acceso, categoria, detalles, icono, requiere_evidencia, tipo_evidencia, fecha_limite, video_url, activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$titulo, $descripcion, $puntos, $tipo, $tipoAcceso, $categoria, $detalles, $icono, $requiereEvidencia, $tipoEvidencia, $fechaLimite, $videoPath]);

        echo json_encode(['success' => true, 'message' => 'Tarea creada exitosamente']);

    } elseif ($action === 'editar_tarea') {
        $id = $_POST['id'] ?? null;
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $puntos = $_POST['puntos'] ?? 0;
        $tipo = $_POST['tipo'] ?? 'diaria';
        $categoria = $_POST['categoria'] ?? 'otros';
        $detalles = $_POST['detalles'] ?? null;
        $icono = $_POST['icono'] ?? 'fas fa-star';
        $requiereEvidencia = isset($_POST['requiere_evidencia']) ? 1 : 0;
        $tipoEvidencia = $_POST['tipo_evidencia'] ?? 'foto';
        $tipoAcceso = $_POST['tipo_acceso'] ?? 'free';
        $fechaLimite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;

        if (!$id) throw new Exception('ID no especificado');

        // Manejo de nueva subida de video (opcional)
        $videoPath = null;
        if (!empty($_FILES['video']) && $_FILES['video']['name'] !== '') {
            if ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['video']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $maxSrv = ini_get('upload_max_filesize');
                        throw new Exception("El video guía excede el límite del servidor ($maxSrv).");
                    default:
                        throw new Exception("Error al subir el nuevo video guía (Código: " . $_FILES['video']['error'] . ")");
                }
            }
            
            // Validar capacidad de 5MB solicitada
            if ($_FILES['video']['size'] > 5 * 1024 * 1024) {
                throw new Exception("El video guía es demasiado grande. El límite es de 5MB.");
            }

            $uploadDir = __DIR__ . '/uploads/task_videos';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $tmp = $_FILES['video']['tmp_name'];
            $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['video']['name']);
            $dest = $uploadDir . '/' . $name;
            if (move_uploaded_file($tmp, $dest)) {
                $videoPath = 'uploads/task_videos/' . $name;
            } else {
                throw new Exception("No se pudo guardar el nuevo video.");
            }
        }

        if ($videoPath) {
            $stmt = $pdo->prepare("UPDATE tareas_comunidad SET titulo = ?, descripcion = ?, puntos = ?, tipo = ?, tipo_acceso = ?, categoria = ?, detalles = ?, icono = ?, requiere_evidencia = ?, tipo_evidencia = ?, fecha_limite = ?, video_url = ? WHERE id = ?");
            $stmt->execute([$titulo, $descripcion, $puntos, $tipo, $tipoAcceso, $categoria, $detalles, $icono, $requiereEvidencia, $tipoEvidencia, $fechaLimite, $videoPath, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tareas_comunidad SET titulo = ?, descripcion = ?, puntos = ?, tipo = ?, tipo_acceso = ?, categoria = ?, detalles = ?, icono = ?, requiere_evidencia = ?, tipo_evidencia = ?, fecha_limite = ? WHERE id = ?");
            $stmt->execute([$titulo, $descripcion, $puntos, $tipo, $tipoAcceso, $categoria, $detalles, $icono, $requiereEvidencia, $tipoEvidencia, $fechaLimite, $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Tarea actualizada exitosamente']);
        
    } elseif ($action === 'eliminar_tarea') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID no especificado');
        
        // Soft delete (desactivar) en lugar de borrar para mantener histórico
        $stmt = $pdo->prepare("UPDATE tareas_comunidad SET activa = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Tarea eliminada exitosamente']);
        
    } elseif ($action === 'obtener_tarea') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID no especificado');
        
        $stmt = $pdo->prepare("SELECT * FROM tareas_comunidad WHERE id = ?");
        $stmt->execute([$id]);
        $tarea = $stmt->fetch();
        
        echo json_encode(['success' => true, 'tarea' => $tarea]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
