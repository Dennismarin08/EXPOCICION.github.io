<?php
require_once 'db.php';

try {
    echo "Starting migration...<br>";
    
    // 1. Update tareas_comunidad
    $cols = $pdo->query("SHOW COLUMNS FROM tareas_comunidad LIKE 'tipo_acceso'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE tareas_comunidad ADD COLUMN tipo_acceso ENUM('free', 'premium') DEFAULT 'free' AFTER tipo");
        echo "- Added 'tipo_acceso' to tareas_comunidad<br>";
    } else {
        echo "- 'tipo_acceso' already exists in tareas_comunidad<br>";
    }

    // 2. Update recompensas
    $cols = $pdo->query("SHOW COLUMNS FROM recompensas LIKE 'tipo_acceso'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE recompensas ADD COLUMN tipo_acceso ENUM('free', 'premium') DEFAULT 'free' AFTER tipo");
        echo "- Added 'tipo_acceso' to recompensas<br>";
    } else {
        echo "- 'tipo_acceso' already exists in recompensas<br>";
    }

    // 3. Ensure usuarios has points and premium (it should, but let's check)
    $cols = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'puntos'")->fetch();
    if (!$cols) {
         $pdo->exec("ALTER TABLE usuarios ADD COLUMN puntos INT DEFAULT 0, ADD COLUMN total_puntos_ganados INT DEFAULT 0");
         echo "- Added points columns to usuarios<br>";
    }
    
    $cols = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'premium'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN premium TINYINT DEFAULT 0");
        echo "- Added premium column to usuarios<br>";
    }

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
