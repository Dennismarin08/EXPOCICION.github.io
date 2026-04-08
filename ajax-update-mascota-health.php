<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$mascotaId = $_POST['mascota_id'] ?? null;
if (!$mascotaId) {
    echo json_encode(['success' => false, 'error' => 'Falta ID de mascota']);
    exit;
}

$esterilizado = $_POST['esterilizado'] ?? 0;
$tamano = $_POST['tamano'] ?? 'mediano';
$vive_en = $_POST['vive_en'] ?? 'casa';
$nivel = $_POST['nivel_actividad'] ?? 'medio';
$alimentacion = $_POST['alimentacion_actual'] ?? '';

try {
    // **CORRECCIÓN DE SEGURIDAD**
    // Se añade la condición AND user_id = ? para asegurar que el usuario
    // solo pueda modificar sus propias mascotas.
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("UPDATE mascotas SET esterilizado = ?, tamano = ?, vive_en = ?, nivel_actividad = ?, alimentacion_actual = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$esterilizado, $tamano, $vive_en, $nivel, $alimentacion, $mascotaId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Mascota no encontrada o no autorizado.']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error en ajax-update-mascota-health: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error en el servidor.']);
}
?>
