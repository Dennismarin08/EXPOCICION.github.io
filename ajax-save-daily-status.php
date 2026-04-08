<?php
session_start();
require_once 'db.php';
require_once 'includes/salud_functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Acceso denegado');
}

$userId = $_SESSION['user_id'];
$mascotaId = $_POST['mascota_id'] ?? null;
$estado = $_POST['estado'] ?? null;

if (!$mascotaId || !$estado) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

if (guardarEstadoDiario($userId, $mascotaId, $estado)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar o ya registrado']);
}
