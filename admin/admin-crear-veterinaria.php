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
    $nombre_local = trim($_POST['nombre_local']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $descripcion = trim($_POST['descripcion']);
    
    try {
        if (empty($nombre) || empty($email) || empty($password) || empty($nombre_local)) {
            throw new Exception("Los campos marcados con * son obligatorios");
        }
        
        $pdo->beginTransaction();
        
        // 1. Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado");
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. Insertar Usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, telefono, password, rol, premium, created_at) 
            VALUES (?, ?, ?, ?, 'veterinaria', 0, NOW())
        ");
        $stmt->execute([$nombre, $email, $telefono, $hashedPassword]);
        $userId = $pdo->lastInsertId();
        
        // 3. Insertar Aliado
        $stmt = $pdo->prepare("
            INSERT INTO aliados (usuario_id, tipo, nombre_local, direccion, telefono, descripcion, activo, created_at) 
            VALUES (?, 'veterinaria', ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$userId, $nombre_local, $direccion, $telefono, $descripcion]);
        
        $pdo->commit();
        $mensaje = "Veterinaria creada correctamente. ID Usuario: $userId";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Veterinaria - Admin RUGAL</title>
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
                <h1 class="page-title">Crear Nueva Veterinaria</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <a href="admin-aliados.php">Aliados</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Crear Veterinaria</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-outline" onclick="window.location.href='admin-aliados.php'">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card" style="max-width: 800px; margin: 0 auto;">
                <div class="card-header">
                    <h3>Datos de la Veterinaria</h3>
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
                        <div class="row">
                            <div class="col-6">
                                <h4 style="margin-bottom:15px; color:var(--admin-accent);">Información de Acceso</h4>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Nombre del Contacto *</label>
                                    <input type="text" name="nombre" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Email de Acceso *</label>
                                    <input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Contraseña *</label>
                                    <input type="password" name="password" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Teléfono de Contacto</label>
                                    <input type="text" name="telefono" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <h4 style="margin-bottom:15px; color:var(--admin-accent);">Información del Local</h4>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Nombre Comercial *</label>
                                    <input type="text" name="nombre_local" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Dirección Física</label>
                                    <input type="text" name="direccion" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Descripción / Bio</label>
                                    <textarea name="descripcion" rows="4" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; resize:none;"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align:right; margin-top:20px;">
                            <button type="submit" class="btn-create" style="padding:15px 30px; font-size:16px;">
                                <i class="fas fa-hospital"></i> Registrar Veterinaria
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
