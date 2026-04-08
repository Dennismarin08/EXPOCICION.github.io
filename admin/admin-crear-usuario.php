<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $premium = isset($_POST['premium']) ? 1 : 0;
    
    try {
        if (empty($nombre) || empty($email) || empty($password)) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado");
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, rol, premium, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$nombre, $email, $hashedPassword, $rol, $premium])) {
            $mensaje = "Usuario creado correctamente";
            // Limpiar campos si fue exitoso (opcional)
        } else {
            throw new Exception("Error al crear el usuario");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Admin RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Crear Nuevo Usuario</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <a href="admin-usuarios.php">Usuarios</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Crear</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-outline" onclick="window.location.href='admin-usuarios.php'">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3>Datos del Usuario</h3>
                </div>
                
                <div class="welcome-content" style="padding: 30px; color: var(--text-primary);">
                    <?php if ($mensaje): ?>
                        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Nombre Completo</label>
                            <input type="text" name="nombre" required style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Correo Electrónico</label>
                            <input type="email" name="email" required style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Contraseña Provisoria</label>
                            <input type="password" name="password" required style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                            <small style="color:#64748b;">El usuario podrá cambiarla al iniciar sesión.</small>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600;">Rol del Sistema</label>
                            <select name="rol" style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                                <option value="usuario">Usuario Estándar</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 30px; display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="premium" id="premiumCheckbox" style="width:20px; height:20px; cursor:pointer;">
                            <label for="premiumCheckbox" style="font-weight:600; cursor:pointer;">Activar Membresía Premium</label>
                        </div>
                        
                        <button type="submit" class="btn-create" style="width:100%; padding:15px; font-size:16px;">
                            <i class="fas fa-user-plus"></i> Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
