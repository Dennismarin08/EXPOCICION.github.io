<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de admin
checkRole('admin');

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Búsqueda y Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tipo   = isset($_GET['tipo'])   ? trim($_GET['tipo'])   : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : ''; // 'pendiente' | 'activo' | ''
$params = [];
$whereClauses = ["1=1"];

if ($search) {
    $whereClauses[] = "(a.nombre LIKE ? OR a.nombre_local LIKE ? OR a.direccion LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($tipo) {
    $whereClauses[] = "a.tipo = ?";
    $params[] = $tipo;
}
if ($estado === 'pendiente') {
    $whereClauses[] = "(a.activo = 0 OR a.pendiente_verificacion = 1)";
} elseif ($estado === 'activo') {
    $whereClauses[] = "a.activo = 1";
}

$whereSql = implode(" AND ", $whereClauses);

// Obtener total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM aliados a WHERE $whereSql");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Contar pendientes (para el badge en el menú)
$stmtPend = $pdo->query("SELECT COUNT(*) FROM aliados WHERE activo = 0 OR pendiente_verificacion = 1");
$totalPendientes = $stmtPend->fetchColumn();

// Obtener aliados con info de usuario
$query = "
    SELECT a.*, u.email as usuario_email, u.nombre as usuario_nombre, u.telefono as usuario_telefono,
           u.ultimo_login as owner_last_login
    FROM aliados a 
    JOIN usuarios u ON a.usuario_id = u.id 
    WHERE $whereSql 
    ORDER BY (a.activo = 0) DESC, a.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$aliados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aliados - Admin RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <style>
        .badge-pending {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
            background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e;
            border: 1px solid #f59e0b;
        }
        .badge-active {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
            background: #d1fae5; color: #065f46; border: 1px solid #10b981;
        }
        .badge-inactive {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
            background: #fee2e2; color: #991b1b; border: 1px solid #ef4444;
        }
        .verify-panel {
            display: none; padding: 16px 20px; background: #f8faff;
            border-top: 2px solid #e8ecf8;
        }
        .verify-photos {
            display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;
        }
        .verify-photo {
            width: 90px; height: 90px; border-radius: 10px; overflow: hidden;
            border: 2px solid #e2e8f0; cursor: pointer;
        }
        .verify-photo img { width: 100%; height: 100%; object-fit: cover; }
        .maps-link {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
            background: #e0f2fe; color: #0369a1; text-decoration: none;
        }
        .maps-link:hover { background: #bae6fd; }
        .btn-approve {
            padding: 8px 18px; border-radius: 10px; border: none; cursor: pointer;
            font-size: 13px; font-weight: 700;
            background: linear-gradient(135deg, #10b981, #059669); color: white;
            transition: all 0.2s;
        }
        .btn-approve:hover { transform: translateY(-1px); }
        .pending-counter {
            background: #ef4444; color: white; border-radius: 50%;
            width: 20px; height: 20px; font-size: 11px; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
            margin-left: 4px;
        }
        .filter-estado {
            padding: 8px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0;
            background: white; font-size: 13px; cursor: pointer;
        }
        .row-pending { background: rgba(251,191,36,0.04); }
        .row-inactive { background: rgba(239,68,68,0.02); }

        /* Photo lightbox */
        .lightbox {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85);
            z-index: 9999; align-items: center; justify-content: center;
        }
        .lightbox.active { display: flex; }
        .lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; }
        .lightbox-close {
            position: absolute; top: 20px; right: 24px; font-size: 28px;
            color: white; cursor: pointer; background: none; border: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>

    <!-- Lightbox para fotos -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <img id="lightboxImg" src="" alt="Foto verificación">
    </div>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Gestión de Aliados</h1>
                <div class="breadcrumb">
                    <span>Admin</span><i class="fas fa-chevron-right"></i><span>Aliados</span>
                </div>
            </div>
            <div class="header-right">
                <button class="btn-create" onclick="window.location.href='admin-crear-veterinaria.php'">
                    <i class="fas fa-hospital"></i> Nueva Veterinaria
                </button>
                <button class="btn-create" onclick="window.location.href='admin-crear-tienda.php'" style="background:var(--warning-color) !important;">
                    <i class="fas fa-store"></i> Nueva Tienda
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="card">
                <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <h3>
                        Lista de Aliados (<?php echo $total; ?>)
                        <?php if ($totalPendientes > 0): ?>
                        <span class="pending-counter"><?php echo $totalPendientes; ?></span>
                        <span style="font-size:13px;color:#f59e0b;font-weight:600;"> pendientes de verificación</span>
                        <?php endif; ?>
                    </h3>
                    <form class="search-box" method="GET" style="display:flex;gap:10px;flex-wrap:wrap;width:auto;">
                        <input type="text" name="search" placeholder="Nombre, dirección..." value="<?php echo htmlspecialchars($search); ?>" style="width:180px;">
                        <select name="tipo" class="filter-estado">
                            <option value="">Todos los tipos</option>
                            <option value="veterinaria" <?php echo $tipo=='veterinaria'?'selected':''; ?>>Veterinaria</option>
                            <option value="tienda" <?php echo $tipo=='tienda'?'selected':''; ?>>Tienda</option>
                        </select>
                        <select name="estado" class="filter-estado">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?php echo $estado=='pendiente'?'selected':''; ?>>Pendientes ⏳</option>
                            <option value="activo" <?php echo $estado=='activo'?'selected':''; ?>>Activos ✅</option>
                        </select>
                        <button type="submit" class="btn-primary" style="padding:8px 14px;"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user-tie"></i> Aliado</th>
                            <th><i class="fas fa-building"></i> Tipo</th>
                            <th><i class="fas fa-map-marker-alt"></i> Local / Dirección</th>
                            <th><i class="fas fa-phone"></i> Contacto</th>
                            <th><i class="fas fa-toggle-on"></i> Estado</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($aliados)): ?>
                            <tr><td colspan="7" style="text-align:center;padding:30px;color:#64748b;">
                                <i class="fas fa-handshake" style="font-size:24px;margin-bottom:10px;"></i><br>
                                No se encontraron aliados
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($aliados as $a):
                                $isPending = (!$a['activo'] || $a['pendiente_verificacion']);
                                
                                $fotosRaw = json_decode($a['fotos_verificacion'] ?? '[]', true);
                                $fotos = [];
                                if (is_array($fotosRaw)) {
                                    foreach ($fotosRaw as $f) {
                                        $url = buildImgUrl($f);
                                        if ($url) $fotos[] = $url;
                                    }
                                }
                                
                                $rowClass = !$a['activo'] ? 'row-inactive' : ($a['pendiente_verificacion'] ? 'row-pending' : '');
                            ?>
                            <tr id="ally-row-<?php echo $a['id']; ?>" class="<?php echo $rowClass; ?>">
                                <td style="font-weight:600;color:#667eea;">#<?php echo $a['id']; ?></td>
                                <td>
                                    <div style="font-weight:600;color:#1e293b;">
                                        <i class="fas fa-user-circle" style="margin-right:6px;color:#667eea;"></i>
                                        <?php echo htmlspecialchars($a['usuario_nombre'] ?? 'Sin nombre'); ?>
                                    </div>
                                    <div style="font-size:11px;color:#64748b;">
                                        <i class="fas fa-at" style="margin-right:3px;"></i>
                                        <?php echo htmlspecialchars($a['usuario_email'] ?? ''); ?>
                                    </div>
                                    <?php if ($a['owner_last_login']): ?>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                                        <i class="fas fa-clock" style="margin-right:3px;"></i>
                                        Últ. login: <?php echo date('d/m/Y H:i', strtotime($a['owner_last_login'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $a['tipo']; ?>" style="display:flex;align-items:center;gap:6px;">
                                        <?php $icon = $a['tipo']=='veterinaria' ? 'fas fa-hospital' : 'fas fa-store'; ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                        <?php echo ucfirst($a['tipo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:600;color:#1e293b;">
                                        <i class="fas fa-store-alt" style="margin-right:4px;color:#64748b;"></i>
                                        <?php echo htmlspecialchars($a['nombre_local'] ?? ''); ?>
                                    </div>
                                    <div style="font-size:11px;color:#64748b;">
                                        <i class="fas fa-map-pin" style="margin-right:3px;"></i>
                                        <?php echo htmlspecialchars($a['direccion'] ?? 'Sin dirección'); ?>
                                    </div>
                                    <?php if (!empty($a['google_maps_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($a['google_maps_url']); ?>" target="_blank" 
                                       style="font-size:11px;color:#0369a1;display:inline-flex;align-items:center;gap:3px;margin-top:2px;">
                                        <i class="fas fa-map"></i> Ver en Maps
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($fotos) || $isPending): ?>
                                    <div style="margin-top:4px;">
                                        <button onclick="toggleVerify(<?php echo $a['id']; ?>)"
                                            style="background:none;border:none;font-size:11px;color:#667eea;cursor:pointer;font-weight:600;padding:0;">
                                            <i class="fas fa-eye"></i> Revisar Información
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#64748b;">
                                    <i class="fas fa-phone" style="margin-right:4px;"></i>
                                    <?php echo htmlspecialchars($a['usuario_telefono'] ?? 'Sin teléfono'); ?>
                                </td>
                                <td>
                                    <?php if (!$a['activo'] && $a['pendiente_verificacion']): ?>
                                        <span class="badge-pending"><i class="fas fa-clock"></i> Pendiente</span>
                                    <?php elseif ($a['activo']): ?>
                                        <span class="badge-active"><i class="fas fa-check-circle"></i> Activo</span>
                                    <?php else: ?>
                                        <span class="badge-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <?php if ($isPending): ?>
                                        <button class="btn-approve" onclick="approveAlly(<?php echo $a['id']; ?>)" title="Aprobar y activar">
                                            <i class="fas fa-check"></i> Aprobar
                                        </button>
                                        <?php else: ?>
                                        <button onclick="toggleAllyStatus(<?php echo $a['id']; ?>)" 
                                            style="background:#e0f2fe;color:#0369a1;border:none;padding:7px 12px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button style="background:#fee2e2;color:#b91c1c;border:none;padding:7px 12px;border-radius:8px;cursor:pointer;" 
                                            title="Eliminar" onclick="deleteAlly(<?php echo $a['id']; ?>, <?php echo $a['usuario_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Panel de verificación (fotos y detalles) -->
                            <?php if (!empty($fotos) || $isPending): ?>
                            <tr id="verify-panel-<?php echo $a['id']; ?>" style="display:none;">
                                <td colspan="7" style="padding:0;">
                                    <div class="verify-panel" style="display:block;">
                                        <div style="font-weight:700;color:#0f172a;margin-bottom:10px;">
                                            <i class="fas fa-clipboard-check" style="color:#667eea;"></i> 
                                            Verificación — <?php echo htmlspecialchars($a['nombre_local'] ?? 'Sin local'); ?>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                            <div>
                                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Nombre del local</div>
                                                <div style="font-weight: 600; font-size: 14px; margin-bottom: 10px; color: #1e293b;"><?php echo htmlspecialchars($a['nombre_local']); ?></div>
                                                
                                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Tipo de negocio</div>
                                                <div style="font-weight: 600; font-size: 14px; color: #1e293b;"><?php echo ucfirst($a['tipo']); ?></div>
                                            </div>
                                            <div>
                                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Dirección</div>
                                                <div style="font-weight: 600; font-size: 14px; margin-bottom: 10px; color: #1e293b;"><?php echo htmlspecialchars($a['direccion'] ?? 'No especificada'); ?></div>
                                                
                                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Descripción</div>
                                                <div style="font-size: 13px; color: #475569;"><?php echo nl2br(htmlspecialchars($a['descripcion'] ?? 'Sin descripción')); ?></div>
                                            </div>
                                        </div>

                                        <?php if(!empty($fotos)): ?>
                                        <div class="verify-photos">
                                            <?php foreach ($fotos as $foto): ?>
                                            <div class="verify-photo" onclick="openLightbox('<?php echo htmlspecialchars($foto); ?>')">
                                                <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto local"
                                                     onerror="this.src=''">
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($a['google_maps_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($a['google_maps_url']); ?>" target="_blank" class="maps-link" style="margin-bottom: 10px;">
                                            <i class="fas fa-map-marker-alt"></i> Ver ubicación en Google Maps
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($isPending): ?>
                                        <div style="margin-top:10px;padding:15px;background:#fffbeb;border-radius:10px;border:1px solid #f59e0b;font-size:13px;color:#92400e;">
                                            <strong><i class="fas fa-info-circle"></i> Cuenta pendiente de verificación.</strong>
                                            Revisa los datos y las fotos antes de aprobar.
                                            <div style="margin-top: 12px; display: flex; gap: 10px; flex-wrap: wrap;">
                                                <button class="btn-approve" onclick="approveAlly(<?php echo $a['id']; ?>)">
                                                    <i class="fas fa-check"></i> Aprobar Cuenta
                                                </button>
                                                <button style="background: #fee2e2; color: #b91c1c; padding: 8px 18px; border-radius: 10px; border: none; cursor: pointer; font-size: 13px; font-weight: 700; transition: all 0.2s;" onclick="rejectAlly(<?php echo $a['id']; ?>)">
                                                    <i class="fas fa-times"></i> Rechazar
                                                </button>
                                                <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $a['usuario_telefono']); ?>?text=Hola%20<?php echo urlencode($a['usuario_nombre']); ?>!%20Hemos%20recibido%20tu%20solicitud%20para%20unirte%20a%20RUGAL%20como%20<?php echo $a['tipo']; ?>." target="_blank" style="background: #25D366; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                    <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div style="padding:20px;display:flex;justify-content:center;gap:10px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($tipo); ?>&estado=<?php echo urlencode($estado); ?>" class="btn-outline">Anterior</a>
                    <?php endif; ?>
                    <span style="display:flex;align-items:center;">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&tipo=<?php echo urlencode($tipo); ?>&estado=<?php echo urlencode($estado); ?>" class="btn-outline">Siguiente</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function toggleAllyStatus(allyId) {
        if (!confirm('¿Cambiar el estado de este aliado?')) return;
        const fd = new FormData();
        fd.append('action', 'toggle_ally_status');
        fd.append('ally_id', allyId);
        fetch('<?php echo BASE_URL; ?>/ajax-admin-user-actions.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + data.message);
        });
    }

    function approveAlly(allyId) {
        if (!confirm('¿Aprobar y activar la cuenta de este aliado?')) return;
        const fd = new FormData();
        fd.append('action', 'approve_ally');
        fd.append('ally_id', allyId);
        fetch('<?php echo BASE_URL; ?>/ajax-admin-user-actions.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error al aprobar: ' + data.message);
        });
    }

    function rejectAlly(allyId) {
        if (!confirm('¿Rechazar esta solicitud? El aliado quedará inactivo y ya no estará pendiente.')) return;
        const fd = new FormData();
        fd.append('action', 'reject_ally');
        fd.append('ally_id', allyId);
        fetch('<?php echo BASE_URL; ?>/ajax-admin-user-actions.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error al rechazar: ' + data.message);
        });
    }

    function deleteAlly(allyId, userId) {
        if (!confirm('¿Eliminar este aliado? Esta acción también eliminará al usuario asociado.')) return;
        const fd = new FormData();
        fd.append('action', 'delete_user');
        fd.append('user_id', userId);
        fetch('<?php echo BASE_URL; ?>/ajax-admin-user-actions.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) document.getElementById('ally-row-' + allyId).remove();
            else alert('Error: ' + data.message);
        });
    }

    function toggleVerify(id) {
        const row = document.getElementById('verify-panel-' + id);
        if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    }

    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightbox').classList.add('active');
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
    }
    </script>
</body>
</html>
