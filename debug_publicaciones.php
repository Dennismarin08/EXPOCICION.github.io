<?php
require_once 'db.php';

echo "<h2>Debug Publicaciones</h2>";
echo "<pre>";

try {
    // Get mascota_id from URL
    $mascotaId = $_GET['id'] ?? 1;
    
    echo "Buscando publicaciones para mascota ID: $mascotaId\n\n";
    
    // Check if publicaciones table exists
    echo "1. Verificando tabla publicaciones...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'publicaciones'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabla publicaciones existe\n\n";
        
        // Show table structure
        echo "2. Estructura de la tabla:\n";
        $stmt = $pdo->query("DESCRIBE publicaciones");
        while ($row = $stmt->fetch()) {
            echo "   - {$row['Field']} ({$row['Type']})\n";
        }
        
        // Count total publications
        echo "\n3. Total de publicaciones en la tabla:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM publicaciones");
        $result = $stmt->fetch();
        echo "   Total: {$result['total']} publicaciones\n\n";
        
        // Get publications for this pet
        echo "4. Publicaciones de esta mascota:\n";
        $stmt = $pdo->prepare("
            SELECT p.*, u.nombre as autor_nombre 
            FROM publicaciones p
            LEFT JOIN usuarios u ON p.user_id = u.id
            WHERE p.mascota_id = ?
            ORDER BY p.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$mascotaId]);
        $pubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($pubs) > 0) {
            echo "   Encontradas: " . count($pubs) . " publicaciones\n\n";
            foreach ($pubs as $i => $pub) {
                echo "   Publicación #" . ($i + 1) . ":\n";
                echo "   - ID: {$pub['id']}\n";
                echo "   - Contenido: " . substr($pub['contenido'], 0, 50) . "...\n";
                echo "   - Imagen: " . ($pub['imagen'] ?? 'Sin imagen') . "\n";
                echo "   - Fecha: " . ($pub['created_at'] ?? 'Sin fecha') . "\n";
                echo "   - Autor: " . ($pub['autor_nombre'] ?? 'Desconocido') . "\n\n";
            }
        } else {
            echo "   ✗ No hay publicaciones para esta mascota\n";
            echo "   Verifica que mascota_id = $mascotaId tenga publicaciones\n";
        }
        
    } else {
        echo "✗ Tabla publicaciones NO EXISTE\n";
        echo "Necesitas crear la tabla primero\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
