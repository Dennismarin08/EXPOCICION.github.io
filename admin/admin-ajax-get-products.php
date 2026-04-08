<?php
// c:\wamp64\www\RUGAL-OFF\admin\ajax-get-products.php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Verificar sesión admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$tipo = $_GET['tipo'] ?? ''; // veterinaria, tienda
$ids = $_GET['ids'] ?? ''; // IDs separados por coma (ej: 1,5,8)
$itemType = $_GET['item_type'] ?? 'producto'; // producto, servicio

if (!$tipo) { echo json_encode([]); exit; }

try {
    $results = [];
    
    // Sanitizar IDs para seguridad
    $idsArray = array_filter(explode(',', $ids), 'is_numeric');
    $idsClean = implode(',', $idsArray);
    
    // Lógica para SERVICIOS (Solo Veterinarias)
    if ($itemType === 'servicio') {
        if ($tipo === 'veterinaria') {
            $sql = "SELECT s.id, s.nombre, s.precio, s.descripcion, '' as imagen, s.veterinaria_id as aliado_id, a.nombre_local as aliado_nombre 
                    FROM servicios_veterinaria s
                    JOIN aliados a ON s.veterinaria_id = a.id
                    WHERE s.activo = 1";
            
            if (!empty($idsClean)) {
                $sql .= " AND s.veterinaria_id IN ($idsClean)";
            }
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } 
    // Lógica para PRODUCTOS (Tiendas o Veterinarias)
    else {
        $table = ($tipo === 'veterinaria') ? 'productos_veterinaria' : 'productos_tienda';
        $colId = ($tipo === 'veterinaria') ? 'veterinaria_id' : 'tienda_id';
        
        // Verificar que la tabla existe para evitar errores
        try {
            $sql = "SELECT p.id, p.nombre, p.precio, p.descripcion, p.imagen, p.$colId as aliado_id, a.nombre_local as aliado_nombre 
                    FROM $table p
                    JOIN aliados a ON p.$colId = a.id
                    WHERE p.activo = 1";
            
            if (!empty($idsClean)) {
                $sql .= " AND p.$colId IN ($idsClean)";
            }
            
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $results = [];
        }
        
        // Ajustar rutas de imagen para que se vean bien en el admin
        foreach ($results as &$row) {
            if (!empty($row['imagen'])) {
                $img = $row['imagen'];
                
                // Si la ruta no tiene 'uploads/', asumimos que es solo el nombre del archivo
                if (strpos($img, 'uploads/') === false) {
                    $folder = ($tipo == 'veterinaria') ? 'productos_vet' : 'tienda';
                    $img = "uploads/$folder/" . $img;
                }
                
                // Limpiar rutas relativas sucias
                $img = str_replace('../', '', $img);
                
                // Ruta relativa desde la carpeta admin/ hacia la raíz
                $row['imagen_url'] = '../' . $img;
            } else {
                $row['imagen_url'] = '';
            }
        }
    }
    
    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
