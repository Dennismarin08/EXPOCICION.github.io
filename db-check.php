<?php
// db-check.php
$_SERVER['SERVER_NAME'] = 'localhost'; // Fix config warning
require_once 'db.php';

try {
    $stmt = $pdo->query("DESCRIBE citas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = "Columns in citas table:\n";
    foreach ($columns as $col) {
        $out .= $col['Field'] . " - " . $col['Type'] . "\n";
    }
    file_put_contents('citas_schema.txt', $out);
    echo "Done";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
