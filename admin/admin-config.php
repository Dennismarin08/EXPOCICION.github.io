<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

// Cargar configuración actual
try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    $mensaje_error = "Error al cargar configuración: " . $e->getMessage();
}

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        foreach ($_POST as $clave => $valor) {
            // Ignorar campos que no son de configuración si los hubiera
            $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$clave, $valor, $valor]);
            $config[$clave] = $valor; // Actualizar localmente
        }
        $pdo->commit();
        $mensaje = "Configuración guardada correctamente";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje_error = "Error al guardar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Admin RUGAL</title>
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
                <h1 class="page-title">Configuración del Sistema</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Configuración</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php if (isset($mensaje)): ?>
                <div class="card" style="padding:15px; margin-bottom:20px; background:#dcfce7; border:1px solid #86efac; color:#166534;">
                    <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-sliders-h"></i> General</h3>
                            </div>
                            <div class="welcome-content" style="padding:20px; color:var(--text-primary);">
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Nombre del Sitio</label>
                                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($config['site_name'] ?? 'RUGAL'); ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Email de Soporte</label>
                                    <input type="email" name="support_email" value="<?php echo htmlspecialchars($config['support_email'] ?? 'soporte@rugal.com'); ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Modo Mantenimiento</label>
                                    <select name="maintenance_mode" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                        <option value="0" <?php echo ($config['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Desactivado</option>
                                        <option value="1" <?php echo ($config['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>Activado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-coins"></i> Gamificación</h3>
                            </div>
                            <div class="welcome-content" style="padding:20px; color:var(--text-primary);">
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Puntos Iniciales (Registro)</label>
                                    <input type="number" name="points_register" value="<?php echo htmlspecialchars($config['points_register'] ?? '100'); ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label style="display:block; margin-bottom:5px; font-weight:600;">Puntos por Post</label>
                                    <input type="number" name="points_post" value="<?php echo htmlspecialchars($config['points_post'] ?? '10'); ?>" class="form-control" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12" style="text-align:right;">
                        <button type="submit" class="btn-create" style="font-size:16px;">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
