<?php
/**
 * Migration: Add health_score tracking and user activity log
 * Run this once to update the database schema
 */

require_once 'db.php';

try {
    echo "=== Starting Database Migration ===\n\n";
    
    // 1. Add health_score column to planes_salud_mensual
    echo "1. Checking health_score column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM planes_salud_mensual LIKE 'health_score'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE planes_salud_mensual 
            ADD COLUMN health_score INT DEFAULT 85 COMMENT 'Health score percentage (0-100)'
        ");
        echo "   ✓ health_score column added\n";
    } else {
        echo "   ✓ health_score column already exists\n";
    }
    echo "\n";
    
    // 2. Add last_updated column for tracking score changes
    echo "2. Checking last_health_update column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM planes_salud_mensual LIKE 'last_health_update'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE planes_salud_mensual 
            ADD COLUMN last_health_update TIMESTAMP NULL DEFAULT NULL COMMENT 'Last time health score was updated'
        ");
        echo "   ✓ last_health_update column added\n";
    } else {
        echo "   ✓ last_health_update column already exists\n";
    }
    echo "\n";
    
    // 3. Create user_activity_log table
    echo "3. Creating user_activity_log table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_date DATE NOT NULL,
            activity_type VARCHAR(50) DEFAULT 'login' COMMENT 'login, task_completed, plan_generated, etc',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, activity_date),
            KEY idx_user_date (user_id, activity_date),
            FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Track daily user activity for health score calculation'
    ");
    echo "   ✓ user_activity_log table created\n\n";
    
    // 4. Initialize health_score for existing plans
    echo "4. Initializing health_score for existing plans...\n";
    $stmt = $pdo->query("
        UPDATE planes_salud_mensual 
        SET health_score = CASE 
            WHEN nivel_alerta = 'verde' THEN 90
            WHEN nivel_alerta = 'amarillo' THEN 65
            WHEN nivel_alerta = 'rojo' THEN 35
            ELSE 85
        END,
        last_health_update = NOW()
        WHERE health_score IS NULL OR health_score = 0
    ");
    $updated = $stmt->rowCount();
    echo "   ✓ Updated $updated existing plans\n\n";
    
    echo "=== Migration Completed Successfully! ===\n";
    echo "\nNext steps:\n";
    echo "- Health scores are now persistent\n";
    echo "- User activity will be tracked automatically\n";
    echo "- Weekly improvements will be calculated based on 5/7 day activity\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}
