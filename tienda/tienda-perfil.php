<?php
require_once __DIR__ . '/../db.php';
checkRole('tienda');

$userId = $_SESSION['user_id'];
$msg = '';
$msgType = '';

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_local'];
    $desc = $_POST['descripcion'];
    $dir = $_POST['direccion'];
    $tipo = $_POST['tipo_alimento'];
    $banco = $_POST['cuenta_banco'];
    $titular = $_POST['titular_cuenta'];
    
    // Manejo de Foto
    $fotoUrl = null;
    if (isset($_FILES['foto_local']) && $_FILES['foto_local']['error'] === 0) {
        $uploadDir = __DIR__ . '/../tienda/uploads/perfil/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['foto_local']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('tienda_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['foto_local']['tmp_name'], $targetPath)) {
            $fotoUrl = 'uploads/perfil/' . $filename;
        }
    }

    // UPDATE con o sin foto
    if ($fotoUrl) {
        $stmt = $pdo->prepare("UPDATE aliados SET nombre_local = ?, descripcion = ?, direccion = ?, tipo_alimento = ?, cuenta_banco = ?, titular_cuenta = ?, foto_local = ? WHERE usuario_id = ? AND tipo = 'tienda'");
        $stmt->execute([$nombre, $desc, $dir, $tipo, $banco, $titular, $fotoUrl, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE aliados SET nombre_local = ?, descripcion = ?, direccion = ?, tipo_alimento = ?, cuenta_banco = ?, titular_cuenta = ? WHERE usuario_id = ? AND tipo = 'tienda'");
        $stmt->execute([$nombre, $desc, $dir, $tipo, $banco, $titular, $userId]);
    }
    
    if ($stmt->rowCount() > 0 || !$fotoUrl) {
        $msg = "Perfil actualizado correctamente";
        $msgType = "success";
    } else {
        $msg = "Error al actualizar perfil: " . implode(" ", $stmt->errorInfo());
        $msgType = "error";
    }
}

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM aliados WHERE usuario_id = ? AND tipo = 'tienda'");
$stmt->execute([$userId]);
$tienda = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            max-width: 1200px;
        }
        
        @media (max-width: 900px) { .profile-container { grid-template-columns: 1fr; } }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .profile-name { font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
        .profile-type { color: #ff7e5f; font-weight: 600; margin-bottom: 20px; }
        
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .section-header {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Configuración de Perfil</h1>
        </header>

        <div class="content-wrapper">
            <?php if ($msg): ?>
                <div class="alert <?php echo $msgType == 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="profile-container">
                <!-- Sidebar Profile Card -->
                <div class="profile-card">
                    <img src="<?php echo !empty($tienda['foto_local']) ? BASE_URL . '/tienda/' . htmlspecialchars($tienda['foto_local']) : BASE_URL . '/assets/default-store.svg'; ?>" class="profile-avatar" id="avatarPreview" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48Y2lyY2xlIGN4PSIxMDAiIGN5PSIxMDAiIHI9IjEwMCIgZmlsbD0iI2ZmN2U1ZiIvPjx0ZXh0IHg9IjEwMCIgeT0iMTIwIiBmb250LXNpemU9IjgwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSJ3aGl0ZSI+8J+Uls8A/jwvdGV4dD48L3N2Zz4='">
                    <div class="profile-name"><?php echo htmlspecialchars($tienda['nombre_local']); ?></div>
                    <div class="profile-type">Tienda Aliada</div>
                    
                    <label for="fotoUpload" style="cursor:pointer; background:#f1f5f9; padding:8px 15px; border-radius:8px; font-weight:600; display:inline-block;">
                        <i class="fas fa-camera"></i> Cambiar Foto
                    </label>
                    <input type="file" name="foto_local" id="fotoUpload" style="display:none;" accept="image/*" onchange="previewImage(this)">
                </div>
                
                <!-- Main Form -->
                <div class="profile-main">
                    <div class="form-section">
                        <div class="section-header">Información del Negocio</div>
                        
                        <label>Nombre de la Tienda</label>
                        <input type="text" name="nombre_local" value="<?php echo htmlspecialchars($tienda['nombre_local']); ?>" class="form-input" required>
                        
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-input" rows="4"><?php echo htmlspecialchars($tienda['descripcion']); ?></textarea>
                        
                        <label>Dirección</label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($tienda['direccion'] ?? ''); ?>" class="form-input" placeholder="Dirección física">
                        
                        <label>Especialidad / Tipo de Productos</label>
                        <input type="text" name="tipo_alimento" value="<?php echo htmlspecialchars($tienda['tipo_alimento'] ?? ''); ?>" class="form-input" placeholder="Ej: Alimentos, Accesorios, Juguetes">
                        
                        <div style="margin-top: 20px;">
                            <a href="tienda-horarios.php" class="btn-save" style="text-decoration: none; background: #64748b; padding: 10px 20px; font-size: 14px;">
                                <i class="fas fa-clock"></i> Configurar Horarios de Atención
                            </a>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">Información Bancaria</div>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                            <div>
                                <label>Banco y Cuenta</label>
                                <input type="text" name="cuenta_banco" value="<?php echo htmlspecialchars($tienda['cuenta_banco'] ?? ''); ?>" class="form-input">
                            </div>
                            <div>
                                <label>Titular</label>
                                <input type="text" name="titular_cuenta" value="<?php echo htmlspecialchars($tienda['titular_cuenta'] ?? ''); ?>" class="form-input">
                            </div>
                        </div>
                        
                        <div style="text-align:right;">
                            <button type="submit" class="btn-save">Guardar Cambios</button>
                        </div>
                    </div>
                </div>
            </form>
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
