<?php
require_once 'db.php';
require_once 'puntos-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$mascotaId = $_GET['id'] ?? null;

if (!$mascotaId) {
    header('Location: mascotas.php');
    exit;
}

// Obtener datos de la mascota
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id = ? AND user_id = ?");
$stmt->execute([$mascotaId, $userId]);
$mascota = $stmt->fetch();

if (!$mascota) {
    header('Location: mascotas.php');
    exit;
}

$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');
$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $raza = $_POST['raza'] ?? '';
    $edad = $_POST['edad'] ?? '';
    $peso = $_POST['peso'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $color = $_POST['color'] ?? '';
    
    if ($nombre && $raza) {
        try {
            $stmt = $pdo->prepare("UPDATE mascotas SET nombre = ?, raza = ?, edad = ?, peso = ?, sexo = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$nombre, $raza, $edad, $peso, $sexo, $color, $mascotaId, $userId]);
            $mensaje = 'Datos actualizados correctamente';
            
            // Recargar datos
            $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id = ?");
            $stmt->execute([$mascotaId]);
            $mascota = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor completa todos los campos requeridos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?php echo htmlspecialchars($mascota['nombre']); ?> - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #334155; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px; background: #f8fafc; color: #1e293b; }
        .form-control:focus { outline: none; border-color: #667eea; background: white; }
        .form-row { display: flex; gap: 20px; }
        .form-col { flex: 1; }
        
        .photo-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .photo-upload-area:hover {
            border-color: #667eea;
            background: #f1f5f9;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 4px solid #667eea;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Editar Perfil de <?php echo htmlspecialchars($mascota['nombre']); ?> ✏️</h1>
                <div class="breadcrumb">
                    <span>Mascotas</span>
                    <i class="fas fa-chevron-right"></i>
                    <span><?php echo htmlspecialchars($mascota['nombre']); ?></span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Editar</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="row">
                <div class="col-8" style="margin: 0 auto; float: none;">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Foto de Perfil -->
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h3>Foto de Perfil</h3>
                        </div>
                        <div style="padding: 30px;">
                            <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                                <?php if (!empty($mascota['foto_perfil'])):
                                    $fotoPreview = htmlspecialchars($mascota['foto_perfil']);
                                    if (strpos($fotoPreview, 'uploads/') !== 0) $fotoPreview = 'uploads/' . $fotoPreview;
                                ?>
                                    <img src="<?php echo $fotoPreview; ?>" 
                                         alt="<?php echo htmlspecialchars($mascota['nombre']); ?>"
                                         class="photo-preview"
                                         id="photoPreview">
                                <?php else: ?>
                                    <div style="font-size: 60px; color: #cbd5e1; margin-bottom: 15px;">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                <?php endif; ?>
                                <p style="color: #64748b; margin: 0;">
                                    <strong>Haz clic para subir una foto</strong><br>
                                    <small>JPG, PNG o GIF (máx. 2MB)</small>
                                </p>
                            </div>
                            <input type="file" id="photoInput" accept="image/*" style="display: none;" onchange="uploadPhoto(this)">
                        </div>
                    </div>
                    
                    <!-- Datos Básicos -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Datos Básicos</h3>
                        </div>
                        <div style="padding: 30px;">
                            <form method="POST" action="">
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Nombre *</label>
                                            <input type="text" name="nombre" class="form-control" 
                                                   value="<?php echo htmlspecialchars($mascota['nombre']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Raza *</label>
                                            <input type="text" name="raza" class="form-control" 
                                                   value="<?php echo htmlspecialchars($mascota['raza']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Edad (años)</label>
                                            <input type="number" name="edad" class="form-control" step="0.1"
                                                   value="<?php echo $mascota['edad']; ?>">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Peso (kg)</label>
                                            <input type="number" name="peso" class="form-control" step="0.1"
                                                   value="<?php echo $mascota['peso']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Sexo</label>
                                            <select name="sexo" class="form-control">
                                                <option value="macho" <?php echo $mascota['sexo'] === 'macho' ? 'selected' : ''; ?>>Macho</option>
                                                <option value="hembra" <?php echo $mascota['sexo'] === 'hembra' ? 'selected' : ''; ?>>Hembra</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Color</label>
                                            <input type="text" name="color" class="form-control" 
                                                   value="<?php echo htmlspecialchars($mascota['color'] ?? ''); ?>" 
                                                   placeholder="Ej: Café, Negro, Blanco">
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 30px; text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                                    <a href="perfil-mascota.php?id=<?php echo $mascota['id']; ?>" 
                                       class="btn-cancel" 
                                       style="padding: 12px 24px; text-decoration: none; display: inline-block;">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn-submit">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function uploadPhoto(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validar tamaño (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('❌ El archivo es muy grande. Máximo 2MB');
                    return;
                }
                
                // Validar tipo
                if (!file.type.match('image.*')) {
                    alert('❌ Por favor selecciona una imagen válida');
                    return;
                }
                
                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const uploadArea = document.querySelector('.photo-upload-area');
                        uploadArea.innerHTML = `
                            <img src="${e.target.result}" class="photo-preview" id="photoPreview">
                            <p style="color: #64748b; margin: 0;">
                                <strong>Subiendo foto...</strong>
                            </p>
                        `;
                    }
                };
                reader.readAsDataURL(file);
                
                // Subir archivo
                const formData = new FormData();
                formData.append('photo', file);
                formData.append('mascota_id', <?php echo $mascota['id']; ?>);
                
                fetch('ajax-upload-pet-photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Foto actualizada correctamente');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('Error al subir la foto');
                });
            }
        }
    </script>
</body>
</html>
