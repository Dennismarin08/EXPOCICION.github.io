<?php
/**
 * RUGAL - Guardar Configuración de Horarios (Veterinaria)
 * AJAX endpoint
 */

require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'veterinaria') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$veterinariaId = $input['veterinaria_id'] ?? null;
$anticipoRequerido = $input['anticipo_requerido'] ?? 50;
$precioConsulta = $input['precio_consulta'] ?? 0;
$horarios = $input['horarios'] ?? [];

if (!$veterinariaId) {
    echo json_encode(['success' => false, 'error' => 'ID de veterinaria no proporcionado']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Actualizar configuración de anticipo y precio
    $stmt = $pdo->prepare("
        UPDATE aliados 
        SET anticipo_requerido = ?, precio_consulta = ?
        WHERE id = ?
    ");
    $stmt->execute([$anticipoRequerido, $precioConsulta, $veterinariaId]);
    
    // Eliminar horarios anteriores
    $stmt = $pdo->prepare("DELETE FROM disponibilidad_veterinaria WHERE veterinaria_id = ?");
    $stmt->execute([$veterinariaId]);
    
    // Insertar nuevos horarios
    $stmt = $pdo->prepare("
        INSERT INTO disponibilidad_veterinaria 
        (veterinaria_id, dia_semana, hora_inicio, hora_fin, duracion_cita, cupo_maximo, activo)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($horarios as $horario) {
        $stmt->execute([
            $veterinariaId,
            $horario['dia_semana'],
            $horario['hora_inicio'],
            $horario['hora_fin'],
            $horario['duracion_cita'],
            $horario['cupo_maximo']
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuración guardada exitosamente'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
