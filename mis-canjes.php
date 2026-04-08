<?php
require_once 'db.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener información del usuario
$user = getUsuario($userId);
$puntosInfo = obtenerPuntosUsuario($userId);

// Obtener todos los canjes
$canjes = obtenerCanjesUsuario($userId);

// Separar por estado
$canjesPendientes = array_filter($canjes, fn($c) => $c['estado'] === 'pendiente');
$canjesActivos = array_filter($canjes, fn($c) => $c['estado'] === 'activo');
$canjesUsados = array_filter($canjes, fn($c) => $c['estado'] === 'usado');
$canjesExpirados = array_filter($canjes, fn($c) => $c['estado'] === 'expirado');

// Obtener mapa de aliados para el modal de activación
$stmt = $pdo->query("SELECT id, nombre_local, tipo, direccion FROM aliados WHERE activo = 1");
$aliadosMap = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Canjes - RUGAL</title>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            padding: 5px;
            background: rgba(255,255,255,0.5);
            border-radius: 15px;
            backdrop-filter: blur(5px);
        }
        
        .tab {
            padding: 12px 25px;
            border-radius: 12px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .canjes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px; /* Better spacing */
        }
        .canje-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); /* Softer shadow */
            border: 1px solid #f1f5f9;
        }
        .canje-titulo {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a; /* Extra dark for contrast */
            margin-bottom: 5px;
        }
        .canje-descripcion {
            color: #475569; /* Darker grey */
            font-size: 14px;
            font-weight: 500;
        }
        .codigo-canje {
            background: #f1f5f9;
            padding: 24px;
            border-radius: 16px;
            font-family: 'Courier New', monospace;
            font-size: 28px; /* Larger */
            font-weight: 900;
            text-align: center;
            border: 2px dashed #6366f1; /* Primary color dashed */
            margin: 20px 0;
            color: #1e1b4b; /* Deep blue/black */
            letter-spacing: 2px;
            position: relative;
        }
        .info-item {
            color: #334155; /* Better contrast */
            font-weight: 600;
        }
        .info-item i {
            color: #6366f1;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    
    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Canjes</h1>
                <div class="breadcrumb">
                    <span>Inicio</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Mis Canjes</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="window.location.href='recompensas.php'">
                    <i class="fas fa-gift"></i>
                    <span>Ver Recompensas</span>
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Header -->
            <div class="glass-card points-widget" style="padding: 40px; background: var(--primary-grad); border: none;">
                <h2 style="margin-bottom: 10px; color: white;">Mis Cupones y Canjes</h2>
                <p style="opacity: 0.9; color: white;">
                    Presenta estos códigos en las tiendas o veterinarias aliadas para usar tus recompensas.
                </p>
            </div>
            
            <!-- Tabs -->
            <div class="card">
                <div class="tabs">
                    <button class="tab active" onclick="cambiarTab('pendientes')">
                        <i class="fas fa-clock"></i> Por Activar (<?php echo count($canjesPendientes); ?>)
                    </button>
                    <button class="tab" onclick="cambiarTab('activos')">
                        <i class="fas fa-ticket-alt"></i> Listos para usar (<?php echo count($canjesActivos); ?>)
                    </button>
                    <button class="tab" onclick="cambiarTab('usados')">
                        <i class="fas fa-check-circle"></i> Historial (<?php echo count($canjesUsados); ?>)
                    </button>
                    <?php if (!empty($canjesExpirados)): ?>
                    <button class="tab" onclick="cambiarTab('expirados')">
                        <i class="fas fa-times-circle"></i> Expirados
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Canjes Pendientes -->
                <div id="pendientes" class="tab-content active">
                    <?php if (empty($canjesPendientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-gift"></i>
                            <p>No tienes cupones pendientes por activar.</p>
                            <button class="btn-premium" onclick="window.location.href='recompensas.php'">
                                Comprar Recompensas
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="canjes-grid">
                            <?php $i = 0; foreach ($canjesPendientes as $canje): $i++; ?>
                                <div class="canje-card animate-up" id="canje-card-<?php echo $canje['id']; ?>" style="animation-delay: <?php echo $i * 0.1; ?>s;">
                                    <div class="canje-header">
                                        <div>
                                            <div class="canje-titulo"><?php echo htmlspecialchars($canje['titulo']); ?></div>
                                            <div class="canje-descripcion"><?php echo htmlspecialchars($canje['descripcion']); ?></div>
                                        </div>
                                        <span class="canje-badge" style="background: #fef9c3; color: #854d0e;">
                                            <i class="fas fa-pause-circle"></i> ADQUIRIDO
                                        </span>
                                    </div>
                                    
                                    <div style="background: #f8fafc; padding: 30px; border-radius: 16px; margin: 20px 0; text-align: center; border: 2px solid #e2e8f0;">
                                        <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;">
                                            Activa este cupón cuando estés en el local para obtener tu código de canje.
                                        </p>
                                        <button class="btn-premium" style="width: 100%;" onclick='abrirModalActivacion(<?php echo json_encode($canje); ?>)'>
                                            <i class="fas fa-bolt"></i> ¡Activar Ahora!
                                        </button>
                                    </div>
                                    
                                    <div class="canje-info">
                                        <div class="info-item">
                                            <i class="fas fa-star"></i> <?php echo $canje['puntos_gastados']; ?> PTS
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($canje['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Canjes Activos (Con Código) -->
                <div id="activos" class="tab-content">
                    <div class="canjes-grid" id="activos-grid">
                        <?php if (empty($canjesActivos)): ?>
                            <div class="empty-state" id="activos-empty">
                                <i class="fas fa-ticket-alt"></i>
                                <p>No tienes cupones listos para usar en este momento.</p>
                            </div>
                        <?php else: ?>
                            <?php $i = 0; foreach ($canjesActivos as $canje): $i++; ?>
                                <div class="canje-card animate-up" style="animation-delay: <?php echo $i * 0.1; ?>s;">
                                    <div class="canje-header">
                                        <div>
                                            <div class="canje-titulo"><?php echo htmlspecialchars($canje['titulo']); ?></div>
                                            <div class="canje-descripcion"><?php echo htmlspecialchars($canje['descripcion']); ?></div>
                                        </div>
                                        <span class="canje-badge">
                                            <i class="fas fa-check-circle"></i> ACTIVO
                                        </span>
                                    </div>
                                    
                                    <div class="codigo-canje" id="codigo-<?php echo $canje['id']; ?>">
                                        <?php echo $canje['codigo_canje']; ?>
                                    </div>
                                    
                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <button class="btn-premium btn-copiar" onclick="copiarCodigo('<?php echo $canje['codigo_canje']; ?>', <?php echo $canje['id']; ?>)">
                                            <i class="fas fa-copy"></i> Copiar Código
                                        </button>
                                    </div>
                                    
                                    <div class="canje-info" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                        <?php if(!empty($canje['aliado_nombre'])): ?>
                                        <div class="info-item" style="grid-column: span 2;">
                                            <i class="fas fa-store"></i> 
                                            <strong>Local:</strong> <?php echo htmlspecialchars($canje['aliado_nombre']); ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if(!empty($canje['fecha_expiracion'])): ?>
                                        <div class="info-item countdown-box" style="grid-column: span 2; background: #fff1f2; color: #be123c; padding: 10px; border-radius: 10px; text-align: center; font-size: 13px; font-weight: 800; margin-top: 5px; border: 1px solid #fecdd3;">
                                            <i class="fas fa-hourglass-half"></i> Expira en: 
                                            <span class="countdown-timer" data-expires="<?php echo $canje['fecha_expiracion']; ?>">--:--:--</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Canjes Usados -->
                <div id="usados" class="tab-content">
                    <div class="canjes-grid" id="usados-grid">
                        <?php if (empty($canjesUsados)): ?>
                            <div class="empty-state" id="usados-empty">
                                <i class="fas fa-check-circle"></i>
                                <p>No has usado ningún canje aún</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($canjesUsados as $canje): ?>
                            <div class="canje-card">
                                <div class="canje-header">
                                    <div>
                                        <div class="canje-titulo"><?php echo htmlspecialchars($canje['titulo']); ?></div>
                                        <div class="canje-descripcion"><?php echo htmlspecialchars($canje['descripcion']); ?></div>
                                    </div>
                                    <span class="estado-badge estado-<?php echo $canje['estado']; ?>">
                                        <i class="fas fa-check"></i> Usado
                                    </span>
                                </div>
                                
                                <div class="codigo-canje usado">
                                    <?php echo $canje['codigo_canje']; ?>
                                </div>
                                
                                <div class="canje-info">
                                    <div class="info-item">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo $canje['puntos_gastados']; ?> puntos</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Canjeado: <?php echo date('d/m/Y', strtotime($canje['created_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Usado: <?php echo date('d/m/Y', strtotime($canje['usado_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Canjes Expirados -->
                <?php if (!empty($canjesExpirados)): ?>
                <div id="expirados" class="tab-content">
                    <?php foreach ($canjesExpirados as $canje): ?>
                    <div class="canje-card">
                        <div class="canje-header">
                            <div>
                                <div class="canje-titulo"><?php echo htmlspecialchars($canje['titulo']); ?></div>
                                <div class="canje-descripcion"><?php echo htmlspecialchars($canje['descripcion']); ?></div>
                            </div>
                            <span class="estado-badge estado-<?php echo $canje['estado']; ?>">
                                <i class="fas fa-times"></i> Expirado
                            </span>
                        </div>
                        
                        <div class="codigo-canje usado">
                            <?php echo $canje['codigo_canje']; ?>
                        </div>
                        
                        <div class="canje-info">
                            <div class="info-item">
                                <i class="fas fa-star"></i>
                                <span><?php echo $canje['puntos_gastados']; ?> puntos</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($canje['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Activación (Selección de Local) -->
    <div id="activationModal" class="modal-overlay">
        <div class="modal-content animate-up" style="max-width: 500px;">
            <div class="modal-header">
                <h3>¿Dónde reclamarás?</h3>
                <button class="modal-close" onclick="cerrarModalActivacion()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px; font-weight: 600; color: #1e293b;" id="rewardTitleDisplay"></p>
                <p style="margin-bottom: 20px; color: #64748b; font-size: 14px;">
                    Selecciona el local participante para activar tu código. Recuerda que tendrás <strong>24 horas</strong> para usarlo.
                </p>
                <div id="allyList" style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding: 5px;">
                    <!-- Aliados filtrados aquí -->
                </div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn-secondary" onclick="cerrarModalActivacion()">Cancelar</button>
            </div>
        </div>
    </div>
    
    <script>
        const aliadosDisponibles = <?php echo json_encode($aliadosMap); ?>;
        let canjeEnProceso = null;

        function abrirModalActivacion(canje) {
            canjeEnProceso = canje;
            document.getElementById('rewardTitleDisplay').innerText = canje.titulo;
            const list = document.getElementById('allyList');
            list.innerHTML = '';

            // Filtrar aliados según el alcance de la recompensa
            const permitidos = [];
            if (canje.alcance_tipo === 'global') {
                aliadosDisponibles.forEach(a => permitidos.push(a));
            } else if (canje.alcance_tipo === 'tipo_aliado') {
                aliadosDisponibles.forEach(a => {
                    if (a.tipo === canje.alcance_valor) permitidos.push(a);
                });
            } else if (canje.alcance_tipo === 'especificos') {
                const ids = canje.alcance_valor.split(',');
                aliadosDisponibles.forEach(a => {
                    if (ids.includes(a.id.toString())) permitidos.push(a);
                });
            }

            if (permitidos.length === 0) {
                list.innerHTML = '<p style="text-align:center; padding: 20px; color: #ef4444;">No hay locales configurados para este premio. Contacta a soporte.</p>';
            } else {
                permitidos.forEach(ally => {
                    const item = document.createElement('div');
                    item.className = 'ally-select-item';
                    item.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s;';
                    
                    const icon = ally.tipo === 'veterinaria' ? 'fa-hospital' : 'fa-store';
                    
                    const mapsLink = ally.direccion ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(ally.direccion)}` : '#';
                    
                    item.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #6366f1;">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a;">${ally.nombre_local}</div>
                                <div style="font-size: 11px; color: #64748b; text-transform: capitalize; font-weight: 600;">
                                    ${ally.tipo} ${ally.direccion ? '• <i class="fas fa-map-marker-alt" style="font-size:10px"></i> ' + ally.direccion : ''}
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1;"></i>
                    `;
                    
                    item.onclick = () => activarCupon(ally.id, ally.nombre_local);
                    
                    item.onmouseover = () => { item.style.background = '#f1f5f9'; item.style.borderColor = '#6366f1'; };
                    item.onmouseout = () => { item.style.background = '#f8fafc'; item.style.borderColor = '#e2e8f0'; };
                    
                    list.appendChild(item);
                });
            }

            document.getElementById('activationModal').classList.add('active');
        }

        function cerrarModalActivacion() {
            document.getElementById('activationModal').classList.remove('active');
        }

        function activarCupon(aliadoId, aliadoNombre) {
            // Mostrar advertencia crítica
            const warningMsg = `⚠️ ADVERTENCIA IMPORTANTE ⚠️\n\n` +
                `Al activar este cupón:\n` +
                `• Tendrás SOLO 24 HORAS para usarlo\n` +
                `• El contador comenzará INMEDIATAMENTE\n` +
                `• Si no lo usas en 24h, LO PERDERÁS\n` +
                `• No podrás recuperar los puntos gastados\n\n` +
                `¿Estás SEGURO que quieres activar ahora para ${aliadoNombre}?`;
            
            if (!confirm(warningMsg)) return;

            const formData = new FormData();
            formData.append('canje_id', canjeEnProceso.id);
            formData.append('aliado_id', aliadoId);

            fetch('ajax-activar-canje.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cerrarModalActivacion();
                    
                    // Remover de pendientes con animación
                    const pendingCard = document.getElementById(`canje-card-${canjeEnProceso.id}`);
                    if (pendingCard) {
                        pendingCard.style.transform = 'scale(0.8)';
                        pendingCard.style.opacity = '0';
                        setTimeout(() => pendingCard.remove(), 400);
                    }

                    // Agregar a la pestaña de activos dinámicamente
                    const activosGrid = document.getElementById('activos-grid');
                    const emptyStateActivos = document.getElementById('activos-empty');
                    if (emptyStateActivos) emptyStateActivos.remove();

                    // Calcular fecha de expiración (24 horas desde ahora)
                    const now = new Date();
                    const expirationDate = new Date(now.getTime() + (24 * 60 * 60 * 1000));
                    const expirationISO = expirationDate.toISOString().slice(0, 19).replace('T', ' ');

                    const activeCard = document.createElement('div');
                    activeCard.className = 'canje-card animate-up';
                    activeCard.innerHTML = `
                        <div class="canje-header">
                            <div>
                                <div class="canje-titulo">${canjeEnProceso.titulo}</div>
                                <div class="canje-descripcion">${canjeEnProceso.descripcion}</div>
                            </div>
                            <span class="canje-badge">
                                <i class="fas fa-check-circle"></i> ACTIVO
                            </span>
                        </div>
                        
                        <div class="codigo-canje" id="codigo-new-${canjeEnProceso.id}">
                            ${data.codigo}
                        </div>
                        
                        <div style="text-align: center; margin-bottom: 20px;">
                            <button class="btn-premium btn-copiar" onclick="copiarCodigo('${data.codigo}', ${canjeEnProceso.id})">
                                <i class="fas fa-copy"></i> Copiar Código
                            </button>
                        </div>
                        
                        <div class="canje-info" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                            <div class="info-item" style="grid-column: span 2;">
                                <i class="fas fa-store"></i> 
                                <strong>Local:</strong> ${aliadoNombre}
                            </div>
                            <div class="info-item countdown-box" style="grid-column: span 2; background: #fff1f2; color: #be123c; padding: 10px; border-radius: 10px; text-align: center; font-size: 13px; font-weight: 800; margin-top: 5px; border: 1px solid #fecdd3;">
                                <i class="fas fa-hourglass-half"></i> Expira en: 
                                <span class="countdown-timer" data-expires="${expirationISO}">23:59:59</span>
                            </div>
                        </div>
                    `;
                    activosGrid.prepend(activeCard);

                    // Cambiar a la pestaña de activos
                    cambiarTab('activos');
                    
                    // Reiniciar timers
                    updateCountdowns();
                    
                    // Mostrar notificación de éxito
                    alert(`✅ ¡Cupón activado!\n\nCódigo: ${data.codigo}\nTienes 24 horas para usarlo en ${aliadoNombre}`);
                } else {
                    alert(data.message);
                }
            });
        }

        function cambiarTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab').classList.add('active');
        }
        
        function copiarCodigo(codigo, canjeId) {
            navigator.clipboard.writeText(codigo).then(function() {
                const btn = event.target.closest('.btn-copiar');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                btn.style.background = '#059669';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = ''; // Revert to CSS
                }, 2000);
            });
        }
        
        function updateCountdowns() {
            const timers = document.querySelectorAll('.countdown-timer');
            timers.forEach(timer => {
                const expires = new Date(timer.dataset.expires).getTime();
                const now = new Date().getTime();
                const distance = expires - now;
                
                if (distance < 0) {
                    timer.innerHTML = "EXPIRADO";
                    timer.parentElement.style.background = "#e2e8f0";
                    timer.parentElement.style.color = "#64748b";
                    timer.parentElement.style.borderColor = "#cbd5e1";
                } else {
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timer.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
                }
            });
        }
        
        setInterval(updateCountdowns, 1000);
        updateCountdowns();
    </script>
</body>
</html>
