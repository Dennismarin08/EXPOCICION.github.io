<?php
require_once 'db.php';
require_once 'includes/planes_salud_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$mascotaId = $_POST['mascota_id'];
$peso = $_POST['peso'];
$fecha = $_POST['fecha'];
$notas = $_POST['notas'] ?? '';

try {
    // 1. Guardar historial
    if (guardarPeso($mascotaId, $peso, $fecha, $notas)) {
        // 2. Actualizar peso actual en tabla mascotas
        $stmt = $pdo->prepare("UPDATE mascotas SET peso = ? WHERE id = ?");
        $stmt->execute([$peso, $mascotaId]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
