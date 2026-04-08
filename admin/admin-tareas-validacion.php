<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../puntos-functions.php';

// Verificar sesión y rol de admin
checkRole('admin');

// 1. Obtener tareas PENDIENTES de validación
// (Solo las que requieren validación y no han sido aprobadas)
$stmt = $pdo->prepare("
    SELECT tc.*, t.titulo, t.puntos, t.tipo_evidencia, u.nombre as usuario_nombre, u.email as usuario_email, u.foto_perfil
    FROM tareas_completadas tc
    JOIN tareas_comunidad t ON tc.tarea_id = t.id
    JOIN usuarios u ON tc.user_id = u.id
    WHERE tc.estado_validacion = 'pendiente'
    ORDER BY tc.completada_at ASC
");
$stmt->execute();
$pendientes = $stmt->fetchAll();

// 2. Obtener HISTORIAL COMPLETO para auditoría (Límite 100)
$stmt = $pdo->prepare("
    SELECT tc.*, t.titulo, t.puntos, tc.puntos_ganados, u.nombre as usuario_nombre
    FROM tareas_completadas tc
    JOIN tareas_comunidad t ON tc.tarea_id = t.id
    JOIN usuarios u ON tc.user_id = u.id
    WHERE tc.estado_validacion != 'pendiente'
    ORDER BY tc.completada_at DESC
    LIMIT 100
");
$stmt->execute();
$historial = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Tareas - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard-extra.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: var(--header-bg);
            color: var(--text-dark);
            padding: 20px 30px;
            border-bottom: 1px solid var(--light-border);
        }
        .main-content {
          padding-top: 20px;
          min-height: 100vh;
        }
        
        .tabs {
            display: flex;
            gap: 20px;
            border-bottom: 2px solid var(--light-border);
            margin-bottom: 20px;
            padding-left: 30px;
        }
        
        .tab {
            padding: 15px 20px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        
        .tab:hover {
            color: var(--primary-color);
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
            padding: 0 30px 40px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Grid de Validación */
        .validacion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .validacion-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s;
            border: 1px solid var(--light-border);
        }
        
        .validacion-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .evidencia-preview {
            width: 100%;
            height: 250px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid var(--light-border);
            position: relative;
        }
        
        .evidencia-preview img, 
        .evidencia-preview video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .validacion-body {
            padding: 20px;
            color: var(--text-dark);
        }
        
        .user-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-border);
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-grad);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .tarea-titulo {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .tarea-puntos {
            color: var(--success-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
            background: rgba(16, 185, 129, 0.1);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .validacion-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-approve, .btn-reject, .btn-revoke {
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-approve {
            background: var(--success-color);
            color: white;
            flex: 1;
        }
        
        .btn-reject {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
            flex: 1;
        }
        
        .btn-reject:hover {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-revoke {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-revoke:hover {
            background: var(--danger-color);
            color: white;
        }

        .btn-evidence {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-evidence:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .zoom-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        
        .zoom-btn:hover {
            background: var(--primary-color);
        }
        
        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-dark);
        }
        
        .admin-table th {
            text-align: left;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 2px solid var(--light-border);
            color: var(--text-light);
            font-weight: 600;
        }
        
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-border);
            vertical-align: middle;
        }
        
        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Sidebar Admin -->
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="logo-text">ADMIN</div>
        </div>
        <div class="sidebar-section">
            <a href="admin-dashboard.php" class="menu-item">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </a>
            <a href="admin-membresias.php" class="menu-item">
                <i class="fas fa-crown"></i> <span>Membresías</span>
            </a>
            <a href="admin-tareas-gestion.php" class="menu-item">
                <i class="fas fa-tasks"></i> <span>Gestión Tareas</span>
            </a>
            <a href="admin-tareas-validacion.php" class="menu-item active">
                <i class="fas fa-check-double"></i> <span>Validar Tareas</span>
            </a>
            <a href="usuarios.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
            <a href="aliados.php" class="menu-item">
                <i class="fas fa-store"></i>
                <span>Aliados</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1 class="page-title">Centro de Validación</h1>
                <div class="breadcrumb"><span>Admin</span> <i class="fas fa-chevron-right"></i> <span>Auditoría de Tareas</span></div>
            </div>
        </div>
        
        <div class="content-wrapper">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('pendientes')">
                    <i class="fas fa-clock"></i> Pendientes (<?php echo count($pendientes); ?>)
                </div>
                <div class="tab" onclick="switchTab('historial')">
                    <i class="fas fa-history"></i> Auditoría Histórica
                </div>
            </div>
            
            <!-- TAB: PENDIENTES -->
            <div id="pendientes" class="tab-content active">
                <?php if (empty($pendientes)): ?>
                    <div class="card" style="text-align: center; padding: 60px;">
                        <i class="fas fa-check-circle" style="font-size: 64px; color: var(--success-color); margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3 style="color: var(--text-dark);">¡Todo al día!</h3>
                        <p style="color: var(--text-light);">No hay tareas pendientes de validación en este momento.</p>
                    </div>
                <?php else: ?>
                    <div class="validacion-grid">
                        <?php foreach ($pendientes as $p): ?>
                            <div class="validacion-card" id="card-<?php echo $p['id']; ?>">
                                <div class="evidencia-preview">
                                    <?php 
                                    $ext = pathinfo($p['evidencia'], PATHINFO_EXTENSION);
                                    $esVideo = in_array(strtolower($ext), ['mp4', 'mov', 'avi', 'mpeg']);
                                    $rutaEvidencia = "uploads/evidencias/" . htmlspecialchars($p['evidencia']);
                                    ?>
                                    
                                    <?php if ($esVideo): ?>
                                        <video src="<?php echo $rutaEvidencia; ?>" controls></video>
                                    <?php else: ?>
                                        <img src="<?php echo $rutaEvidencia; ?>" alt="Evidencia">
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo $rutaEvidencia; ?>" target="_blank" class="zoom-btn" title="Ver original">
                                        <i class="fas fa-expand"></i>
                                    </a>
                                </div>
                                
                                <div class="validacion-body">
                                    <div class="user-meta">
                                        <?php if (!empty($p['foto_perfil'])): ?>
                                            <img src="uploads/<?php echo $p['foto_perfil']; ?>" class="user-avatar-small">
                                        <?php else: ?>
                                            <div class="user-avatar-placeholder">
                                                <?php echo strtoupper(substr($p['usuario_nombre'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($p['usuario_nombre']); ?></div>
                                            <div style="font-size: 12px; color: var(--text-light);"><?php echo date('d/m/Y h:i A', strtotime($p['completada_at'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="tarea-titulo"><?php echo htmlspecialchars($p['titulo']); ?></div>
                                    <div class="tarea-puntos">+<?php echo $p['puntos']; ?> Puntos</div>
                                    
                                    <div class="validacion-actions">
                                        <button class="btn-reject" onclick="procesarTarea(<?php echo $p['id']; ?>, 'rechazar')">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                        <button class="btn-approve" onclick="procesarTarea(<?php echo $p['id']; ?>, 'aprobar')">
                                            <i class="fas fa-check"></i> Aprobar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB: HISTORIAL / AUDITORÍA -->
            <div id="historial" class="tab-content">
                <div class="card">
                    <div class="card-header" style="padding:20px; border-bottom:1px solid var(--light-border);">
                        <h3 style="color:var(--text-dark); margin:0;"><i class="fas fa-list-alt"></i> Historial de Tareas Completadas</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Tarea</th>
                                    <th>Puntos</th>
                                    <th>Evidencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $h): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($h['completada_at'])); ?></td>
                                        <td style="font-weight: 500; color: var(--primary-color);"><?php echo htmlspecialchars($h['usuario_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($h['titulo']); ?></td>
                                        <td style="color: var(--success-color); font-weight: bold;">+<?php echo $h['puntos_ganados']; ?></td>
                                        <td>
                                            <?php if ($h['evidencia']): ?>
                                                <a href="uploads/evidencias/<?php echo htmlspecialchars($h['evidencia']); ?>" target="_blank" class="btn-evidence">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-size: 12px;">Automática</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($h['estado_validacion'] == 'aprobada'): ?>
                                                <span class="status-badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success-color); padding:4px 8px; border-radius:4px; font-size:12px;">Aprobada</span>
                                            <?php elseif ($h['estado_validacion'] == 'rechazada'): ?>
                                                <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: var(--danger-color); padding:4px 8px; border-radius:4px; font-size:12px;">Rechazada</span>
                                            <?php elseif ($h['estado_validacion'] == 'revocada'): ?>
                                                <span class="status-badge" style="background: rgba(124, 58, 237, 0.2); color: var(--accent-color); padding:4px 8px; border-radius:4px; font-size:12px;">Revocada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($h['estado_validacion'] == 'aprobada'): ?>
                                                <button class="btn-revoke" onclick="revocarPuntos(<?php echo $h['id']; ?>)">
                                                    <i class="fas fa-ban"></i> Revocar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }

        function procesarTarea(id, accion) {
            let comentario = '';
            
            if (accion === 'rechazar') {
                comentario = prompt('Motivo del rechazo (opcional):', '');
                if (comentario === null) return;
            }
            
            if (!confirm(`¿Estás seguro de ${accion} esta tarea?`)) return;
            
            const btnContainer = document.querySelector(`#card-${id} .validacion-actions`);
            const originalContent = btnContainer.innerHTML;
            btnContainer.innerHTML = '<div style="text-align:center; padding: 10px; color: var(--primary-color);"><i class="fas fa-spinner fa-spin"></i> Procesando...</div>';
            
            fetch('admin-procesar-tareas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${accion}&id=${id}&comentario=${encodeURIComponent(comentario)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById(`card-${id}`);
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // Refrescar página para actualizar historial o contadores si es necesario
                        if(document.querySelectorAll('.validacion-card').length === 0) location.reload();
                    }, 300);
                } else {
                    alert('Error: ' + data.message);
                    btnContainer.innerHTML = originalContent;
                }
            })
            .catch(err => {
                 alert('Error de conexión o servidor');
                 btnContainer.innerHTML = originalContent;
            });
        }
        
        function revocarPuntos(id) {
            const motivo = prompt('¿Por qué deseas REVOCAR los puntos de esta tarea? El usuario los perderá.');
            if (!motivo) return;
            
            fetch('admin-procesar-tareas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=revocar&id=${id}&comentario=${encodeURIComponent(motivo)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Puntos revocados exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
