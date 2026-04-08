<?php
require_once __DIR__ . '/../db.php';

checkRole('veterinaria');

$userId = $_SESSION['user_id'];
$success = false;

// Obtener datos actuales
$stmt = $pdo->prepare("
    SELECT u.nombre as user_nombre, u.email, u.telefono as user_tel, a.*
    FROM usuarios u
    JOIN aliados a ON a.usuario_id = u.id
    WHERE u.id = ? AND a.tipo = 'veterinaria'
");
$stmt->execute([$userId]);
$perfil = $stmt->fetch() ?: [];

// Inicializar variables para evitar errores de null
$nombreLocal = $perfil['nombre_local'] ?? ($perfil['user_nombre'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreLocal = $_POST['nombre_local'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $precio = $_POST['precio_consulta'] ?? 0;
    $tel = $_POST['telefono'] ?? '';
    $cuenta = $_POST['cuenta_banco'] ?? '';
    $titular = $_POST['titular_cuenta'] ?? '';

    // --- Lógica para eliminar foto de galería ---
    $fotosGaleria = json_decode($perfil['fotos_verificacion'] ?? '[]', true);
    if (!is_array($fotosGaleria)) $fotosGaleria = [];

    if (isset($_POST['delete_photo_idx'])) {
        $idx = intval($_POST['delete_photo_idx']);
        if (isset($fotosGaleria[$idx])) {
            array_splice($fotosGaleria, $idx, 1);
        }
    }

    // --- Lógica de Fotos ---
    $fotoPrincipal = (!empty($perfil['foto_local']) && $perfil['foto_local'] !== '[]') ? $perfil['foto_local'] : '';

    // 1. Foto Principal
    if (isset($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] === 0) {
        $fn = uploadPhoto($_FILES['foto_principal']);
        if ($fn) $fotoPrincipal = 'uploads/' . $fn; // Guardar con prefijo para consistencia
    }

    // 2. Fotos del Local (Galería - hasta 3)
    if (isset($_FILES['fotos_galeria'])) {
        $files = $_FILES['fotos_galeria'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0) {
                $fileData = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $fn = uploadPhoto($fileData);
                if ($fn) $fotosGaleria[] = 'uploads/' . $fn; // Guardamos con prefijo para compatibilidad
            }
        }
        // Limitar a las últimas 3 fotos de galería
        $fotosGaleria = array_slice($fotosGaleria, -3);
    }

    // Actualizar Aliado
    $updateA = $pdo->prepare("
        UPDATE aliados 
        SET nombre_local = ?, descripcion = ?, direccion = ?, precio_consulta = ?, telefono = ?, foto_local = ?, fotos_verificacion = ?, cuenta_banco = ?, titular_cuenta = ?, activo = ?
        WHERE usuario_id = ? AND tipo = 'veterinaria'
    ");
    $updateA->execute([$nombreLocal, $descripcion, $direccion, $precio, $tel, $fotoPrincipal, json_encode($fotosGaleria), $cuenta, $titular, 1, $userId]);

    // Actualizar Usuario (básico)
    $updateU = $pdo->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
    $updateU->execute([$nombreLocal, $tel, $userId]);

    $success = true;
    // Recargar datos
    $stmt->execute([$userId]);
    $perfil = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil de Veterinaria | RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; }
        .perfil-container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .card-premium { background: white; border-radius: 24px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 30px; }
        .card-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: #7c3aed; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; color: #64748b; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input { width: 100%; padding: 14px 18px; border: 1.5px solid #e2e8f0; border-radius: 14px; font-size: 15px; transition: all 0.2s; background: #fcfdfe; }
        .form-input:focus { border-color: #7c3aed; background: white; outline: none; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
        
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 15px; }
        .photo-box { aspect-ratio: 1; border-radius: 16px; border: 2px dashed #cbd5e1; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; background: #f8fafc; transition: all 0.2s; }
        .photo-box:hover { border-color: #7c3aed; background: #f5f3ff; }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; }
        .main-photo-label { background: #7c3aed; color: white; padding: 4px 10px; border-radius: 6px; font-size: 10px; position: absolute; top: 8px; left: 8px; font-weight: 800; }
        .btn-del-photo { position: absolute; top: 8px; right: 8px; background: rgba(239, 68, 68, 0.9); color: white; border: none; border-radius: 8px; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; backdrop-filter: blur(4px); }
        .btn-upload-wrap { cursor: pointer; text-align: center; color: #94a3b8; font-weight: 600; font-size: 12px; }
        
        .btn-save { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white; border: none; padding: 16px; border-radius: 16px; font-weight: 800; cursor: pointer; width: 100%; font-size: 16px; margin-top: 10px; box-shadow: 0 10px 20px rgba(124,58,237,0.2); transition: transform 0.2s; }
        .btn-save:hover { transform: translateY(-2px); }
        .alert-success { background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #a7f3d0; text-align: center; }
        
        @media (max-width: 900px) { .perfil-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Configuración de Perfil</h1>
            </div>
        </header>

        <div class="content-wrapper">
            <?php if($success): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> ¡Perfil actualizado correctamente!</div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="perfil-container">
                <!-- Columna Izquierda -->
                <div>
                    <div class="card-premium">
                        <div class="card-title"><i class="fas fa-images"></i> Galería del Establecimiento</div>
                        <div class="photo-grid">
                            <!-- Foto Principal -->
                            <div class="photo-box" onclick="if(event.target.tagName !== 'I' && event.target.tagName !== 'BUTTON') document.getElementById('file_p').click()">
                                <span class="main-photo-label">PRINCIPAL</span> 
                                <?php if(!empty($perfil['foto_local']) && $perfil['foto_local'] != '[]'): ?>
                                    <img id="prev_p" src="<?php echo buildImgUrl($perfil['foto_local']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <div class="btn-upload-wrap" id="wrap_p">
                                        <i class="fas fa-camera fa-2x"></i><br>Subir Principal
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="file_p" name="foto_principal" hidden accept="image/*" onchange="previewMain(this)">
                            </div>

                            <!-- Fotos del Local -->
                            <?php 
                            $galeria = json_decode($perfil['fotos_verificacion'] ?? '[]', true);
                            for($i=0; $i<3; $i++): 
                            ?>
                            <div class="photo-box">
                                <?php if(isset($galeria[$i])): ?>
                                    <img src="<?php echo buildImgUrl($galeria[$i]); ?>">
                                    <button type="submit" name="delete_photo_idx" value="<?php echo $i; ?>" class="btn-del-photo"><i class="fas fa-trash-alt"></i></button>
                                <?php else: ?>
                                    <div class="btn-upload-wrap" onclick="document.getElementById('file_g').click()">
                                        <i class="fas fa-plus fa-2x"></i><br>Agregar Local
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                            <input type="file" id="file_g" name="fotos_galeria[]" hidden multiple accept="image/*">
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 15px;">* Recomendado: 1 foto principal de fachada y 3 fotos de interiores/consultorios.</p>
                    </div>

                    <div class="card-premium">
                        <div class="card-title"><i class="fas fa-info-circle"></i> Datos de la Veterinaria</div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Nombre del Establecimiento</label>
                                <input type="text" name="nombre_local" class="form-input" value="<?php echo htmlspecialchars($perfil['nombre_local'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Precio Consulta General ($)</label>
                                <input type="number" name="precio_consulta" class="form-input" value="<?php echo intval($perfil['precio_consulta'] ?? 0); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción Profesional</label>
                            <textarea name="descripcion" class="form-input" rows="4" required placeholder="Escribe aquí tu experiencia, especialidades y servicios ofrecidos..."><?php echo htmlspecialchars($perfil['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Dirección Física</label>
                            <input type="text" name="direccion" class="form-input" value="<?php echo htmlspecialchars($perfil['direccion'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div>
                    <div class="card-premium" style="border-top: 4px solid #10b981;">
                        <div class="card-title"><i class="fas fa-university"></i> Datos de Pago</div>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Esta información se mostrará a los clientes para el pago de anticipos de citas.</p>
                        
                        <div class="form-group">
                            <label>Entidad o Número de Cuenta</label>
                            <input type="text" name="cuenta_banco" class="form-input" value="<?php echo htmlspecialchars($perfil['cuenta_banco'] ?? ''); ?>" placeholder="Ej: Nequi 300... o Ahorros Bancolombia #...">
                        </div>

                        <div class="form-group">
                            <label>Nombre del Titular</label>
                            <input type="text" name="titular_cuenta" class="form-input" value="<?php echo htmlspecialchars($perfil['titular_cuenta'] ?? ''); ?>" placeholder="Nombre completo">
                        </div>
                    </div>

                    <div class="card-premium">
                        <div class="card-title"><i class="fas fa-headset"></i> Contacto Público</div>
                        <div class="form-group">
                            <label>Teléfono de Atención</label>
                            <input type="text" name="telefono" class="form-input" value="<?php echo htmlspecialchars($perfil['telefono'] ?? ($perfil['user_tel'] ?? '')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email (No editable)</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($perfil['email'] ?? ''); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function previewMain(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById('prev_p');
                if(img) { img.src = e.target.result; }
                else {
                    document.getElementById('wrap_p').outerHTML = `<img id="prev_p" src="${e.target.result}">`;
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>