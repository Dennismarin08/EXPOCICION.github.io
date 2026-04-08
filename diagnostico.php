<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico RUGAL</h1>";

try {
    require_once 'db.php';
    $pdo = getDBConnection();
    
    echo "<h2>✅ Conexión a base de datos exitosa</h2>";
    
    // Verificar historial_medico
    echo "<h3>📋 Estructura de tabla 'historial_medico':</h3>";
    $stmt = $pdo->query("DESCRIBE historial_medico");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Campo</th><th>Tipo</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
    }
    echo "</table>";

    // Verificar Conexión IA
    echo "<h2>🤖 Verificando Conexión IA</h2>";
    try {
        require_once 'chat-ia/CHAT-IA/config.php';
        if (isset($conn) && !$conn->connect_error) {
            echo "✅ Conexión a IA (ia_perros) exitosa<br>";
            $res = $conn->query("SELECT COUNT(*) as total FROM preguntas");
            $data = $res->fetch_assoc();
            echo "Total preguntas en IA: " . $data['total'] . "<br>";
            
            // Ver algunas preguntas
            echo "<h4>Últimas 5 preguntas en IA:</h4><ul>";
            $res = $conn->query("SELECT texto FROM preguntas ORDER BY id DESC LIMIT 5");
            while($row = $res->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['texto']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "❌ Falló conexión a IA<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error IA: " . $e->getMessage() . "<br>";
    }

    // Verificar sesión actual
    echo "<h3>🔐 Sesión actual:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
