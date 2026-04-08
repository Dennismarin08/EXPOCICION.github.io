<?php
require_once 'db.php';
require_once 'includes/check-auth.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_evento'] ?? '';
    $titulo = $_POST['titulo'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $mascota_id = $_POST['mascota_id'] ?? 0;

    if (empty($tipo) || empty($titulo) || empty($fecha) || !$mascota_id) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    try {
        if ($tipo === 'cita') {
            // Insert into citas
            $stmt = $pdo->prepare("INSERT INTO citas (user_id, mascota_id, motivo, fecha_hora, estado) VALUES (?, ?, ?, ?, 'pendiente')");
            $fecha_hora = $fecha . ' ' . ($hora ?: '00:00:00');
            $stmt->execute([$userId, $mascota_id, $titulo, $fecha_hora]);
        } elseif ($tipo === 'recordatorio') {
            // Insert into recordatorios
            $stmt = $pdo->prepare("INSERT INTO recordatorios (user_id, mascota_id, titulo, descripcion, fecha_programada, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$userId, $mascota_id, $titulo, $descripcion, $fecha]);
        } elseif (in_array($tipo, ['rutina_diaria', 'rutina_semanal', 'rutina_mensual', 'salud'])) {
            // For plan routines, add to recordatorios
            $stmt = $pdo->prepare("INSERT INTO recordatorios (user_id, mascota_id, titulo, descripcion, fecha_programada, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$userId, $mascota_id, $titulo, $descripcion, $fecha]);
        }

        echo json_encode(['success' => true, 'message' => 'Evento agregado exitosamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el evento: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
