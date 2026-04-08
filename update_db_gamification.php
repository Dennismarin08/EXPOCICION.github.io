<?php
require_once 'db.php';

try {
    echo "Iniciando actualización de base de datos para Gamificación...\n";

    // Columnas a agregar en tabla usuarios
    $columns = [
        'nivel_numerico' => "ALTER TABLE usuarios ADD COLUMN nivel_numerico INT DEFAULT 1",
        'experiencia_nivel' => "ALTER TABLE usuarios ADD COLUMN experiencia_nivel INT DEFAULT 0",
        'racha_dias' => "ALTER TABLE usuarios ADD COLUMN racha_dias INT DEFAULT 0",
        'ultima_tarea_fecha' => "ALTER TABLE usuarios ADD COLUMN ultima_tarea_fecha DATE NULL"
    ];

    foreach ($columns as $col => $sql) {
        try {
            // Verificar si existe
            $check = $pdo->query("SHOW COLUMNS FROM usuarios LIKE '$col'");
            if ($check->rowCount() == 0) {
                $pdo->exec($sql);
                echo "Columna '$col' agregada correctamente.\n";
            } else {
                echo "Columna '$col' ya existe.\n";
            }
        } catch (PDOException $e) {
            echo "Error al agregar '$col': " . $e->getMessage() . "\n";
        }
    }

    echo "Actualización completada.\n";

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
?>
