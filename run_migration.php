<?php
require_once 'db.php';
try {
    $sql = file_get_contents(__DIR__ . '/sql/migration_aliados_verificacion.sql');
    // Remove comments and split by semicolon
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $q) {
        if (!empty($q) && strpos($q, '--') !== 0) {
            $pdo->exec($q);
        }
    }
    echo 'Migration OK';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
