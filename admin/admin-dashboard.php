<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

// Obtener estadísticas para admin
$stats = [
    'total_usuarios' => 0,
    'usuarios_nuevos_hoy' => 0,
    'total_mascotas' => 0,
    'aliados_activos' => 0,
    'veterinarias' => 0,
    'tiendas' => 0,
    'premium_activos' => 0,
    'ingresos_mes' => 0,
    'aliados_pendientes' => 0
];

// Consultas para estadísticas
$queries = [
    'total_usuarios' => "SELECT COUNT(*) FROM usuarios",
    'usuarios_nuevos_hoy' => "SELECT COUNT(*) FROM usuarios WHERE DATE(created_at) = CURDATE()",
    'total_mascotas' => "SELECT COUNT(*) FROM mascotas",
    'aliados_activos' => "SELECT COUNT(*) FROM aliados WHERE activo = 1",
    'veterinarias' => "SELECT COUNT(*) FROM aliados WHERE tipo = 'veterinaria' AND activo = 1",
    'tiendas' => "SELECT COUNT(*) FROM aliados WHERE tipo = 'tienda' AND activo = 1",
    'premium_activos' => "SELECT COUNT(*) FROM usuarios WHERE premium = 1",
    'ingresos_mes' => "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE estado = 'completado' AND MONTH(fecha_pago) = MONTH(CURDATE())",
    'aliados_pendientes' => "SELECT COUNT(*) FROM aliados WHERE activo = 0 AND pendiente_verificacion = 1"
];

foreach ($queries as $key => $query) {
    try {
        $stmt = $pdo->query($query);
        $stats[$key] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats[$key] = 0;
    }
}

// Obtener últimos usuarios registrados
try {
    $stmt = $pdo->query("SELECT id, nombre, email, rol, premium, created_at FROM usuarios ORDER BY id DESC LIMIT 5");
    $ultimosUsuarios = $stmt->fetchAll();
} catch (Exception $e) {
    $ultimosUsuarios = [];
}

// Obtener últimos aliados
try {
    $stmt = $pdo->query("SELECT a.*, u.nombre FROM aliados a JOIN usuarios u ON a.usuario_id = u.id ORDER BY a.id DESC LIMIT 5");
    $ultimosAliados = $stmt->fetchAll();
} catch (Exception $e) {
    $ultimosAliados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <style>
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .admin-stat-card.gradient-1 {
            border-left: 5px solid #667eea;
        }
        
        .admin-stat-card.gradient-2 {
            border-left: 5px solid #00b09b;
        }
        
        .admin-stat-card.gradient-3 {
            border-left: 5px solid #ff7e5f;
        }
        
        .admin-stat-card.gradient-4 {
            border-left: 5px solid #feb47b;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th {
            background: #f8fafc;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .admin-table tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-admin {
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #6366f1;
            color: white;
        }
        
        .role-vet {
            background: #10b981;
            color: white;
        }
        
        .role-tienda {
            background: #f59e0b;
            color: white;
        }
        
        .role-usuario {
            background: #6b7280;
            color: white;
        }
        .table-responsive-wrapper {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
        }
    </style>
</head>
<body>
    <!-- Sidebar para Admin -->
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>

    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Panel de Administración</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Dashboard</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-create" onclick="window.location.href='admin-crear-usuario.php'">
                    <i class="fas fa-plus"></i>
                    <span>Crear Usuario</span>
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php if ($stats['aliados_pendientes'] > 0): ?>
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: #f59e0b; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; color: #92400e; font-size: 16px;">¡Atención requerida!</h4>
                            <p style="margin: 5px 0 0 0; color: #b45309; font-size: 14px;">Tienes <strong><?php echo $stats['aliados_pendientes']; ?></strong> solicitudes de aliados pendientes de verificación.</p>
                        </div>
                    </div>
                    <a href="admin-aliados.php?estado=pendiente" style="background: #d97706; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s;">
                        Revisar solicitudes
                    </a>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Principales -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                         <div class="stat-value"><?php echo $stats['total_usuarios']; ?></div>
                         <div style="width:40px; height:40px; border-radius:10px; background:#e0e7ff; color:#4338ca; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-users"></i>
                         </div>
                    </div>
                    <div class="stat-label">Total Usuarios</div>
                    <div class="stat-sub"><?php echo $stats['usuarios_nuevos_hoy']; ?> nuevos hoy</div>
                </div>
                
                <div class="admin-stat-card">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                         <div class="stat-value"><?php echo $stats['total_mascotas']; ?></div>
                         <div style="width:40px; height:40px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-paw"></i>
                         </div>
                    </div>
                    <div class="stat-label">Mascotas</div>
                    <div class="stat-sub">Ecosistema activo</div>
                </div>
                
                <div class="admin-stat-card">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                         <div class="stat-value"><?php echo $stats['aliados_activos']; ?></div>
                         <div style="width:40px; height:40px; border-radius:10px; background:#fef3c7; color:#b45309; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-handshake"></i>
                         </div>
                    </div>
                    <div class="stat-label">Aliados Activos</div>
                    <div class="stat-sub"><?php echo $stats['veterinarias']; ?> Vet + <?php echo $stats['tiendas']; ?> Tiendas</div>
                </div>
                
                <div class="admin-stat-card">
                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:10px;">
                         <div class="stat-value">$<?php echo number_format($stats['ingresos_mes'], 0); ?></div>
                         <div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-dollar-sign"></i>
                         </div>
                    </div>
                    <div class="stat-label">Ingresos Mensuales</div>
                    <div class="stat-sub"><?php echo $stats['premium_activos']; ?> Usuarios Premium</div>
                </div>
            </div>
            
            <div class="row">
                <!-- Últimos Usuarios -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Últimos Usuarios Registrados</h3>
                            <a href="admin-usuarios.php" class="btn-text">Ver todos</a>
                        </div>
                        <div class="table-responsive-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosUsuarios as $usuario): ?>
                                <tr>
                                    <td>#<?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                                            <?php echo $usuario['rol']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos Aliados -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Últimos Aliados Registrados</h3>
                            <a href="admin-aliados.php" class="btn-text">Ver todos</a>
                        </div>
                        <div class="table-responsive-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Local</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosAliados as $aliado): ?>
                                <tr>
                                    <td>#<?php echo $aliado['id']; ?></td>
                                    <td><?php echo htmlspecialchars($aliado['nombre']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $aliado['tipo']; ?>">
                                            <?php echo $aliado['tipo']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($aliado['nombre_local']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($aliado['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones Rápidas Admin -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Acciones Rápidas</h3>
                        </div>
                        <div class="admin-actions">
                            <button class="btn-create" onclick="window.location.href='admin-crear-veterinaria.php'">
                                <i class="fas fa-hospital"></i> Crear Veterinaria
                            </button>
                            <button class="btn-create" onclick="window.location.href='admin-crear-tienda.php'">
                                <i class="fas fa-store"></i> Crear Tienda
                            </button>
                            <button class="btn-create" onclick="window.location.href='admin-ia-knowledge.php'" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
                                <i class="fas fa-robot"></i> Entrenar IA Local
                            </button>
                            <button class="btn-create" onclick="window.location.href='admin-educacion.php'">
                                <i class="fas fa-graduation-cap"></i> Gestionar Educación
                            </button>
                            <button class="btn-create" onclick="window.location.href='admin-reporte.php'">
                                <i class="fas fa-file-pdf"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>