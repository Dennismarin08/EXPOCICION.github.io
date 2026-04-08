<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/dashboard_data_functions.php';
require_once 'includes/planes_salud_functions.php';
require_once 'includes/comunidad_functions.php';
require_once 'includes/salud_functions.php';
require_once 'premium-functions.php';
require_once 'includes/plan_salud_mensual_functions.php';
require_once 'includes/seguimiento_functions.php';

// Verificar si el usuario está logueado
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener información del usuario
if (function_exists('getUsuario')) {
    $user = getUsuario($userId);
} else {
    // fallback: intentar cargar desde la tabla usuarios
    $stmtTmp = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtTmp->execute([$userId]);
    $user = $stmtTmp->fetch(PDO::FETCH_ASSOC);
}

// Redirigir según rol si aplica
if (!empty($user['rol']) && $user['rol'] !== 'usuario') {
    switch ($user['rol']) {
        case 'admin': header('Location: admin-dashboard.php'); exit;
        case 'veterinaria': header('Location: vet-dashboard.php'); exit;
        case 'tienda': header('Location: tienda-dashboard.php'); exit;
    }
}

$puntosInfo = obtenerPuntosUsuario($userId);
$nivelInfo = obtenerInfoNivel($puntosInfo['nivel']);
$progresoNivel = calcularProgresoNivel($puntosInfo['total_puntos_ganados']);
$estadisticasDia = obtenerEstadisticasDia($userId);
$recomendacionDiaria = obtenerRecomendacionDiaria();

$isPremium = esPremium($userId);
$gamification = obtenerInfoGamificacion($userId);
$puntosNextLevel = obtenerExperienciaParaNivel($gamification['nivel'] + 1);
$xpRestante = $puntosNextLevel - $gamification['xp_total'];
$tareasRestantes = ceil($xpRestante / ($gamification['multiplicador_activo'] ? 2 : 1)); 

// Streak Message
$streakMsg = "";
if ($gamification['racha'] >= 1 && $gamification['racha'] < 8) {
    $daysLeft = 8 - $gamification['racha'];
    $streakMsg = "¡Sigue así! Solo faltan $daysLeft días para duplicar tu XP diaria.";
} elseif ($gamification['racha'] >= 8) {
    $streakMsg = "¡Racha Leyenda activa! Estás ganando DOBLE experiencia en cada tarea.";
} else {
    $streakMsg = "¡Completa una tarea hoy para iniciar tu racha!";
}

$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ? ORDER BY id LIMIT 1");
$stmt->execute([$userId]);
$pet = $stmt->fetch();

// Tareas y Comunidad
$tareasDiarias = obtenerTareasDisponibles('diaria');
$tareasPendientes = array_slice(array_filter($tareasDiarias, fn($t) => !tareaCompletadaHoy($userId, $t['id'])), 0, 3);
$comunidadPosts = obtenerPublicacionesDestacadas(4);

// Recordatorios unificados para el dashboard
require_once 'includes/calendario_functions.php';
$proximosRecordatorios = obtenerProximosEventos($userId, 5);

// Plan de Salud Monthly
$planSalud = ($pet) ? obtenerPlanSaludMensual($pet['id']) : null;

// Ultimos seguimientos (7 días) para mostrar resumen en dashboard
$ultimosSeguimientos = ($pet) ? obtenerSeguimientos($pdo, $pet['id'], date('Y-m-d', strtotime('-7 days')), date('Y-m-d')) : [];

// Últimas citas completadas con notas del veterinario (para mostrar en dashboard)
$ultimasCitasVet = [];
if ($pet) {
    try {
        $stmtCitasUser = $pdo->prepare("SELECT fecha_hora, notas_veterinaria, diagnostico, tratamiento, veterinaria_id FROM citas WHERE mascota_id = ? AND estado = 'completada' ORDER BY fecha_hora DESC LIMIT 3");
        $stmtCitasUser->execute([$pet['id']]);
        $ultimasCitasVet = $stmtCitasUser->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $ultimasCitasVet = [];
    }
}

// Stats de mascota
$realStats = ($pet) ? obtenerEstadisticasMascotaReal($pet['id']) : [
    'next_vaccine' => 'N/A',
    'weight_change' => '0.0 kg',
    'health_score' => '0%'
];

$isPremium = esPremium($userId);

// Registrar actividad del usuario (login/visita al dashboard)
require_once 'includes/plan_salud_mensual_functions.php';
registrarActividadUsuario($userId, 'dashboard_visit');

// Determinar estado de salud para mostrar (Prioridad: Plan Activo > Base de datos)
$estadoSaludText = ucfirst($pet['estado_salud'] ?: 'Excelente');
$estadoSaludColor = '#10b981'; // Verde por defecto

if ($planSalud) {
    $nivel = $planSalud['nivel_alerta'] ?? 'verde';
    $alertasPlan = $planSalud['alertas'] ?? [];
    
    if ($nivel === 'rojo') {
        $estadoSaludColor = '#ef4444';
        $estadoSaludText = $alertasPlan['titulo'] ?? 'Atención Requerida';
    } elseif ($nivel === 'amarillo') {
        $estadoSaludColor = '#f59e0b';
        $estadoSaludText = $alertasPlan['titulo'] ?? 'Precaución';
    } else {
        // Mostrar título específico (ej: Ligero Sobrepeso) o Excelente por defecto
        $estadoSaludText = ($alertasPlan['titulo'] === 'Estado Saludable') ? 'Excelente' : ($alertasPlan['titulo'] ?? 'Excelente');
    }
} else if ($pet) {
    // Lógica de respaldo: Verificar peso vs raza si NO hay plan activo
    $datosRaza = obtenerDatosRaza($pet['raza']);
    $pesoActual = floatval($pet['peso'] ?? 0);
    $pesoMin = floatval($datosRaza['peso_min'] ?? 0);
    $pesoMax = floatval($datosRaza['peso_max'] ?? 0);
    
    if ($pesoActual > 0) {
        if ($pesoMax > 0 && $pesoActual > $pesoMax) {
            $pct = (($pesoActual - $pesoMax) / $pesoMax) * 100;
            if ($pct > 40) {
                $estadoSaludText = 'Obesidad Severa';
                $estadoSaludColor = '#ef4444';
            } elseif ($pct > 20) {
                $estadoSaludText = 'Sobrepeso';
                $estadoSaludColor = '#f59e0b';
            } elseif ($pct > 0) {
                $estadoSaludText = 'Ligero Sobrepeso';
                // Mantenemos verde o un color neutro porque no es grave, pero informa
            }
        } elseif ($pesoMin > 0 && $pesoActual < $pesoMin) {
            $pct = (($pesoMin - $pesoActual) / $pesoMin) * 100;
            if ($pct > 30) {
                $estadoSaludText = 'Desnutrición Severa';
                $estadoSaludColor = '#ef4444';
            } elseif ($pct > 15) {
                $estadoSaludText = 'Bajo Peso';
                $estadoSaludColor = '#f59e0b';
            } elseif ($pct > 0) {
                $estadoSaludText = 'Peso Bajo Promedio';
            }
        }
    }
}

