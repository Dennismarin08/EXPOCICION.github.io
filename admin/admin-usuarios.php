<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$whereClause = "";

if ($search) {
    $whereClause = "WHERE nombre LIKE ? OR email LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Obtener total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios $whereClause");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Obtener usuarios
$query = "SELECT * FROM usuarios $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Admin RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <style>
        /* Mejoras de diseño para admin-usuarios */
        .card {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease !important;
        }

        .card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important;
        }

        .admin-table {
            background: rgba(255, 255, 255, 0.9) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important;
        }

        .admin-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
            color: #475569 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            letter-spacing: 0.05em !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .admin-table td {
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background-color 0.2s ease !important;
        }

        .admin-table tr:hover {
            background-color: rgba(59, 130, 246, 0.02) !important;
        }

        .role-badge {
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: capitalize !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        .role-admin {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%) !important;
            color: white !important;
        }

        .role-vet {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            color: white !important;
        }

        .role-tienda {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            color: white !important;
        }

        .role-usuario {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
            color: white !important;
        }

        .badge {
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        .premium-toggle {
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .premium-toggle:hover {
            transform: scale(1.05) !important;
        }

        .btn-admin {
            padding: 8px 12px !important;
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .btn-admin:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }

        .btn-create {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
        }

        .btn-create:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4) !important;
        }

        .search-box {
            position: relative !important;
            background: rgba(255, 255, 255, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            backdrop-filter: blur(8px) !important;
        }

        .search-box i {
            position: absolute !important;
            left: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #64748b !important;
        }

        .search-box input {
            background: transparent !important;
            border: none !important;
            padding: 12px 15px 12px 45px !important;
            font-size: 14px !important;
            color: #1e293b !important;
            width: 100% !important;
        }

        .search-box input:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .btn-outline {
            background: transparent !important;
            border: 2px solid #e2e8f0 !important;
            color: #64748b !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .btn-outline:hover {
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
            background: rgba(59, 130, 246, 0.05) !important;
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease-out !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column !important;
                gap: 15px !important;
                align-items: flex-start !important;
            }

            .search-box {
                width: 100% !important;
            }

            .admin-table {
                font-size: 14px !important;
            }

            .admin-table th,
            .admin-table td {
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Gestión de Usuarios</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Usuarios</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-create" onclick="window.location.href='admin-crear-usuario.php'">
                    <i class="fas fa-plus"></i> Crear Usuario
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header" style="justify-content: space-between;">
                    <h3>Lista de Usuarios (<?php echo $total; ?>)</h3>
                    <form class="search-box" method="GET">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Buscar por nombre o email..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Usuario</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-user-tag"></i> Rol</th>
                            <th><i class="fas fa-star"></i> Membresía</th>
                            <th><i class="fas fa-calendar-alt"></i> Fecha Registro</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 30px; color:#64748b;">
                                    <i class="fas fa-users" style="font-size:24px; margin-bottom:10px;"></i><br>
                                    No se encontraron usuarios
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td style="font-weight:600; color:#667eea;">#<?php echo $u['id']; ?></td>
                                <td>
                                    <div style="font-weight:600; color:#1e293b;">
                                        <i class="fas fa-user-circle" style="margin-right:8px; color:#667eea;"></i>
                                        <?php echo htmlspecialchars($u['nombre']); ?>
                                    </div>
                                </td>
                                <td style="color:#64748b;">
                                    <i class="fas fa-at" style="margin-right:4px;"></i>
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $u['rol']; ?>" style="display:flex; align-items:center; gap:6px;">
                                        <?php
                                        $icon = 'fas fa-user';
                                        if($u['rol'] == 'admin') $icon = 'fas fa-user-shield';
                                        elseif($u['rol'] == 'vet') $icon = 'fas fa-user-md';
                                        elseif($u['rol'] == 'tienda') $icon = 'fas fa-store';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                        <?php echo ucfirst($u['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="premium-toggle" data-user-id="<?php echo $u['id']; ?>" style="cursor:pointer;" onclick="togglePremium(<?php echo $u['id']; ?>)">
                                        <?php if ($u['premium']): ?>
                                            <span class="badge" style="background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color:#78350f; display:flex; align-items:center; gap:4px;">
                                                <i class="fas fa-crown"></i> Premium
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#e2e8f0; color:#64748b; display:flex; align-items:center; gap:4px;">
                                                <i class="fas fa-user"></i> Gratis
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="color:#64748b;">
                                    <i class="fas fa-calendar" style="margin-right:4px;"></i>
                                    <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:8px;">
                                        <button class="btn-admin btn-edit" title="Editar" onclick="alert('Editar en desarrollo')" style="background:#e0f2fe; color:#0369a1;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-admin" style="background:#fee2e2; color:#b91c1c;" title="Eliminar" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div style="padding: 20px; display: flex; justify-content: center; gap: 10px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" class="btn-outline">Anterior</a>
                    <?php endif; ?>
                    
                    <span style="display:flex; align-items:center;">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" class="btn-outline">Siguiente</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function togglePremium(userId) {
        if (!confirm('¿Deseas cambiar el estado de membresía de este usuario?')) return;
        
        const formData = new FormData();
        formData.append('action', 'toggle_premium');
        formData.append('user_id', userId);
        
        fetch('<?php echo BASE_URL; ?>/ajax/ajax-admin-user-actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function deleteUser(userId, userName) {
        if (!confirm('¿Estás SEGURO de eliminar al usuario "' + userName + '"? Esta acción no se puede deshacer.')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_id', userId);
        
        fetch('<?php echo BASE_URL; ?>/ajax/ajax-admin-user-actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
    </script>
</body>
</html>
