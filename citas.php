<?php
require_once 'db.php';
require_once 'includes/citas_functions.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Obtener citas del usuario
$citasProximas = obtenerCitasUsuario($userId, 'proximas');
$citasPasadas = obtenerCitasUsuario($userId, 'pasadas');

// Verificar límite Free
$limiteCitas = puedeCrearCita($userId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="css/dashboard-colors.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --p-primary: #33a1ff;
            --p-accent: #5fc1ff;
            --p-gradient: linear-gradient(135deg, #33a1ff 0%, #1d91f0 100%);
            --p-glass: rgba(30, 41, 59, 0.7);
            --p-border: rgba(255, 255, 255, 0.1);
            --bg-dark: #0f172a;
        }

        body {
            background-color: var(--bg-dark) !important;
            background-image: 
                radial-gradient(circle at 20% 35%, rgba(51, 161, 255, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 65%, rgba(51, 161, 255, 0.1) 0%, transparent 40%);
            background-attachment: fixed;
            color: #f1f5f9 !important;
        }

        /* Standardized Premium Header (Centered) */
        .header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px 20px;
            gap: 15px;
            background: transparent;
        }

        .header-left, .header-right {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 2.5rem !important;
            margin-bottom: 8px !important;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.3s ease;
            letter-spacing: -1px;
        }

        .page-title:hover {
            transform: scale(1.02);
        }

        .breadcrumb, .breadcrumb span, .breadcrumb i {
            color: rgba(255, 255, 255, 0.5) !important;
            justify-content: center;
            font-size: 14px;
        }

        .content-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Glassmorphism Cards */
        .citas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .cita-card {
            background: var(--p-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--p-border);
            border-radius: 28px;
            padding: 25px;
            margin-bottom: 0;
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.4);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--p-primary);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cita-card:hover {
            transform: translateY(-8px);
            border-left-color: var(--p-accent);
            border-color: rgba(51, 161, 255, 0.4);
            box-shadow: 0 25px 50px -12px rgba(51, 161, 255, 0.25);
        }

        .cita-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .cita-veterinaria {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .vet-icon {
            width: 50px;
            height: 50px;
            background: var(--p-gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            box-shadow: 0 8px 20px rgba(51, 161, 255, 0.2);
        }

        .vet-details h3 {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            line-height: 1.2;
        }

        .vet-details p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin: 4px 0 0 0;
        }

        .estado-badge {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .estado-confirmada { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .estado-pendiente { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .estado-cancelada { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        .cita-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 18px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        .info-item i {
            color: var(--p-primary);
            font-size: 14px;
            width: 16px;
            text-align: center;
        }

        .motivo-box {
            background: rgba(51, 161, 255, 0.05);
            border: 1px solid rgba(51, 161, 255, 0.1);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .cita-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: auto;
        }

        .btn-cita {
            padding: 12px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            text-decoration: none;
        }

        .btn-ver { background: rgba(51, 161, 255, 0.1); color: #fff; border: 1px solid rgba(51, 161, 255, 0.2); }
        .btn-ver:hover { background: rgba(51, 161, 255, 0.2); }

        .btn-pagar { background: var(--p-gradient); color: #fff; box-shadow: 0 8px 20px rgba(51, 161, 255, 0.3); }
        .btn-pagar:hover { filter: brightness(1.1); transform: translateY(-2px); }

        .btn-cancelar { 
            background: rgba(239, 68, 68, 0.05); 
            color: #ef4444; 
            border: 1px solid rgba(239, 68, 68, 0.1);
            grid-column: span 2;
            margin-top: 5px;
        }
        .btn-cancelar:hover { background: rgba(239, 68, 68, 0.1); }

        .estado-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .estado-pendiente { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .estado-confirmada { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .estado-completada { background: rgba(51, 161, 255, 0.2); color: #8ecfff; border: 1px solid rgba(51, 161, 255, 0.3); }
        .estado-cancelada { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .cita-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            padding: 20px 0;
            border-top: 1px solid var(--p-border);
            border-bottom: 1px solid var(--p-border);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #e2e8f0;
        }

        .info-item i {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--p-accent);
        }

        .pago-info {
            background: rgba(51, 161, 255, 0.1);
            border: 1px solid rgba(51, 161, 255, 0.2);
            border-radius: 16px;
            padding: 16px 20px;
            margin-top: 20px;
        }

        .cita-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-cita {
            flex: 1;
            padding: 12px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            text-decoration: none;
        }

        .btn-ver { background: rgba(255, 255, 255, 0.05); color: #fff; border: 1px solid var(--p-border); }
        .btn-ver:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.3); }

        .btn-pagar { background: var(--p-gradient); color: #fff; }
        .btn-pagar:hover { filter: brightness(1.2); transform: translateY(-2px); }

        .btn-cancelar { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-cancelar:hover { background: rgba(239, 68, 68, 0.2); }

        /* Tabs Premium */
        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            justify-content: center;
            padding: 6px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 18px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .tab {
            padding: 12px 24px;
            border-radius: 14px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab.active {
            background: var(--p-primary);
            color: #fff;
            box-shadow: 0 4px 15px rgba(51, 161, 255, 0.3);
        }

        .limite-free-banner {
            background: rgba(51, 161, 255, 0.05);
            border: 1px solid rgba(51, 161, 255, 0.2);
            color: #fff;
            padding: 28px;
            border-radius: 24px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-premium {
            background: var(--p-gradient);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 18px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(51, 161, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn-premium:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(51, 161, 255, 0.4);
        }

        .btn-premium::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.6s;
        }

        .btn-premium:hover::after {
            left: 100%;
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 14px 28px;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .citas-header {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .limite-free-banner { flex-direction: column; text-align: center; gap: 20px; }
            .btn-cita { font-size: 12px; margin-bottom: 5px; }
            .cita-header { flex-direction: column; gap: 15px; align-items: flex-start; }
            .estado-badge { align-self: flex-start; }
            .citas-grid { grid-template-columns: 1fr; }
            .tabs { flex-wrap: wrap; width: 100%; justify-content: space-between; }
            .tab { flex: 1; text-align: center; justify-content: center; }
            .btn-group { flex-direction: column; width: 100%; display: flex; align-items: stretch; }
            .btn-group .btn-premium, .btn-group .btn-glass { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    
    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">📅 Mis Citas Médicas</h1>
                <div class="breadcrumb">
                    <span>Inicio</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Citas</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Límite Free Banner -->
            <?php if (!esPremium($userId) && !$limiteCitas['permitido']): ?>
            <div class="limite-free-banner">
                <div>
                    <strong>⚠️ Límite alcanzado</strong>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">Has usado <?php echo $limiteCitas['actual']; ?> de <?php echo $limiteCitas['limite']; ?> citas este mes. Upgrade a Premium para citas ilimitadas.</p>
                </div>
                <a href="upgrade-premium.php" class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-crown" style="color: #fbbf24;"></i> <span style="color: white;">Upgrade Premium</span>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Botones de Acción -->
            <div class="citas-header">
                <div class="action-banner" style="background: rgba(51, 161, 255, 0.05); border: 1px solid rgba(51, 161, 255, 0.2); border-radius: 24px; padding: 40px; text-align: center; width: 100%; margin-bottom: 40px; backdrop-filter: blur(10px);">
                    <h2 style="font-size: 24px; margin-bottom: 25px; color: white;">¿Necesitas atención médica?</h2>
                    <div class="btn-group" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="agendar-cita.php" class="btn-premium <?php echo !$limiteCitas['permitido'] ? 'disabled' : ''; ?>" style="min-width: 250px; height: 60px; font-size: 18px;">
                            <i class="fas fa-calendar-plus" style="font-size: 22px;"></i> Agendar Cita Ahora
                        </a>
                        <button class="btn-glass" onclick="mostrarModalCitaManual()" style="min-width: 250px; height: 60px; font-size: 18px;">
                            <i class="fas fa-file-medical" style="font-size: 22px;"></i> Agregar Cita Manual
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="cambiarTab('proximas')">
                    <i class="fas fa-clock"></i> Próximas (<?php echo count($citasProximas); ?>)
                </button>
                <button class="tab" onclick="cambiarTab('pasadas')">
                    <i class="fas fa-history"></i> Historial (<?php echo count($citasPasadas); ?>)
                </button>
            </div>
            
            <div id="proximas" class="tab-content active">
                <?php if (empty($citasProximas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No tienes citas próximas</h3>
                        <p>Agenda una cita con tu veterinaria de confianza</p>
                    <a href="agendar-cita.php" class="btn-premium" style="margin-top: 20px; padding: 18px 40px; font-size: 16px;">
                        <i class="fas fa-calendar-plus"></i> Agendar Mi Primera Cita
                    </a>
                    </div>
                <?php else: ?>
                    <div class="citas-grid">
                        <?php foreach ($citasProximas as $cita): ?>
                        <div class="cita-card">
                            <div class="cita-header">
                                <div class="cita-veterinaria">
                                    <div class="vet-icon">
                                        <i class="fas fa-hospital"></i>
                                    </div>
                                    <div class="vet-details">
                                        <h3><?php echo htmlspecialchars($cita['veterinaria_nombre']); ?></h3>
                                        <p><?php echo htmlspecialchars($cita['veterinaria_direccion'] ?? 'Dirección no disponible'); ?></p>
                                    </div>
                                </div>
                                <span class="estado-badge estado-<?php echo $cita['estado']; ?>">
                                    <?php echo ucfirst($cita['estado']); ?>
                                </span>
                            </div>
                            
                            <div class="cita-info">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('d M Y', strtotime($cita['fecha_hora'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('h:i A', strtotime($cita['fecha_hora'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-paw"></i>
                                    <span><?php echo htmlspecialchars($cita['mascota_nombre']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-stethoscope"></i>
                                    <span><?php echo ucfirst($cita['tipo_cita']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($cita['motivo']): ?>
                            <div class="motivo-box">
                                <strong style="color: var(--p-primary); display: block; margin-bottom: 4px;">Motivo:</strong>
                                <?php echo htmlspecialchars($cita['motivo']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Acciones -->
                            <div class="cita-actions">
                                <button class="btn-cita btn-ver" onclick="verDetallesCita(<?php echo $cita['id']; ?>)">
                                    <i class="fas fa-eye"></i> Detalles
                                </button>
                                
                                <?php if ($cita['anticipo_pagado'] < $cita['anticipo_requerido'] && $cita['estado'] == 'pendiente'): ?>
                                <button class="btn-cita btn-pagar" onclick="subirComprobante(<?php echo $cita['id']; ?>)">
                                    <i class="fas fa-wallet"></i> Pagar
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($cita['estado'] == 'pendiente'): ?>
                                <button class="btn-cita btn-cancelar" onclick="cancelarCita(<?php echo $cita['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancelar Cita
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab: Historial -->
            <div id="pasadas" class="tab-content">
                <?php if (empty($citasPasadas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>Sin historial de citas</h3>
                        <p>Aquí aparecerán tus citas pasadas</p>
                    </div>
                <?php else: ?>
                    <div class="citas-grid">
                        <?php foreach ($citasPasadas as $cita): ?>
                        <div class="cita-card" style="opacity: 0.85; border-left-color: #94a3b8;">
                            <div class="cita-header">
                                <div class="cita-veterinaria">
                                    <div class="vet-icon" style="background: rgba(51, 161, 255, 0.05); color: #5fc1ff; box-shadow: none;">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div class="vet-details">
                                        <h3><?php echo htmlspecialchars($cita['veterinaria_nombre']); ?></h3>
                                        <p><?php echo date('d M Y - h:i A', strtotime($cita['fecha_hora'])); ?></p>
                                    </div>
                                </div>
                                <span class="estado-badge" style="background: rgba(51, 161, 255, 0.1); color: #5fc1ff; border: 1px solid rgba(51, 161, 255, 0.2);">Completada</span>
                            </div>
                            
                            <div class="cita-actions">
                                <button class="btn-cita btn-ver" style="grid-column: span 2;" onclick="verDetallesCita(<?php echo $cita['id']; ?>)">
                                    <i class="fas fa-eye"></i> Revisar Detalles
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function cambiarTab(tab) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostrar tab seleccionado
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }
        
        function cancelarCita(citaId) {
            if (!confirm('¿Estás seguro de cancelar esta cita?')) return;
            
            fetch('ajax-cancelar-cita.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({cita_id: citaId})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Cita cancelada exitosamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        function subirComprobante(citaId) {
            // Redirigir a página de pago
            window.location.href = 'pagar-cita.php?id=' + citaId;
        }
        
        function verDetallesCita(citaId) {
            window.location.href = 'detalle-cita.php?id=' + citaId;
        }
        
        function mostrarModalCitaManual() {
            // TODO: Implementar modal para agregar cita manual
            alert('Función en desarrollo');
        }
    </script>
</body>
</html>
