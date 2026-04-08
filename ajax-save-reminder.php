<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();
require_once 'db.php';
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$mascotaId = $_POST['mascota_id'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$fecha = $_POST['fecha'] ?? '';

if (empty($mascotaId) || empty($titulo) || empty($fecha)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

try {
    // Insertar en la tabla recordatorios
    $sql = "INSERT INTO recordatorios (user_id, mascota_id, titulo, fecha_programada, estado, created_at) VALUES (?, ?, ?, ?, 'pendiente', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $mascotaId, $titulo, $fecha]);
    
    echo json_encode(['success' => true, 'message' => 'Recordatorio guardado']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
