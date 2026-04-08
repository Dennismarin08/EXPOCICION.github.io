<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

// Obtener datos globales para reportes
$periodo = $_GET['periodo'] ?? 'mes';

switch ($periodo) {
    case 'semana':
        $dateFilter = "DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'año':
        $dateFilter = "DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $dateFilter = "DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
}

// Stats por periodo
$total_usuarios_global = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$registrosPeriodo = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE created_at >= $dateFilter")->fetchColumn();
$premiumPeriodo = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE premium = 1 AND created_at >= $dateFilter")->fetchColumn();
$aliadosPeriodo = $pdo->query("SELECT COUNT(*) FROM aliados WHERE created_at >= $dateFilter")->fetchColumn();

// Distribución de roles
$rolesDist = $pdo->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol")->fetchAll();

// Actividad reciente (Canjes)
$ultimosCanjes = $pdo->query("
    SELECT c.*, r.titulo as recompensa_nombre, u.nombre as usuario_nombre 
    FROM canjes c 
    JOIN recompensas r ON c.recompensa_id = r.id 
    JOIN usuarios u ON c.user_id = u.id 
    ORDER BY c.created_at DESC LIMIT 10
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .btn-print {
            background: #1e293b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Generación de Reportes</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Reportes</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir Informe
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Filtro de Periodo -->
            <div class="card" style="padding: 20px; margin-bottom: 20px;">
                <div style="display:flex; align-items:center; gap:15px;">
                    <span style="font-weight:600;">Filtrar Periodo:</span>
                    <a href="?periodo=semana" class="btn-outline <?php echo $periodo == 'semana' ? 'active' : ''; ?>">Última Semana</a>
                    <a href="?periodo=mes" class="btn-outline <?php echo $periodo == 'mes' ? 'active' : ''; ?>">Último Mes</a>
                    <a href="?periodo=año" class="btn-outline <?php echo $periodo == 'año' ? 'active' : ''; ?>">Último Año</a>
                </div>
            </div>

            <div class="report-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Crecimiento del Ecosistema</h3>
                    </div>
                    <div class="welcome-content" style="padding:20px; color:var(--text-primary);">
                        <div style="display:flex; flex-direction:column; gap:15px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fas fa-user-plus"></i> Nuevos Usuarios:</span>
                                <span style="font-weight:700; font-size:18px; color:var(--admin-accent);"><?php echo $registrosPeriodo; ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fas fa-crown"></i> Conversion Premium:</span>
                                <span style="font-weight:700; font-size:18px; color:#f59e0b;"><?php echo $premiumPeriodo; ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fas fa-handshake"></i> Nuevos Aliados:</span>
                                <span style="font-weight:700; font-size:18px; color:#10b981;"><?php echo $aliadosPeriodo; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Distribución de Roles</h3>
                    </div>
                    <div class="welcome-content" style="padding:20px;">
                        <?php foreach($rolesDist as $rd): ?>
                            <div style="margin-bottom:10px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                    <span style="color:var(--text-primary); text-transform:capitalize;"><?php echo $rd['rol']; ?></span>
                                    <span style="font-weight:600; color:var(--text-primary);"><?php echo $rd['total']; ?></span>
                                </div>
                                <div style="width:100%; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                                    <div style="width:<?php echo ($rd['total']/$total_usuarios_global)*100; ?>%; height:100%; background:var(--admin-accent);"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Actividad Reciente de Canjes</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Recompensa</th>
                            <th>Código</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ultimosCanjes as $c): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($c['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($c['recompensa_nombre']); ?></td>
                                <td style="font-family:monospace;"><?php echo $c['codigo']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $c['estado'] == 'canjeado' ? 'status-active' : 'status-pending'; ?>">
                                        <?php echo ucfirst($c['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
