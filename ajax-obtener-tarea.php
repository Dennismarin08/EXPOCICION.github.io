<?php
require_once 'db.php';
// session_start(); // Already in db.php

header('Content-Type: application/json');

ob_start(); // Start buffering to catch any prior output
ob_clean(); // Clean it

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'obtener_tarea') {
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID no especificado');
        
        $stmt = $pdo->prepare("SELECT * FROM tareas_comunidad WHERE id = ? AND activa = 1");
        $stmt->execute([$id]);
        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tarea) {
            throw new Exception('Tarea no encontrada o inactiva');
        }
        
        echo json_encode(['success' => true, 'tarea' => $tarea]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