// Mensaje de Alerta para Usuarios Free con problemas de peso detectados
$dashboardAlert = null;
$alertColors = ['#ef4444', '#f59e0b'];
$alertKeywords = ['Obesidad', 'Sobrepeso', 'Desnutrición', 'Bajo Peso', 'Atención', 'Precaución', 'Peso Bajo'];

// Búsqueda parcial de palabras clave (CORRECCIÓN CLAVE)
$hasKeyword = false;
foreach ($alertKeywords as $kw) {
    if (stripos($estadoSaludText, $kw) !== false) {
        $hasKeyword = true;
        break;
    }
}

if (!$isPremium && (in_array($estadoSaludColor, $alertColors) || $hasKeyword)) {
    // Personalizar mensaje según el caso
    $mensaje = 'Hemos detectado indicadores que requieren atención. Un plan de salud adecuado es clave.';
    if (stripos($estadoSaludText, 'Sobrepeso') !== false) {
        $mensaje = 'Tu mascota presenta sobrepeso. Un plan de alimentación y ejercicio personalizado puede ayudarle a recuperar su peso ideal.';
    } elseif (stripos($estadoSaludText, 'Bajo') !== false || stripos($estadoSaludText, 'Desnutrición') !== false) {
        $mensaje = 'Tu mascota está por debajo del peso ideal. Necesita una dieta rica en nutrientes para recuperarse.';
    }

    $dashboardAlert = [
        'title' => 'Recomendación de Salud',
        'subtitle' => $estadoSaludText,
        'message' => $mensaje,
        'action_vet' => 'Consultar Veterinario',
        'action_premium' => 'Activar Plan Premium'
    ];
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #60a5fa;
            --premium: #FDB931;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --bg-dark: #0f172a;
        }

        body {
            background-color: var(--bg-dark);
            color: #f8fafc;
            margin: 0;
            display: flex;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            margin-left: 260px; /* Sidebar width */
            padding: 0;
            min-height: 100vh;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
        }

        .content-wrapper {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .puntos-widget {
            background: var(--p-gradient);
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .puntos-widget::after {
            content: "\f005";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 150px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .puntos-numero {
            font-size: 42px;
            font-weight: 800;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            align-items: center;
            gap: 10px;
        }

        .gamification-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .gam-stat {
            flex: 1;
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
        
        .gam-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .gam-value { font-size: 18px; font-weight: 700; color: #fbbf24; }

        .streak-alert {
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: #fbbf24;
            padding: 10px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            align-items: center;
            gap: 10px;
        }

        .gamification-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .gam-stat {
            flex: 1;
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
        
        .gam-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .gam-value { font-size: 18px; font-weight: 700; color: #fbbf24; }

        .streak-alert {
            background: rgba(251, 191, 36, 0.2);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: #fbbf24;
            padding: 10px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .health-tip-widget {
            background: var(--glass);
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid var(--p-accent);
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }

        .pet-hero-card {
            background: var(--p-glass);
            border: 1px solid var(--p-border);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 25px;
            display: flex;
            gap: 30px;
            transition: transform 0.3s ease;
        }

        .pet-hero-card:hover { transform: translateY(-5px); }

        .pet-hero-image {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            border: 3px solid rgba(59, 130, 246, 0.3);
        }

        .pet-hero-placeholder {
            width: 160px;
            height: 160px;
            border-radius: 20px;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #475569;
        }

        .stat-badge-mini {
            background: rgba(15, 23, 42, 0.6);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }

        .health-plan-widget {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .plan-pillar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .plan-pillar:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        .pillar-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .tarea-mini {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tarea-mini:hover { background: rgba(255,255,255,0.08); }

        .community-feed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 15px;
        }

        .community-card {
            background: var(--glass);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            transition: transform 0.2s;
        }

        .community-card:hover { transform: scale(1.02); }

        .community-img { width: 100%; height: 160px; object-fit: cover; }

        /* Nuevas clases para mejor control responsive */
        .puntos-widget-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .puntos-widget-left {
            flex: 1;
        }

        .puntos-widget-right {
            text-align: right;
        }

        .puntos-widget-upgrade-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .puntos-widget-upgrade-text {
            flex: 1;
        }

        .puntos-widget-upgrade-btn {
            flex-shrink: 0;
            margin-left: 20px;
        }

        .pet-hero-image-wrapper {
            flex-shrink: 0;
        }

        .pet-hero-content {
            flex: 1;
        }

        .pet-hero-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .pet-hero-title-wrapper {
            flex: 1;
        }

        .pet-hero-title {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }

        .pet-hero-subtitle {
            color: #60a5fa;
            font-weight: 600;
            font-size: 14px;
        }

        .pet-hero-btn {
            background: rgba(255,255,255,0.1);
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid var(--glass-border);
            flex-shrink: 0;
            margin-left: 15px;
        }

        .pet-hero-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .plan-salud-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .reminder-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        /* ========================================
           RESPONSIVE STYLES - DASHBOARD
           ======================================== */

        /* Tablet (1024px) */
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; max-width: 100vw; overflow-x: hidden; }
            .col-8, .col-4 { width: 100% !important; }
            .row { flex-direction: column; }
            
            .content-wrapper {
                padding: 20px;
            }
            
            .puntos-widget {
                padding: 20px;
            }
            
            .pet-hero-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .pet-hero-image,
            .pet-hero-placeholder {
                width: 120px;
                height: 120px;
            }
        }

        /* Mobile (768px) */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }

            /* Widget de Puntos Premium */
            .puntos-widget {
                padding: 20px;
            }
            
            .puntos-widget-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .puntos-widget-right {
                text-align: center;
            }
            
            .puntos-numero {
                font-size: 32px;
                justify-content: center;
            }

            /* Widget Premium Upgrade */
            .puntos-widget-upgrade-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .puntos-widget-upgrade-btn {
                width: 100%;
                text-align: center;
                display: block;
                margin-left: 0;
            }

            /* Tip de Salud */
            .health-tip-widget {
                padding: 15px;
            }
            
            .health-tip-widget h4 {
                font-size: 14px;
            }
            
            .health-tip-widget p {
                font-size: 13px;
            }

            /* Pet Hero Card */
            .pet-hero-card {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }
            
            .pet-hero-image-wrapper {
                width: 100%;
                display: flex;
                justify-content: center;
            }
            
            .pet-hero-image,
            .pet-hero-placeholder {
                width: 100px;
                height: 100px;
            }
            
            .pet-hero-content {
                width: 100%;
            }
            
            .pet-hero-header {
                flex-direction: column;
                gap: 15px;
                align-items: center;
                text-align: center;
            }
            
            .pet-hero-title-wrapper {
                width: 100%;
            }
            
            .pet-hero-title {
                font-size: 24px !important;
            }
            
            .pet-hero-btn {
                width: 100%;
                margin-left: 0;
                text-align: center;
            }
            
            .pet-hero-stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            /* Stats Grid (3 columnas -> 1 columna) */
            .stat-badge-mini {
                padding: 10px;
            }

            /* Plan de Salud */
            .plan-salud-grid {
                grid-template-columns: 1fr !important;
                gap: 15px;
            }
            
            .plan-pillar {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .plan-pillar > div:last-child {
                width: 100%;
            }

            /* Widget Papas Primerizos */
            .card[style*="border: 1px solid var(--p-primary)"] > div > div:first-child {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .card[style*="border: 1px solid var(--p-primary)"] p {
                max-width: 100% !important;
            }
            
            .card[style*="border: 1px solid var(--p-primary)"] a {
                width: 100%;
                justify-content: center;
            }

            /* Recordatorios - Botones de filtro */
            .reminder-filters {
                flex-wrap: wrap;
                gap: 6px;
            }
            
            .btn-filter {
                flex: 1;
                min-width: calc(50% - 3px);
                font-size: 11px;
                padding: 8px 8px !important;
            }

            /* Tareas Pendientes */
            .tarea-mini {
                padding: 12px;
            }

            /* Community Feed Grid */
            .community-feed-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .community-card {
                margin-bottom: 0;
            }

            /* Header */
            .header {
                padding: 15px 20px;
                flex-wrap: wrap;
            }
            
            .header-left {
                width: 100%;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .header-right {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
            
            .nivel-badge {
                width: 100%;
                text-align: center;
            }
        }

        /* Small Mobile (480px) */
        @media (max-width: 480px) {
            .content-wrapper {
                padding: 12px;
            }
            
            .puntos-numero {
                font-size: 28px;
            }
            
            .pet-hero-card h2 {
                font-size: 20px !important;
            }
            
            .pet-hero-image,
            .pet-hero-placeholder {
                width: 80px;
                height: 80px;
            }
            
            .btn-filter {
                min-width: 100%;
                margin-bottom: 4px;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            /* Ajustes Checklist Móvil */
            .symptom-grid {
                grid-template-columns: repeat(3, 1fr) !important; /* Mantener 3 en móvil para simetría */
                gap: 8px !important;
            }
            .check-card {
                padding: 10px 5px !important;
            }
            .check-card i {
                font-size: 20px !important;
            }
            .check-card span {
                font-size: 10px !important;
            }
        }

        /* Estilos Checklist Interactivo */
        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .symptom-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .check-card-label {
            cursor: pointer;
            position: relative;
            display: block;
        }
        .check-card-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .check-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.2s ease;
            height: 100%;
            text-align: center;
        }
        .check-card i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .check-card span {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            line-height: 1.2;
        }
        .check-card-label input:checked ~ .check-card {
            background: #eff6ff;
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        .check-card-label input:checked ~ .check-card i {
            color: #3b82f6;
        }
        .check-card-label input:checked ~ .check-card span {
            color: #1e40af;
        }
        .form-select-modern {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background-color: #fff;
            font-size: 14px;
            color: #334155;
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            font-weight: 500;
        }
        .form-select-modern:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">¡Hola, <?php echo htmlspecialchars(explode(' ', $user['nombre'] ?? 'Usuario')[0]); ?>! 👋</h1>
                <div class="breadcrumb"><span>Panel Principal de Control</span></div>
            </div>
            
            <div class="header-right">
                <div class="nivel-badge" style="background: var(--glass); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--glass-border); font-weight: 700;">
                    <?php echo $nivelInfo['icono']; ?> Nivel <?php echo $nivelInfo['nombre']; ?>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- PWA Install Banner (Oculto por defecto, se muestra vía JS si es instalable) -->
            <div id="pwa-install-banner" class="card" style="display:none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 25px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                            <i class="fas fa-download"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 18px; color: white;">Instala la App de RUGAL</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Mejor experiencia, notificaciones y acceso rápido.</p>
                        </div>
                    </div>
                    <button id="btn-install-dashboard" style="background: white; color: #667eea; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="fas fa-mobile-alt"></i> Instalar Ahora
                    </button>
                </div>
            </div>

            <!-- Notification Banner (Se muestra si no están activas) -->
            <div id="pwa-notif-banner" class="card" style="display:none; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; margin-bottom: 25px; border: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 18px; color: white;">Activar Notificaciones</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Recibe recordatorios de citas y alertas de salud.</p>
                        </div>
                    </div>
                    <button id="btn-enable-notif" style="background: white; color: #2563eb; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        Activar
                    </button>
                </div>
            </div>

            <!-- Alerta Interactiva para Usuarios Free -->
            <?php if ($dashboardAlert): ?>
            <div class="card" style="border: 1px solid <?php echo $estadoSaludColor; ?>40; background: linear-gradient(to right, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95)); margin-bottom: 25px; overflow: hidden; position: relative; animation: fadeIn 0.5s ease; padding: 0;">
                <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 6px; background: <?php echo $estadoSaludColor; ?>;"></div>
                <div style="padding: 20px 25px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    
                    <!-- Icono -->
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: <?php echo $estadoSaludColor; ?>20; display: flex; align-items: center; justify-content: center; color: <?php echo $estadoSaludColor; ?>; font-size: 24px; flex-shrink: 0;">
                        <i class="fas fa-heartbeat"></i>
                    </div>

                    <!-- Texto -->
                    <div style="flex: 1; min-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            <h3 style="margin: 0; font-size: 16px; color: #f8fafc;"><?php echo htmlspecialchars($dashboardAlert['title']); ?></h3>
                            <span style="background: <?php echo $estadoSaludColor; ?>; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                <?php echo htmlspecialchars($dashboardAlert['subtitle']); ?>
                            </span>
                        </div>
                        <p style="margin: 0; font-size: 13px; color: #cbd5e1; line-height: 1.5;">
                            <?php echo htmlspecialchars($dashboardAlert['message']); ?>
                        </p>
                    </div>

                    <!-- Botones -->
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="aliados.php?tipo=veterinaria" style="padding: 10px 16px; border-radius: 10px; background: rgba(255,255,255,0.05); color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($dashboardAlert['action_vet']); ?>
                        </a>
                        <a href="upgrade-premium.php" style="padding: 10px 16px; border-radius: 10px; background: linear-gradient(135deg, #FDB931 0%, #f59e0b 100%); color: #1a1f3a; text-decoration: none; font-size: 13px; font-weight: 800; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(253, 185, 49, 0.2); transition: all 0.2s;">
                            <i class="fas fa-crown"></i> <?php echo htmlspecialchars($dashboardAlert['action_premium']); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mostrar últimas notas del veterinario cuando existan -->
            <?php if (!empty($ultimasCitasVet)): ?>
                <div class="card" style="border-radius:12px; padding:16px; margin-bottom:16px; background: linear-gradient(90deg,#061826, #071126); border:1px solid rgba(255,255,255,0.03);">
                    <h4 style="margin:0 0 10px 0; color:#f8fafc;">Notas recientes del veterinario</h4>
                    <?php foreach ($ultimasCitasVet as $citaNote): ?>
                        <div style="background: rgba(255,255,255,0.02); padding:10px; border-radius:8px; margin-bottom:8px; color:#cbd5e1;">
                            <div style="font-size:13px; color:#93c5fd; font-weight:700;"><?php echo date('d/m/Y H:i', strtotime($citaNote['fecha_hora'])); ?></div>
                            <?php if (!empty($citaNote['diagnostico'])): ?>
                                <div><strong>Diagnóstico:</strong> <?php echo htmlspecialchars($citaNote['diagnostico']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($citaNote['tratamiento'])): ?>
                                <div><strong>Tratamiento:</strong> <?php echo htmlspecialchars($citaNote['tratamiento']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($citaNote['notas_veterinaria'])): ?>
                                <div><strong>Notas:</strong> <?php echo htmlspecialchars($citaNote['notas_veterinaria']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <!-- Widget Premiun / Nivel y Racha (Ahora para todos) -->
            <?php endif; ?>
            <div class="puntos-widget">
                <div class="puntos-widget-content">
                    <div class="puntos-widget-left">
                        <!-- Nivel Actual con icono -->
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                            <span style="font-size: 28px;"><?php echo $nivelInfo['icono']; ?></span>
                            <div>
                                <div style="font-size: 14px; opacity: 0.8;">Tu Nivel</div>
                                <div style="font-size: 24px; font-weight: 800;"><?php echo $nivelInfo['nombre']; ?></div>
                            </div>
                        </div>
                        <!-- Beneficio/Mensaje motivacional -->
                        <div style="font-size: 14px; opacity: 0.9; background: rgba(0,0,0,0.2); padding: 8px 15px; border-radius: 20px; display: inline-block; margin-top: 5px;">
                            <?php echo $nivelInfo['beneficio']; ?>
                        </div>
                    </div>
                    <div class="puntos-widget-right">
                        <!-- Racha de días -->
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 800; color: #fbbf24;"><?php echo $gamification['racha']; ?> 🔥</div>
                            <div style="font-size: 12px; opacity: 0.8;">Días de Racha</div>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje interactivo de Racha -->
                <?php 
                // Calcular días para el siguiente hito
                $diasParaDobleXP = 8 - $gamification['racha'];
                $diasParaNivel = $gamification['xp_required'] - $gamification['xp_actual'];
                
                if ($gamification['racha'] >= 8): ?>
                    <div class="streak-alert" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4); color: #10b981;">
                        <i class="fas fa-fire"></i>
                        <span>🎉 ¡Racha ACTIVA! Estás ganando <strong>DOBLE XP</strong> por tarea. ¡Sigue así!</span>
                    </div>
                <?php elseif ($gamification['racha'] >= 6): ?>
                    <div class="streak-alert">
                        <i class="fas fa-bolt"></i>
                        <span>🔥 ¡Casi lo logras! Solo faltan <strong><?php echo $diasParaDobleXP; ?> días</strong> para activar DOBLE XP.</span>
                    </div>
                <?php elseif ($gamification['racha'] >= 1): ?>
                    <div class="streak-alert" style="background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.4); color: #60a5fa;">
                        <i class="fas fa-chart-line"></i>
                        <span>💪 Si sigues así, en <strong><?php echo max(1, $diasParaDobleXP); ?> días</strong> activas DOBLE XP.</span>
                    </div>
                <?php else: ?>
                    <div class="streak-alert" style="background: rgba(107, 114, 128, 0.2); border-color: rgba(107, 114, 128, 0.4); color: #9ca3af;">
                        <i class="fas fa-play"></i>
                        <span>🚀 ¡Completa una tarea para iniciar tu racha!</span>
                    </div>
                <?php endif; ?>
                
                <!-- Progreso al siguiente nivel -->
                <?php if ($gamification['nivel'] < 10): ?>
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                        <span>Siguiente nivel: <strong>Nivel <?php echo $gamification['nivel'] + 1; ?></strong></span>
                        <span><?php echo round($gamification['progreso_porcentaje']); ?>%</span>
                    </div>
                    <div class="progress-bar" style="height: 10px; background: rgba(0,0,0,0.2); border-radius: 5px; overflow: hidden;">
                        <div class="progress-fill" style="width: <?php echo $gamification['progreso_porcentaje']; ?>%; background: linear-gradient(90deg, #3b82f6, #10b981); height: 100%;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$isPremium): ?>
                    <!-- Recordatorio sutil de Premium para usuarios Free -->
                    <div style="margin-top: 20px; padding: 15px; background: rgba(253, 185, 49, 0.1); border-radius: 12px; border: 1px dashed #FDB931; display: flex; align-items: center; gap: 15px;">
                        <div style="color: #FDB931; font-size: 20px;"><i class="fas fa-crown"></i></div>
                        <div style="flex: 1;">
                            <div style="color: #fff; font-size: 13px; font-weight: 700;">Potencia tu progreso con Premium</div>
                            <div style="color: rgba(255,255,255,0.6); font-size: 11px;">Desbloquea tareas de alto puntaje y el Plan de Salud IA.</div>
                        </div>
                        <a href="upgrade-premium.php" style="background: #FDB931; color: #000; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s ease;">
                            <i class="fas fa-crown"></i> MEJORAR
                        </a>
                    </div>
                    
                    <!-- Botón principal de Upgrade a Premium (grande y destacado) -->
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="upgrade-premium.php" style="display: inline-block; background: linear-gradient(135deg, #FDB931 0%, #f59e0b 100%); color: #000; padding: 14px 35px; border-radius: 12px; font-size: 15px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 15px rgba(253, 185, 49, 0.3); transition: all 0.3s ease; transform: translateY(0);">
                            <i class="fas fa-crown"></i> Compra Premiun
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-8">
                    <!-- Tip del Día -->
                    <div class="health-tip-widget">
                        <h4 style="margin: 0 0 8px 0; color: var(--p-primary); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-lightbulb"></i> Tip de Salud: <?php echo htmlspecialchars($recomendacionDiaria['titulo']); ?>
                        </h4>
                        <p style="margin:0; font-size: 14px; opacity: 0.9; line-height: 1.6;">
                            <?php echo htmlspecialchars($recomendacionDiaria['contenido']); ?>
                        </p>
                    </div>

                    <!-- Mascota Principal Hero -->
                    <?php if ($pet): ?>
                    <div class="pet-hero-card">
                        <div class="pet-hero-image-wrapper">
                            <?php if ($pet['foto_perfil']): ?>
                                <?php 
                                $foto = $pet['foto_perfil'];
                                if(strpos($foto, 'http') !== 0 && strpos($foto, 'uploads/') !== 0) $foto = 'uploads/'.$foto;
                                ?>
                                <img src="<?php echo htmlspecialchars($foto); ?>" class="pet-hero-image" alt="Mascota" style="object-fit: contain; background: var(--bg-card);">
                            <?php else: ?>
                                <div class="pet-hero-placeholder"><i class="fas fa-paw"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pet-hero-content">
                            <div class="pet-hero-header">
                                <div class="pet-hero-title-wrapper">
                                    <h2 class="pet-hero-title"><?php echo htmlspecialchars($pet['nombre']); ?></h2>
                                    <span class="pet-hero-subtitle"><?php echo htmlspecialchars($pet['raza']); ?> • <?php echo $pet['edad']; ?> años</span>
                                </div>
                                <a href="perfil-mascota.php?id=<?php echo $pet['id']; ?>" class="pet-hero-btn">Ver Perfil</a>
                            </div>

                            <div class="pet-hero-stats-grid">
                                <div class="stat-badge-mini">
                                    <div style="font-size: 11px; opacity: 0.7; margin-bottom: 4px;">Último Peso</div>
                                    <div style="font-weight: 700; color: var(--p-primary);"><i class="fas fa-weight"></i> <?php echo obtenerUltimoPeso($pet['id']) ?: $pet['peso']; ?> kg</div>
                                </div>
                                <div class="stat-badge-mini">
                                    <div style="font-size: 11px; opacity: 0.7; margin-bottom: 4px;">Próx. Vacuna</div>
                                    <div style="font-weight: 700; color: var(--p-accent);"><i class="fas fa-syringe"></i> <?php echo $realStats['next_vaccine']; ?></div>
                                </div>
                                <div class="stat-badge-mini" style="border-color: rgba(245, 158, 11, 0.3);">
                                    <div style="font-size: 11px; opacity: 0.7; margin-bottom: 4px;">Estado Salud</div>
                                    <div style="font-weight: 700; color: <?php echo $estadoSaludColor; ?>;"><i class="fas fa-heartbeat"></i> <?php echo htmlspecialchars($estadoSaludText); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card" style="padding: 40px; text-align: center; border-style: dashed; border-width: 2px;">
                        <i class="fas fa-paw" style="font-size: 48px; opacity: 0.2; margin-bottom: 15px;"></i>
                        <h3>Registra a tu mascota</h3>
                        <p style="margin-bottom: 20px;">Empieza a monitorear su salud y ganar puntos hoy mismo.</p>
                        <a href="agregar-mascota.php" style="background: var(--primary); color: white; padding: 12px 25px; border-radius: 12px; text-decoration: none; font-weight: 700;">Agregar Mascota</a>
                    </div>
                    <?php endif; ?>

                    <!-- Checklist Diario (compacto) -->
                    <div id="checklist-card" class="card" style="margin-bottom: 25px;">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-clipboard-check"></i> Historial Médico Diario
                                <?php if (!$isPremium): ?>
                                    <span class="badge-premium" style="font-size: 12px; background: #FDB931; color: #1a1f3a; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">
                                        1 registro/día
                                    </span>
                                <?php else: ?>
                                    <span class="badge-premium" style="font-size: 12px; background: #10b981; color: #fff; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">
                                        <i class="fas fa-crown"></i> PREMIUM (Ilimitado)
                                    </span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div style="padding: 25px;">
                            <?php if ($pet): ?>
                                <!-- Formulario compacto -->
                                <form id="checklist-form" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%); padding: 20px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                    <input type="hidden" name="mascota_id" value="<?php echo intval($pet['id']); ?>">
                                    
                                    <div class="checklist-grid">
                                        <div>
                                            <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 8px;">⚡ Nivel de Actividad</label>
                                            <select name="datos[actividad]" class="form-select-modern">
                                                <option>Normal (Comportamiento energético esperado)</option>
                                                <option>Letárgico/Deprimido (Falta de energía, debilidad)</option>
                                                <option>Hiperactivo (Exceso de movimiento, inquietud)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 8px;">🍖 Apetito</label>
                                            <select name="datos[apetito]" class="form-select-modern">
                                                <option>Normal (Come con normalidad)</option>
                                                <option>Hiporexia (Come menos que lo usual)</option>
                                                <option>Anorexia (No come o rechaza alimento)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 8px;">💧 Ingesta de Agua</label>
                                            <select name="datos[agua]" class="form-select-modern">
                                                <option>Normal (Bebe agua regularmente)</option>
                                                <option>Polidipsia (Bebe más agua que lo usual)</option>
                                                <option>Disminuido (Bebe menos agua que lo usual)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 8px;">😊 Ánimo</label>
                                            <select name="datos[animo]" class="form-select-modern">
                                                <option>Normal/Feliz (Comportamiento habitual positivo)</option>
                                                <option>Apático (Sin interés, desánimo)</option>
                                                <option>Irritable/Agresivo (Comportamiento inusual negativo)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 10px;">⚠️ Síntomas Observados (Selecciona si aplica)</label>
                                    <div class="symptom-grid">
                                        <label class="check-card-label">
                                            <input type="checkbox" name="datos[vomitos]" value="1">
                                            <div class="check-card">
                                                <i class="fas fa-virus"></i>
                                                <span>Vómitos</span>
                                            </div>
                                        </label>
                                        <label class="check-card-label">
                                            <input type="checkbox" name="datos[diarrea]" value="1">
                                            <div class="check-card">
                                                <i class="fas fa-poop"></i>
                                                <span>Diarrea</span>
                                            </div>
                                        </label>
                                        <label class="check-card-label">
                                            <input type="checkbox" name="datos[prurito]" value="1">
                                            <div class="check-card">
                                                <i class="fas fa-hand-paper"></i>
                                                <span>Picazón</span>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 5px;">Observaciones Clínicas (Información adicional importante)</label>
                                        <textarea name="observaciones" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; resize: vertical; min-height: 60px;" placeholder="Describe síntomas, cambios observados o diferencias en comportamiento..."></textarea>
                                    </div>
                                    <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
                                        <button type="submit" style="background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; flex: 1;">Guardar Registro Médico</button>
                                        <div id="checklist-status" style="font-size: 12px; color: #64748b;"></div>
                                    </div>
                                    <div id="checklist-alert" style="margin-top: 10px; display: none; padding: 10px; border-radius: 8px; font-size: 12px;"></div>
                                </form>

                                <!-- Estado cuando el checklist está bloqueado (después de guardar) -->
                                <div id="checklist-disabled" style="display:none; margin-top:12px; padding:12px; background: rgba(99,102,241,0.04); border-left: 3px solid #6366f1; border-radius:8px;">
                                    <div style="font-weight:700; color:#334155;">Checklist diario enviado</div>
                                    <div id="checklist-disabled-msg" style="margin-top:6px; color:#64748b;">✅ Se hizo el checklist diario. Vuelve a las 18:00.</div>
                                    <div id="checklist-timer" style="margin-top:8px; font-weight:700; color:#0f172a;"></div>
                                </div>

                                <!-- Últimos registros (7 días) -->
                                <div style="margin-top: 20px;">
                                    <h4 style="margin-bottom: 10px; color: #334155;">Últimos registros (7 días)</h4>
                                    <div id="checklist-list" style="max-height: 200px; overflow-y: auto;">
                                        <?php if (!empty($ultimosSeguimientos)): ?>
                                            <?php foreach ($ultimosSeguimientos as $s): ?>
                                                <div style="padding: 10px; background: rgba(15, 23, 42, 0.03); border-radius: 8px; margin-bottom: 8px; font-size: 13px; border-left: 3px solid #3b82f6;">
                                                    <div style="font-weight: 700; color: #334155;"><?php echo htmlspecialchars($s['fecha']); ?></div>
                                                    <div style="color: #64748b; margin-top: 2px;">
                                                                        <?php
                                                                        $datos = [];
                                                                        if (is_string($s['datos'])) {
                                                                            $datos = json_decode($s['datos'], true) ?? [];
                                                                        } elseif (is_array($s['datos'])) {
                                                                            $datos = $s['datos'];
                                                                        }
                                                                        $resumen = [];
                                                                        if (!empty($datos['actividad'])) $resumen[] = "Actividad: " . $datos['actividad'];
                                                                        if (!empty($datos['apetito'])) $resumen[] = "Apetito: " . $datos['apetito'];
                                                                        echo implode(" | ", $resumen);
                                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="text-align: center; color: #94a3b8; font-size: 13px; padding: 15px;">
                                                No hay registros esta semana. ¡Comienza registrando hoy!
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Advertencia -->
                                <div style="margin-top: 15px; padding: 12px; background: rgba(245, 158, 11, 0.1); border-radius: 8px; border-left: 3px solid #f59e0b; font-size: 12px; color: #92400e;">
                                    ⚠️ <strong>Importante:</strong> Los datos registrados deben ser verídicos. Esta información se guardará permanentemente en el historial médico y será visible para el veterinario.
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; color: #94a3b8; padding: 15px;">
                                    <i class="fas fa-paw" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                    <p>No tienes mascotas aún. <a href="agregar-mascota.php" style="color: #3b82f6; text-decoration: none; font-weight: 700;">Agrega tu primera mascota</a> para comenzar el registro diario.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Papas Primerizos (Educación) Widget -->
                    <div class="card" style="margin-bottom: 25px; border: 1px solid var(--p-primary); overflow: hidden; position: relative;">
                        <div style="position: absolute; right: -15px; top: -15px; font-size: 80px; opacity: 0.1; color: var(--p-primary); transform: rotate(15deg);">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div style="padding: 25px; position: relative; z-index: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: var(--p-text-main); display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-graduation-cap" style="color: var(--p-primary);"></i> Papas Primerizos
                                </h3>
                                <span class="badge" style="background: var(--p-primary); color: white; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;">GUÍA EDUCATIVA</span>
                            </div>
                            <p style="font-size: 14px; color: var(--p-text-muted); line-height: 1.6; margin-bottom: 20px; max-width: 80%;">
                                ¿Eres nuevo en el mundo de las mascotas? Descubre consejos expertos sobre alimentación, juegos y educación para darle lo mejor a tu mejor amigo.
                            </p>
                            <a href="educacion.php" class="btn-text" style="background: var(--p-primary); color: white; padding: 10px 20px; border-radius: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s;">
                                Explorar Guías <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <!-- Próximos Recordatorios -->
                    <div class="card" style="margin-bottom: 25px; border-left: 4px solid #3b82f6;">
                        <div class="card-header">
                            <h3><i class="fas fa-bell"></i> Recordatorios Próximos</h3>
                            <a href="calendario.php" class="btn-text">Ver Calendario</a>
                        </div>
                        <div style="padding: 20px;">
                            <div class="reminder-filters">
                                <button class="btn-filter" onclick="filterReminders('all')" style="padding:6px 10px; border-radius:8px; background: rgba(255,255,255,0.03); color: #cbd5e1; border: none;">Todos</button>
                                <button class="btn-filter" onclick="filterReminders('cita')" style="padding:6px 10px; border-radius:8px; background: rgba(59,130,246,0.08); color:#60a5fa; border: none;">Citas</button>
                                <button class="btn-filter" onclick="filterReminders('vacuna')" style="padding:6px 10px; border-radius:8px; background: rgba(236,72,153,0.06); color:#ec4899; border: none;">Vacunas</button>
                                <button class="btn-filter" onclick="filterReminders('recordatorio')" style="padding:6px 10px; border-radius:8px; background: rgba(16,185,129,0.06); color:#10b981; border: none;">Recordatorios</button>
                            </div>
                            <?php if (empty($proximosRecordatorios)): ?>
                                <div style="text-align: center; padding: 10px; opacity: 0.6;">
                                    <p style="font-size: 13px;">No hay recordatorios próximos.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($proximosRecordatorios as $rec): ?>
                                <div class="rem-item" data-tipo="<?php echo htmlspecialchars($rec['tipo']); ?>" style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 10px;">
                                    <div style="width: 35px; height: 35px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: <?php 
                                        echo ($rec['tipo'] == 'cita' ? '#3b82f6' : ($rec['tipo'] == 'recordatorio' ? '#0ea5e9' : '#10b981')); 
                                    ?>;">
                                        <i class="fas fa-<?php echo $rec['icono']; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 13px;"><?php echo htmlspecialchars($rec['titulo']); ?></div>
                                        <div style="font-size: 11px; opacity: 0.6;">
                                            <i class="fas fa-paw"></i> <?php echo htmlspecialchars($rec['mascota']); ?> • 
                                            <i class="far fa-calendar"></i> <?php echo date('d M, H:i', strtotime($rec['fecha'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tareas Pendientes Hoy -->
                    <div class="card" style="margin-bottom: 25px;">
                        <div class="card-header">
                            <h3><i class="fas fa-tasks"></i> Tareas de Hoy</h3>
                            <a href="tareas.php" class="btn-text">Ver todas</a>
                        </div>
                        <div style="padding: 20px;">
                            <?php if (empty($tareasPendientes)): ?>
                                <div style="text-align: center; padding: 20px; opacity: 0.6;">
                                    <i class="fas fa-check-circle" style="font-size: 32px; color: #10b981; margin-bottom: 10px;"></i>
                                    <p>¡Todo listo por hoy!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tareasPendientes as $tarea): ?>
                                <div class="tarea-mini <?php echo tareaCompletadaHoy($userId, $tarea['id']) ? 'completed' : ''; ?>" onclick="window.location.href='tareas.php'">
                                    <?php if (!tareaCompletadaHoy($userId, $tarea['id'])): ?>
                                        <div class="tarea-badge">Nuevo</div>
                                    <?php endif; ?>
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                                        <i class="<?php echo $tarea['icono']; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 14px;"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                                        <div style="font-size: 11px; opacity: 0.7;">+<?php echo $tarea['puntos']; ?> pts • <?php echo rand(5, 15); ?> min</div>
                                    </div>
                                    <i class="fas fa-chevron-right" style="font-size: 12px; opacity: 0.3;"></i>
                                    <div class="tarea-progress" style="width: <?php echo rand(20, 80); ?>%;"></div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comunidad Destacada -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Feed Comunidad</h3>
                            <a href="comunidad.php" class="btn-text">Ver todo</a>
                        </div>
                        <div style="padding: 15px;">
                            <div class="community-feed-grid">
                                <?php foreach ($comunidadPosts as $post): ?>
                                <div class="community-card">
                                    <?php if($post['media_url']): ?>
                                        <div style="position: relative;">
                                            <img src="uploads/<?php echo htmlspecialchars($post['media_url']); ?>" class="community-img">
                                            <?php if($post['mascota_id']): ?>
                                                <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.6); padding: 4px 8px; border-radius: 20px; font-size: 10px; color: white;">
                                                    <i class="fas fa-paw"></i> Mascot
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="padding: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                            <img src="uploads/<?php echo $post['autor_foto'] ?: 'default-user.png'; ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                                            <span style="font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($post['autor_nombre']); ?></span>
                                        </div>
                                        <p style="margin: 0; font-size: 11px; opacity: 0.8; height: 3em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            <?php echo htmlspecialchars($post['contenido']); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <script>
                                function filterReminders(tipo) {
                                    const items = document.querySelectorAll('.rem-item');
                                    items.forEach(it => {
                                        if (tipo === 'all' || it.getAttribute('data-tipo') === tipo) {
                                            it.style.display = 'flex';
                                        } else {
                                            it.style.display = 'none';
                                        }
                                    });
                                }

                                function scrollToPlanCard() {
                                    const card = document.getElementById('plan-salud-card');
                                    if (card) {
                                        card.scrollIntoView({ behavior: 'smooth' });
                                    }
                                }

                                // Scroll to plan card if URL has the hash
                                if (window.location.hash === '#plan-salud-card') {
                                    setTimeout(function() {
                                        scrollToPlanCard();
                                    }, 100);
                                }

                                // Listen for hash changes (when clicking sidebar link)
                                window.addEventListener('hashchange', function() {
                                    if (window.location.hash === '#plan-salud-card') {
                                        setTimeout(function() {
                                            scrollToPlanCard();
                                        }, 100);
                                    }
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const checkForm = document.getElementById('checklist-form');
    if (!checkForm) return;
    const mascotaIdInput = checkForm.querySelector('input[name="mascota_id"]');
    const mascotaId = mascotaIdInput ? mascotaIdInput.value : null;

    function showChecklistDisabled(mId, expiry) {
        const formEl = document.getElementById('checklist-form');
        const disabledEl = document.getElementById('checklist-disabled');
        const timerEl = document.getElementById('checklist-timer');
        if (!disabledEl || !formEl) return;
        formEl.style.display = 'none';
        disabledEl.style.display = 'block';

        function update() {
            const now = Date.now();
            const left = Math.max(0, (expiry || 0) - now);
            if (left <= 0) {
                // restore
                localStorage.removeItem('checklist_expiry_' + mId);
                disabledEl.style.display = 'none';
                formEl.style.display = 'block';
                if (timerInterval) clearInterval(timerInterval);
                return;
            }
            const hrs = Math.floor(left / (1000*60*60));
            const mins = Math.floor((left % (1000*60*60)) / (1000*60));
            const secs = Math.floor((left % (1000*60)) / 1000);
            timerEl.innerText = `${hrs}h ${mins}m ${secs}s`;
        }
        update();
        const timerInterval = setInterval(update, 1000);
    }

    // On load, check localStorage for expiry
    if (mascotaId) {
        const key = 'checklist_expiry_' + mascotaId;
        const v = localStorage.getItem(key);
        if (v) {
            const expiry = parseInt(v, 10);
            if (!isNaN(expiry) && expiry > Date.now()) {
                showChecklistDisabled(mascotaId, expiry);
            } else {
                localStorage.removeItem(key);
            }
        }
    }
    
    checkForm.addEventListener('submit', async function(e){
        e.preventDefault();
        const fd = new FormData(this);
        const obj = {
            mascota_id: fd.get('mascota_id'), 
            observaciones: fd.get('observaciones'), 
            datos: {}
        };
        
        for (const pair of fd.entries()){
            const k = pair[0];
            const v = pair[1];
            if (k.startsWith('datos[')){
                const key = k.substring(6, k.length-1);
                if (v === '1') obj.datos[key] = true; 
                else if (v) obj.datos[key] = v;
            }
        }
        
        try {
            const res = await fetch('ajax-save-seguimiento.php', {
                method: 'POST', 
                headers: {'Content-Type':'application/json'}, 
                body: JSON.stringify(obj)
            });
            const j = await res.json();
            
            if (j.success){ 
                // Mostrar éxito (mensaje solicitado que indique hora de nuevo registro)
                const alertEl = document.getElementById('checklist-alert');
                if (alertEl) {
                    alertEl.style.display = 'block';
                    alertEl.style.background = 'rgba(16, 185, 129, 0.1)';
                    alertEl.style.borderLeft = '3px solid #10b981';
                    alertEl.style.color = '#047857';
                    alertEl.innerHTML = '✅ Se hizo el checklist diario. Vuelve a las 18:00.';
                }
                // Limpiar form
                checkForm.reset();
                const statusEl = document.getElementById('checklist-status');
                if (statusEl) statusEl.innerHTML = '✓ Registro completado';
                // Marcar bloqueo en localStorage por 24h
                try {
                    const mascotaId = fd.get('mascota_id');
                    const expires = Date.now() + 24 * 3600 * 1000; // 24h
                    localStorage.setItem('checklist_expiry_' + mascotaId, String(expires));
                    showChecklistDisabled(mascotaId, expires);
                } catch(e) { setTimeout(()=>location.reload(), 2000); }
            }
            else { 
                const alertEl = document.getElementById('checklist-alert');
                if (alertEl) {
                    alertEl.style.display = 'block';
                    alertEl.style.background = 'rgba(239, 68, 68, 0.1)';
                    alertEl.style.borderLeft = '3px solid #ef4444';
                    alertEl.style.color = '#7f1d1d';
                    alertEl.innerHTML = '❌ ' + (j.error || 'Error al guardar');
                }
            }
        } catch(err){ 
            const alertEl = document.getElementById('checklist-alert');
            if (alertEl) {
                alertEl.style.display = 'block';
                alertEl.style.background = 'rgba(239, 68, 68, 0.1)';
                alertEl.style.borderLeft = '3px solid #ef4444';
                alertEl.style.color = '#7f1d1d';
                alertEl.innerHTML = '❌ Error de conexión';
            }
        }
    });
});
// Notificaciones push locales
document.addEventListener('DOMContentLoaded', () => {
    const notifyBtn = document.getElementById('btn-enable-notif');
    const notifyBanner = document.getElementById('pwa-notif-banner');
    const sidebarNotifBtn = document.getElementById('pwa-notif-btn');

    // Verificar si el navegador soporta notificaciones
    if (!("Notification" in window)) {
        console.log("Este navegador no soporta notificaciones de escritorio.");
        if (notifyBanner) notifyBanner.style.display = 'none';
        if (sidebarNotifBtn) sidebarNotifBtn.style.display = 'none';
        return;
    }

    // Comprobar estado inicial del permiso
    if (Notification.permission === "granted") {
        if (notifyBanner) notifyBanner.style.display = 'none';
        // Enviar notificaciones pendientes si las hay
        checkAndSendReminders();
    } else if (Notification.permission !== "denied") {
        if (notifyBanner) notifyBanner.style.display = 'block';
    } else {
        if (notifyBanner) notifyBanner.style.display = 'none'; // Denegado permanentemente
    }

    // Función para solicitar permisos y suscribir
    function requestPushPermission(e) {
        if (e) e.preventDefault();
        
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                if (notifyBanner) notifyBanner.style.display = 'none';
                
                // Enviar una de prueba/bienvenida
                new Notification("¡Notificaciones activadas!", {
                    body: "Ahora recibirás recordatorios de citas y bienestar de tu mascota.",
                    icon: "assets/images/logo.png",
                    badge: "assets/images/logo.png"
                });
                
                checkAndSendReminders();
            } else {
                alert("Las notificaciones fueron bloqueadas. Puedes activarlas desde los ajustes de tu navegador.");
                if (notifyBanner) notifyBanner.style.display = 'none';
            }
        });
    }

    if (notifyBtn) notifyBtn.addEventListener('click', requestPushPermission);
    if (sidebarNotifBtn) sidebarNotifBtn.addEventListener('click', requestPushPermission);
    
    // Función para iterar las notificaciones mostradas en PHP
    function checkAndSendReminders() {
        // Solo enviar una vez por sesión para evitar spam al recargar
        if (sessionStorage.getItem('notifs_sent_today')) return;
        
        <?php if (!empty($proximosRecordatorios)): ?>
            const recordatorios = <?php echo json_encode($proximosRecordatorios); ?>;
            
            // Limitamos a 2 para no saturar
            recordatorios.slice(0, 2).forEach((rec, index) => {
                // Pequeño delay si hay más de una
                setTimeout(() => {
                    new Notification("Pendiente: " + rec.titulo, {
                        body: rec.mascota + " - " + rec.fecha,
                        icon: "assets/images/logo.png",
                        requireInteraction: true // Mantenerla abierta hasta que el usuario cliquee
                    });
                }, index * 1000);
            });
            
            sessionStorage.setItem('notifs_sent_today', 'true');
        <?php endif; ?>
    }
});
</script>
