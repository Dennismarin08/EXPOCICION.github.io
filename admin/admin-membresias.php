<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../premium-functions.php';

// Verificar sesión y rol de admin
checkRole('admin');

// Obtener todos los planes para el modal
$planes = obtenerPlanesPremium();

// Búsqueda de usuarios
$search = $_GET['search'] ?? '';
$filtro = $_GET['filtro'] ?? 'todos';

// Construir consulta
$sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.premium, u.nivel,
               s.id as suscripcion_id, s.plan_id, s.fecha_inicio, s.fecha_fin, s.estado as estado_suscripcion,
               p.nombre as plan_nombre
        FROM usuarios u
        LEFT JOIN suscripciones s ON u.id = s.user_id AND s.estado = 'activa'
        LEFT JOIN planes_premium p ON s.plan_id = p.id
        WHERE u.rol = 'usuario'";

if ($search) {
    $sql .= " AND (u.nombre LIKE :search OR u.email LIKE :search OR u.telefono LIKE :search)";
}

if ($filtro === 'premium') {
    $sql .= " AND u.premium = 1";
} elseif ($filtro === 'free') {
    $sql .= " AND u.premium = 0";
}

$sql .= " ORDER BY u.id DESC";

$stmt = $pdo->prepare($sql);

if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Membresías - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: #1e293b;
            color: white;
            padding: 20px 30px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-premium {
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            color: white;
        }
        
        .status-free {
            background: #e2e8f0;
            color: #64748b;
        }
        
        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-activate {
            background: #dcfce7;
            color: #166534;
        }
        
        .btn-cancel {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Reusar sidebar de admin existente, simplificado para este ejemplo -->
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="logo-text">ADMIN</div>
        </div>
        <div class="sidebar-section">
            <a href="admin-dashboard.php" class="menu-item">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </a>
            <a href="admin-membresias.php" class="menu-item active">
                <i class="fas fa-crown"></i> <span>Membresías</span>
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="page-title" style="color: white; font-size: 24px;">Gestión de Membresías</h1>
        </div>
        
        <div class="content-wrapper">
            <div class="filters">
                <form class="search-box" style="width: auto; flex: 1;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Buscar por nombre, email o teléfono..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
                
                <select onchange="window.location.href='?filtro='+this.value" style="padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="premium" <?php echo $filtro === 'premium' ? 'selected' : ''; ?>>Solo Premium</option>
                    <option value="free" <?php echo $filtro === 'free' ? 'selected' : ''; ?>>Solo Free</option>
                </select>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Plan Activo</th>
                            <th>Vencimiento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($u['nombre']); ?></div>
                                <div style="font-size: 12px; color: #64748b;">ID: <?php echo $u['id']; ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($u['email']); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($u['telefono']); ?></div>
                            </td>
                            <td>
                                <?php if ($u['premium']): ?>
                                    <span class="status-badge status-premium"><i class="fas fa-check"></i> Premium</span>
                                <?php else: ?>
                                    <span class="status-badge status-free">Gratis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $u['premium'] ? htmlspecialchars($u['plan_nombre']) : '-'; ?>
                            </td>
                            <td>
                                <?php 
                                if ($u['premium'] && $u['fecha_fin']) {
                                    $dias = (new DateTime($u['fecha_fin']))->diff(new DateTime())->days;
                                    $vencido = new DateTime($u['fecha_fin']) < new DateTime();
                                    
                                    echo date('d/m/Y', strtotime($u['fecha_fin']));
                                    if (!$vencido) {
                                        echo "<div style='font-size: 11px; color: #166534;'>($dias días restantes)</div>";
                                    } else {
                                        echo "<div style='font-size: 11px; color: #991b1b;'>(Vencido)</div>";
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($u['premium']): ?>
                                        <button class="btn-sm btn-cancel" onclick="cancelarMembresia(<?php echo $u['id']; ?>)">
                                            <i class="fas fa-ban"></i> Cancelar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-sm btn-activate" onclick="abrirModalActivacion(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre']); ?>')">
                                            <i class="fas fa-bolt"></i> Activar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($u['telefono']): ?>
                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $u['telefono']); ?>" target="_blank" class="btn-sm btn-whatsapp" style="text-decoration: none;">
                                            <i class="fab fa-whatsapp"></i> Chat
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Activación -->
    <div class="modal-overlay" id="modalActivacion">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Activar Membresía Manual</h3>
                <button class="modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Activando premium para: <strong id="modalUserName"></strong></p>
                
                <form id="formActivacion">
                    <input type="hidden" id="modalUserId" name="user_id">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Plan:</label>
                        <select name="plan_id" id="modalPlanId" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>">
                                    <?php echo $plan['nombre']; ?> - $<?php echo number_format($plan['precio']); ?> (<?php echo $plan['duracion_dias']; ?> días)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Referencia Pago (Opcional):</label>
                        <input type="text" name="referencia" placeholder="Ej: Nequi 123456" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-submit" onclick="confirmarActivacion()">Confirmar Activación</button>
            </div>
        </div>
    </div>

    <script>
        function abrirModalActivacion(userId, userName) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('modalActivacion').classList.add('active');
        }
        
        function cerrarModal() {
            document.getElementById('modalActivacion').classList.remove('active');
        }
        
        function confirmarActivacion() {
            const userId = document.getElementById('modalUserId').value;
            const planId = document.getElementById('modalPlanId').value;
            const referencia = document.querySelector('input[name="referencia"]').value;
            
            fetch('admin-procesar-membresia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=activar&user_id=${userId}&plan_id=${planId}&referencia=${referencia}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Membresía activada exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function cancelarMembresia(userId) {
            if (!confirm('¿Estás seguro de cancelar esta suscripción? El usuario perderá los beneficios inmediatamente.')) return;
            
            fetch('admin-procesar-membresia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=cancelar&user_id=${userId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Membresía cancelada');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
