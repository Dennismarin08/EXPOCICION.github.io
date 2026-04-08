<?php
require_once 'db.php';
require_once 'includes/calendario_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

$userId = $_SESSION['user_id'];
$tipo = $_POST['tipo'] ?? 'todos'; // citas, vacunas, recordatorios, todos

try {
    // Obtener recordatorios con filtro
    $query = "
        SELECT 
            rp.id, rp.tipo, rp.descripcion, rp.fecha_programada,
            m.nombre as mascota,
            CASE 
                WHEN rp.tipo = 'rutina_diaria' THEN 'fa-heart'
                WHEN rp.tipo = 'rutina_semanal' THEN 'fa-calendar-week'
                WHEN rp.tipo = 'rutina_mensual' THEN 'fa-calendar'
                ELSE 'fa-bell'
            END as icono,
            CASE 
                WHEN rp.tipo = 'rutina_diaria' THEN rp.descripcion
                WHEN rp.tipo = 'rutina_semanal' THEN 'Rutina Semanal: ' . rp.descripcion
                WHEN rp.tipo = 'rutina_mensual' THEN 'Rutina Mensual: ' . rp.descripcion
                ELSE rp.descripcion
            END as titulo
        FROM recordatorios_plan rp
        JOIN planes_salud ps ON rp.plan_id = ps.id
        JOIN mascotas m ON ps.mascota_id = m.id
        WHERE m.user_id = ? 
        AND rp.fecha_programada >= DATE(NOW())
    ";
    
    $params = [$userId];
    
    // Aplicar filtro por tipo
    if ($tipo !== 'todos') {
        if ($tipo === 'citas') {
            // Las citas vienen de otra tabla, pero por ahora mostramos rutinas diarias
            $query .= " AND rp.tipo = 'rutina_diaria'";
        } elseif ($tipo === 'vacunas') {
            $query .= " AND rp.tipo = 'vacuna'";
        } elseif ($tipo === 'recordatorios') {
            $query .= " AND rp.tipo NOT IN ('rutina_diaria', 'rutina_semanal', 'rutina_mensual')";
        }
    }
    
    $query .= " ORDER BY rp.fecha_programada ASC LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $recordatorios = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'recordatorios' => $recordatorios,
        'count' => count($recordatorios)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
