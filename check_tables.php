<?php
/**
 * Quick check - verify database tables
 */
require_once 'db.php';

echo "<h2>Database Tables Check</h2><pre>";

try {
    // Check user_activity_log
    echo "1. Checking user_activity_log table...\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_activity_log");
        $result = $stmt->fetch();
        echo "   ✓ Table exists ({$result['count']} records)\n";
    } catch (Exception $e) {
        echo "   ✗ Table NOT FOUND\n";
        echo "   Creating table...\n";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                activity_date DATE NOT NULL,
                activity_type VARCHAR(50) DEFAULT 'login',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_date (user_id, activity_date),
                FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ Table created!\n";
    }
    
    // Check planes_salud_mensual columns
    echo "\n2. Checking planes_salud_mensual columns...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM planes_salud_mensual");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required = ['health_score', 'last_health_update', 'nivel_alerta'];
    foreach ($required as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ $col exists\n";
        } else {
            echo "   ✗ $col MISSING - run migrate_health_score.php\n";
        }
    }
    
    // Check publicaciones table
    echo "\n3. Checking publicaciones table...\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM publicaciones");
        $result = $stmt->fetch();
        echo "   ✓ Table exists ({$result['count']} records)\n";
        
        // Show structure
        $stmt = $pdo->query("SHOW COLUMNS FROM publicaciones");
        echo "   Columns: ";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . ", ";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "   ✗ Table NOT FOUND: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Check Complete ===\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
