<?php
// reset-password.php - RESETEAR CONTRASEÑA DEL ADMIN
require_once 'db.php';

// Contraseña nueva para el admin
$nuevaContraseña = "Admin1234";
$hash = password_hash($nuevaContraseña, PASSWORD_DEFAULT);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Resetear Contraseña - RUGAL</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Resetear Contraseña del Admin</h1>";

try {
    // 1. Actualizar contraseña del admin
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, rol = 'admin' WHERE email = 'admin@test.com'");
    $stmt->execute([$hash]);
    
    echo "<div class='success'>✅ <strong>¡Contraseña actualizada correctamente!</strong></div>";
    echo "<p><strong>Email:</strong> admin@test.com</p>";
    echo "<p><strong>Nueva contraseña:</strong> <span class='success'>$nuevaContraseña</span></p>";
    echo "<p><strong>Hash generado:</strong> $hash</p>";
    
    // 2. Verificar que se actualizó
    $stmt = $pdo->query("SELECT id, nombre, email, rol, LEFT(password, 30) as password_hash FROM usuarios WHERE email = 'admin@test.com'");
    $admin = $stmt->fetch();
    
    echo "<h3>Información del admin actualizada:</h3>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
    // 3. Verificar password_verify
    echo "<h3>Verificación de login:</h3>";
    if (password_verify($nuevaContraseña, $hash)) {
        echo "<div class='success'>✅ Password verify funciona correctamente</div>";
    } else {
        echo "<div class='error'>❌ Error en password verify</div>";
    }
    
    // 4. Mostrar todos los usuarios
    echo "<h3>Usuarios en la base de datos:</h3>";
    $stmt = $pdo->query("SELECT id, nombre, email, rol, DATE(created_at) as fecha FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Fecha</th></tr>";
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nombre']}</td>";
        echo "<td>{$usuario['email']}</td>";
        echo "<td><strong>{$usuario['rol']}</strong></td>";
        echo "<td>{$usuario['fecha']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><hr><br>";
    echo "<a href='login.php' class='btn'>Ir al Login</a>";
    echo "<a href='admin-dashboard.php' class='btn' style='background:#00b09b; margin-left:10px;'>Ir al Admin Dashboard</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>