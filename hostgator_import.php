<?php
/**
 * RUGAL - Importador de Base de Datos para HostGator
 * Este script ayuda a cargar el archivo SQL corregido directamente en el servidor.
 */

require_once 'db.php';

// Nombre del archivo SQL corregido
$sqlFile = 'rugal_db (3).sql';

if (!file_exists($sqlFile)) {
    die("Error: No se encontró el archivo $sqlFile en el directorio actual.");
}

echo "<h2>Iniciando Importación de Base de Datos</h2>";
echo "<p>Archivo: <strong>$sqlFile</strong></p>";

try {
    $pdo = getDBConnection();
    
    // Leer el contenido del archivo
    $sql = file_get_contents($sqlFile);
    
    // NOTA: Como el archivo usa DELIMITER $$, PDO no puede ejecutarlo todo de una vez.
    // Vamos a limpiar el archivo de DELIMITERs y separar las sentencias.
    
    // Eliminar comentarios de una línea (-- o #)
    $lines = explode("\n", $sql);
    $clean_sql = "";
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed != "" && strpos($trimmed, "--") !== 0 && strpos($trimmed, "#") !== 0) {
            $clean_sql .= $line . "\n";
        }
    }
    
    // Manejar el DELIMITER $$
    // Primero, separamos las partes que no usan $$ de las que sí
    $parts = preg_split('/\s*DELIMITER\s+\$\$\s*/i', $clean_sql);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($parts as $index => $part) {
        if (trim($part) == "") continue;
        
        // Si el índice es impar, es una sección que estaba entre DELIMITER $$ y DELIMITER ;
        // o simplemente después de un DELIMITER $$
        if ($index % 2 != 0) {
            // Buscamos el cierre del bloque de función/procedimiento
            $subparts = preg_split('/\s*DELIMITER\s+;\s*/i', $part);
            
            // La primera parte es el bloque con $$ (que ahora trataremos como uno solo)
            $routine_block = rtrim($subparts[0], "$$");
            
            try {
                $pdo->exec($routine_block);
                echo "<p style='color:green;'>✓ Rutina/Función creada correctamente.</p>";
                $success_count++;
            } catch (PDOException $e) {
                echo "<p style='color:red;'>✗ Error creando rutina: " . $e->getMessage() . "</p>";
                $error_count++;
            }
            
            // Si hay una segunda parte, son comandos normales
            if (isset($subparts[1]) && trim($subparts[1]) != "") {
                executeNormalSQL($subparts[1], $pdo, $success_count, $error_count);
            }
        } else {
            // Comandos normales separados por ;
            executeNormalSQL($part, $pdo, $success_count, $error_count);
        }
    }
    
    echo "<h3>Resumen de importación:</h3>";
    echo "<ul>
            <li>Sentencias exitosas: $success_count</li>
            <li>Sentencias con error: $error_count</li>
          </ul>";
    
    if ($error_count == 0) {
        echo "<p style='color:green; font-weight:bold;'>¡Importación completada con éxito!</p>";
        echo "<p>Ya puede eliminar este archivo (hostgator_import.php) por seguridad.</p>";
    } else {
        echo "<p style='color:orange;'>La importación terminó con algunos errores. Revise los mensajes anteriores.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red; font-weight:bold;'>Error crítico: " . $e->getMessage() . "</p>";
}

function executeNormalSQL($sql_block, $pdo, &$success, &$errors) {
    // Dividir por ; pero ignorar los que están dentro de comillas (simplificado)
    $queries = explode(";", $sql_block);
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query != "") {
            try {
                $pdo->exec($query);
                $success++;
            } catch (PDOException $e) {
                // Ignorar errores de "Table already exists" si se desea, 
                // pero aquí los mostramos para transparencia
                echo "<p style='color:red;'>Error en query: " . substr($query, 0, 50) . "... <br>Detalle: " . $e->getMessage() . "</p>";
                $errors++;
            }
        }
    }
}
