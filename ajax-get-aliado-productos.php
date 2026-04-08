<?php
require_once 'db.php';

header('Content-Type: application/json');

$type = $_GET['tipo'] ?? ''; // 'veterinaria' or 'tienda'
$itemType = $_GET['item_type'] ?? 'producto'; // 'producto' or 'servicio'
$ids = $_GET['ids'] ?? ''; // comma separated IDs

if (empty($type)) {
    echo json_encode([]);
    exit;
}

try {
    // Si es servicio, obtener de servicios_veterinaria
    if ($itemType === 'servicio' && $type === 'veterinaria') {
        $table = 'servicios_veterinaria';
        $fk = 'veterinaria_id';
        
        $sql = "SELECT id, nombre, precio, '' as imagen FROM $table WHERE activo = 1";
        $params = [];
        
        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $placeholders = implode(',', array_fill(0, count($idArray), '?'));
            $sql .= " AND $fk IN ($placeholders)";
            $params = $idArray;
        }
        
        $sql .= " ORDER BY nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    // Si es producto
    $table = ($type === 'veterinaria') ? 'productos_veterinaria' : 'productos_tienda';
    $fk = ($type === 'veterinaria') ? 'veterinaria_id' : 'tienda_id';
    
    // Agregar imagen según el tipo
    $sql = "SELECT id, nombre, precio, imagen FROM $table WHERE activo = 1";
    $params = [];
    
    if (!empty($ids)) {
        $idArray = explode(',', $ids);
        $placeholders = implode(',', array_fill(0, count($idArray), '?'));
        $sql .= " AND $fk IN ($placeholders)";
        $params = $idArray;
    }
    
    $sql .= " ORDER BY nombre ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalizar la ruta de la imagen para productos de veterinaria
    foreach ($results as &$row) {
        if (!empty($row['imagen']) && $type === 'veterinaria') {
            // Ya viene con la ruta completa desde la DB, solo verificamos
            $row['imagen'] = $row['imagen'];
        }
    }
    unset($row);
    
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
