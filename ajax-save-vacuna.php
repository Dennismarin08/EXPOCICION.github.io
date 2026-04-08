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
$nombre = $_POST['nombre'];
$fecha = $_POST['fecha'];
$proxima = !empty($_POST['proxima_fecha']) ? $_POST['proxima_fecha'] : null;
$veterinaria = !empty($_POST['veterinaria_id']) ? $_POST['veterinaria_id'] : null;

try {
    if (guardarVacuna($mascotaId, $nombre, $fecha, $proxima, $veterinaria)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
