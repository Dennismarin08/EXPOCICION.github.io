<?php
require_once 'db.php';
require_once 'premium-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Obtener planes
$planes = obtenerPlanesPremium();

// Verificar si ya es premium
$yaPremium = esPremium($userId);
$suscripcionActiva = null;
if ($yaPremium) {
    $suscripcionActiva = obtenerSuscripcionActiva($userId);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade a Premium - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold-gradient: linear-gradient(135deg, #FDB931 0%, #f59e0b 100%);
            --dark-card: #1e293b;
        }
        
        .premium-hero {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .premium-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(253, 185, 49, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .premium-icon-large {
            font-size: 80px;
            margin-bottom: 20px;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 20px rgba(253, 185, 49, 0.3));
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .planes-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        
        .plan-card {
            background: white;
            border-radius: 24px;
            padding: 40px 30px;
            width: 100%;
            max-width: 350px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .plan-card.featured {
            border-color: #FDB931;
            background: linear-gradient(to bottom, #fff, #fffbeb);
            transform: scale(1.05);
            z-index: 11;
        }
        
        .plan-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
            box-shadow: 0 25px 50px rgba(253, 185, 49, 0.2);
        }
        
        .badge-popular {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gold-gradient);
            color: #1e1b4b;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(253, 185, 49, 0.4);
        }
        
        .plan-price {
            font-size: 42px;
            font-weight: 900;
            color: #1e293b;
            margin: 20px 0 5px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 5px;
        }
        
        .plan-price span {
            font-size: 20px;
            margin-top: 5px;
            opacity: 0.6;
        }
        
        .plan-period {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 30px;
            text-align: left;
            flex: 1;
        }
        
        .features-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .features-list li:last-child {
            border-bottom: none;
        }
        
        .features-list li i {
            color: #10b981;
            font-size: 16px;
        }
        
        .btn-select-plan {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            border: none;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-select-plan.primary {
            background: var(--gold-gradient);
            color: #1e1b4b;
            box-shadow: 0 10px 20px rgba(253, 185, 49, 0.2);
        }
        
        .btn-select-plan.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(253, 185, 49, 0.3);
        }
        
        .btn-select-plan.secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-select-plan.secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        .comparison-section {
            margin-top: 80px;
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comparison-table th {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #f1f5f9;
            color: #1e293b;
            font-size: 16px;
        }
        
        .comparison-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #64748b;
        }
        
        .comparison-table tr:last-child td {
            border-bottom: none;
        }
        
        .feature-name {
            text-align: left;
            font-weight: 600;
            color: #334155;
            width: 40%;
        }
        
        .check-yes { color: #10b981; font-size: 18px; }
        .check-no { color: #cbd5e1; font-size: 18px; }
        
        @media (max-width: 768px) {
            .premium-hero { padding: 40px 20px; }
            .plan-card.featured { transform: scale(1); }
            .plan-card.featured:hover { transform: translateY(-10px); }
            .comparison-section { padding: 20px; overflow-x: auto; }
            .comparison-table { min-width: 500px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Premium</h1>
                <div class="breadcrumb">
                    <span>Inicio</span> <i class="fas fa-chevron-right"></i> <span>Upgrade</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            
            <?php if ($yaPremium && $suscripcionActiva): ?>
                <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px; text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 60px; margin-bottom: 20px;"><i class="fas fa-crown"></i></div>
                    <h2 style="font-size: 32px; margin-bottom: 10px;">¡Ya eres miembro Premium!</h2>
                    <p style="font-size: 18px; opacity: 0.9; margin-bottom: 30px;">
                        Tu plan <strong><?php echo htmlspecialchars($suscripcionActiva['plan_nombre']); ?></strong> está activo hasta el <?php echo date('d/m/Y', strtotime($suscripcionActiva['fecha_fin'])); ?>.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="plan-salud-mensual.php" class="btn-select-plan" style="background: white; color: #059669; width: auto; padding: 12px 30px;">
                            Ir a mi Plan de Salud
                        </a>
                    </div>
                </div>
            <?php else: ?>
            
            <div class="premium-hero">
                <i class="fas fa-crown premium-icon-large"></i>
                <h1 style="font-size: 36px; font-weight: 900; margin-bottom: 15px;">Desbloquea el Potencial de tu Mascota</h1>
                <p style="font-size: 18px; opacity: 0.8; max-width: 600px; margin: 0 auto 40px;">
                    Accede a planes de salud personalizados, recompensas exclusivas y herramientas avanzadas de cuidado.
                </p>
            </div>
            
            <div class="planes-container">
                <!-- Plan Free (Visual Reference) -->
                <div class="plan-card">
                    <div style="text-align: center;">
                        <h3 style="font-size: 24px; color: #64748b; margin: 0;">Básico</h3>
                        <div class="plan-price">
                            <span>$</span>0
                        </div>
                        <div class="plan-period">Para siempre</div>
                    </div>
                    <ul class="features-list">
                        <li><i class="fas fa-check"></i> Registro de mascotas</li>
                        <li><i class="fas fa-check"></i> Recordatorios básicos</li>
                        <li><i class="fas fa-check"></i> Acceso a comunidad</li>
                        <li><i class="fas fa-check"></i> Recompensas estándar</li>
                        <li style="opacity: 0.5;"><i class="fas fa-times" style="color: #cbd5e1;"></i> Plan de Salud Mensual</li>
                        <li style="opacity: 0.5;"><i class="fas fa-times" style="color: #cbd5e1;"></i> Historial Médico Completo</li>
                    </ul>
                    <button class="btn-select-plan secondary" disabled>Plan Actual</button>
                </div>

                <!-- Planes Premium Dinámicos -->
                <?php foreach ($planes as $index => $plan): 
                    $isPopular = ($plan['duracion_dias'] >= 30 && $plan['duracion_dias'] <= 180); // Logic for popular
                ?>
                <div class="plan-card <?php echo $isPopular ? 'featured' : ''; ?>">
                    <?php if ($isPopular): ?>
                        <div class="badge-popular"><i class="fas fa-star"></i> Más Popular</div>
                    <?php endif; ?>
                    
                    <div style="text-align: center;">
                        <h3 style="font-size: 24px; color: #1e1b4b; margin: 0;"><?php echo htmlspecialchars($plan['nombre']); ?></h3>
                        <div class="plan-price">
                            <span>$</span><?php echo number_format($plan['precio'], 0, ',', '.'); ?>
                        </div>
                        <div class="plan-period">
                            <?php 
                                if ($plan['duracion_dias'] == 30) echo 'Facturado mensualmente';
                                elseif ($plan['duracion_dias'] == 180) echo 'Facturado semestralmente';
                                elseif ($plan['duracion_dias'] == 365) echo 'Facturado anualmente';
                                else echo 'Por ' . $plan['duracion_dias'] . ' días';
                            ?>
                        </div>
                    </div>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Plan de Salud Mensual</strong></li>
                        <li><i class="fas fa-check-circle"></i> Registro de mascotas</li>
                        <li><i class="fas fa-check-circle"></i> Recompensas VIP (50% OFF)</li>
                        <li><i class="fas fa-check-circle"></i> Historial Médico Completo</li>
                        <li><i class="fas fa-check-circle"></i> Soporte Prioritario</li>
                        <?php if ($plan['duracion_dias'] > 30): ?>
                            <li><i class="fas fa-gift" style="color: #FDB931;"></i> <strong>Ahorras <?php echo ($plan['duracion_dias'] == 365) ? '2 meses' : '15%'; ?></strong></li>
                        <?php endif; ?>
                    </ul>
                    
                    <button class="btn-select-plan primary" onclick="seleccionarPlan('<?php echo htmlspecialchars($plan['nombre']); ?>', <?php echo $plan['precio']; ?>)">
                        Seleccionar Plan
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="comparison-section">
                <h3 style="text-align: center; margin-bottom: 30px; font-size: 24px;">Comparativa de Beneficios</h3>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th class="feature-name">Característica</th>
                            <th>Gratis</th>
                            <th style="color: #FDB931;">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="feature-name">Mascotas Registradas</td>
                            <td style="text-align: center;">Ilimitadas</td>
                            <td style="text-align: center; font-weight: bold;">Ilimitadas</td>
                        </tr>
                        <tr>
                            <td class="feature-name">Plan de Salud Mensual</td>
                            <td style="text-align: center;"><i class="fas fa-times check-no"></i></td>
                            <td style="text-align: center;"><i class="fas fa-check-circle check-yes"></i></td>
                        </tr>
                        <tr>
                            <td class="feature-name">Recompensas y Canjes</td>
                            <td style="text-align: center;">Estándar</td>
                            <td style="text-align: center; font-weight: bold;">VIP (Mejores descuentos)</td>
                        </tr>
                        <tr>
                            <td class="feature-name">Historial Médico</td>
                            <td style="text-align: center;">Básico</td>
                            <td style="text-align: center; font-weight: bold;">Completo + Exportable</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php endif; ?>
            
        </div>
    </div>

    <script>
        function seleccionarPlan(planNombre, precio) {
            const numeroWhatsapp = "573167197604"; // Tu número
            const userEmail = "<?php echo $user['email']; ?>";
            const mensaje = `Hola RUGAL, quiero activar el *Plan ${planNombre}* ($${precio.toLocaleString('es-CO')}) para mi cuenta: ${userEmail}.%0A%0AQuiero saber los métodos de pago.`;
            
            if (confirm(`Serás redirigido a WhatsApp para completar tu suscripción al Plan ${planNombre}.\n\n¿Continuar?`)) {
                window.open(`https://wa.me/${numeroWhatsapp}?text=${mensaje}`, '_blank');
            }
        }
    </script>
</body>
</html>
