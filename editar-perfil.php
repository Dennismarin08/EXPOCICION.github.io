<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$msg = '';
$msgType = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($nombre) || empty($email)) {
        $msg = 'Nombre y Email son obligatorios.';
        $msgType = 'error';
    } else {
        try {
            // Verificar email duplicado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception("El email ya está en uso por otro usuario.");
            }

            // Construir query base
            $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, ciudad = ?";
            $params = [$nombre, $email, $telefono, $ciudad];

            // Actualizar password si se proporcionó
            if (!empty($password)) {
                if ($password !== $confirmPassword) {
                    throw new Exception("Las contraseñas no coinciden.");
                }
                if (strlen($password) < 8) {
                    throw new Exception("La contraseña debe tener al menos 8 caracteres.");
                }
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            // Manejar foto de perfil
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadDir . $filename)) {
                    $sql .= ", foto_perfil = ?";
                    $params[] = $filename;
                }
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Actualizar sesión
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_email'] = $email;

            $msg = 'Perfil actualizado correctamente.';
            $msgType = 'success';

        } catch (Exception $e) {
            $msg = $e->getMessage();
            $msgType = 'error';
        }
    }
}

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-container { max-width: 800px; margin: 0 auto; }
        .profile-upload {
            display: flex; flex-direction: column; align-items: center; margin-bottom: 30px;
        }
        .avatar-preview {
            width: 120px; height: 120px; border-radius: 50%; object-fit: cover;
            border: 4px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px; background: #f8fafc;
        }
        .btn-upload {
            background: #e0e7ff; color: #4338ca; padding: 8px 16px; border-radius: 20px;
            font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-upload:hover { background: #c7d2fe; }
        
        .form-section {
            background: white; border-radius: 20px; padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .section-title {
            font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px;
            padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #64748b; font-size: 14px; }
        .form-input {
            width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px;
            font-size: 16px; transition: border-color 0.3s;
        }
        .form-input:focus { outline: none; border-color: #667eea; }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; padding: 15px 40px; border-radius: 12px;
            font-size: 16px; font-weight: bold; cursor: pointer; width: 100%;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: transform 0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Editar Perfil</h1>
                <div class="breadcrumb">
                    <a href="perfil.php" style="color: inherit; text-decoration: none;">Mi Perfil</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Editar</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="edit-container">
                <?php if ($msg): ?>
                    <div class="alert <?php echo $msgType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="profile-upload">
                            <?php 
                                $foto = $user['foto_perfil'] ? 'uploads/' . $user['foto_perfil'] : 'assets/images/default-user.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($foto); ?>" id="avatarPreview" class="avatar-preview" onerror="this.src='assets/images/default-user.png'">
                            <label for="fotoInput" class="btn-upload">
                                <i class="fas fa-camera"></i> Cambiar Foto
                            </label>
                            <input type="file" name="foto_perfil" id="fotoInput" style="display:none;" accept="image/*" onchange="previewImage(this)">
                        </div>

                        <div class="section-title">Información Personal</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-input" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-input" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-input" value="<?php echo htmlspecialchars($user['ciudad'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">Seguridad (Opcional)</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="password" class="form-input" placeholder="Dejar en blanco para no cambiar">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="confirm_password" class="form-input" placeholder="Repetir nueva contraseña">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>