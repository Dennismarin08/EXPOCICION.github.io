<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/salud_functions.php';
require_once 'includes/planes_salud_functions.php';
require_once 'includes/check-auth.php';

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$puntosInfo = obtenerPuntosUsuario($userId);
$nivelInfo = obtenerInfoNivel($puntosInfo['nivel']);

// Obtener mascotas para el selector
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$mascotas = $stmt->fetchAll();


// Lógica de Calendario - Vista mensual completa con historial
$today = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

// Para navegación - Permitir ver meses anteriores y futuros
$month = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;
$year = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// Validar mes y año
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Calcular días del mes
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('w', strtotime("$year-$month-01")); // 0 (Dom) - 6 (Sáb)
$firstDayOfMonth = ($firstDayOfMonth + 6) % 7; // Convertir a 0 (Lun) - 6 (Dom)

// Fechas para navegación
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Determinar si estamos viendo el mes actual
$isCurrentMonth = ($month == $currentMonth && $year == $currentYear);



// Detectar si es móvil automáticamente (para vista semanal)
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone', 'BlackBerry'];

    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

$isMobile = isMobileDevice() || (isset($_GET['mobile']) && $_GET['mobile'] == '1');

// Lógica para vista semanal en móvil
if ($isMobile) {
    // Obtener la semana actual o la semana seleccionada
    $weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
    $baseDate = strtotime("$weekOffset weeks", strtotime("$year-$month-01"));
    $weekStart = strtotime('monday this week', $baseDate);
    
    // Navegación por semanas
    $prevWeekOffset = $weekOffset - 1;
    $nextWeekOffset = $weekOffset + 1;
    
    // Días de la semana
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $dayDate = strtotime("+$i days", $weekStart);
        $weekDays[] = [
            'date' => $dayDate,
            'day' => date('j', $dayDate),
            'month' => date('n', $dayDate),
            'year' => date('Y', $dayDate),
            'isToday' => date('Y-m-d', $dayDate) == date('Y-m-d'),
            'isPast' => $dayDate < strtotime(date('Y-m-d'))
        ];
    }
}


// Obtener eventos del calendario unificado
require_once 'includes/calendario_functions.php';

// Obtener mascota seleccionada (por defecto la primera)
$mascotaIdActual = isset($_GET['mascota_id']) ? (int)$_GET['mascota_id'] : ($mascotas[0]['id'] ?? 0);

// Cambiar la lógica para obtener eventos unificados de la mascota seleccionada
$todosEventos = [];
if ($mascotaIdActual > 0) {
    $todosEventos = obtenerEventosCalendario($mascotaIdActual, $month, $year);
}

// Organizar eventos por día
$eventosPorDia = [];
foreach ($todosEventos as $ev) {
    $day = intval(date('d', strtotime($ev['fecha'])));
    if (!isset($eventosPorDia[$day])) {
        $eventosPorDia[$day] = [];
    }
    $eventosPorDia[$day][] = $ev;
}

// Función para determinar clase CSS según fecha
function getDayClass($day, $month, $year) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $todayStr = date('Y-m-d');
    
    if ($dateStr == $todayStr) {
        return 'day-today';
    } elseif ($dateStr < $todayStr) {
        return 'day-past';
    } else {
        return 'day-future';
    }
}


// Definir meses en español
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Obtener fecha actual para el botón "Hoy"
$dateComponents = getdate();

// Obtener información de la mascota seleccionada
$mascotaSeleccionada = null;
if ($mascotaIdActual > 0) {
    $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id = ? AND user_id = ?");
    $stmt->execute([$mascotaIdActual, $userId]);
    $mascotaSeleccionada = $stmt->fetch();
}

// Obtener plan de salud de la mascota
$planSalud = obtenerPlanSalud($mascotaIdActual);

// Obtener recordatorios de hoy
$recordatoriosHoy = [];
if ($planSalud) {
    $stmt = $pdo->prepare("SELECT * FROM recordatorios_plan WHERE plan_id = ? AND fecha_programada = CURDATE()");
    $stmt->execute([$planSalud['id']]);
    $recordatoriosHoy = $stmt->fetchAll();
}

// Obtener plan de salud mensual activo para determinar estado real
$planSaludMensual = null;
$estadoColor = 'gris';
$estadoMensaje = 'Sin plan activo';
$recomendaciones = [];

if ($mascotaIdActual > 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM planes_salud_mensual 
        WHERE mascota_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$mascotaIdActual]);
    $planSaludMensual = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($planSaludMensual) {
        $alertas = json_decode($planSaludMensual['alertas_json'], true);
        $recomendacionesData = json_decode($planSaludMensual['recomendaciones_json'], true);
        
        // Determinar color y estado basado en el plan
        $nivelAlerta = $alertas['nivel'] ?? 'verde';
        
        switch ($nivelAlerta) {
            case 'rojo':
                $estadoColor = 'rojo';
                $estadoMensaje = $alertas['titulo'] ?? 'Requiere atención veterinaria';
                break;
            case 'amarillo':
            case 'naranja':
                $estadoColor = 'naranja';
                $estadoMensaje = $alertas['titulo'] ?? 'Precaución';
                break;
            case 'verde':
            default:
                $estadoColor = 'verde';
                $estadoMensaje = $alertas['titulo'] ?? 'Saludable';
                break;
        }
        
        // Construir recomendaciones desde el plan
        $recomendaciones = [];
        
        // Agregar mensaje de alerta si existe
        if (isset($alertas['mensaje']) && !empty($alertas['mensaje'])) {
            $recomendaciones[] = $alertas['mensaje'];
        }
        if (isset($alertas['accion']) && !empty($alertas['accion'])) {
            $recomendaciones[] = 'Acción recomendada: ' . $alertas['accion'];
        }
        
        // Agregar algunas recomendaciones del plan según frecuencia
        if (!empty($recomendacionesData['diaria']['alimentacion'])) {
            $recomendaciones[] = 'Alimentación: ' . $recomendacionesData['diaria']['alimentacion'][0];
        }
        if (!empty($recomendacionesData['diaria']['ejercicio'])) {
            $recomendaciones[] = 'Ejercicio: ' . $recomendacionesData['diaria']['ejercicio'][0];
        }
        if (!empty($recomendacionesData['semanal']['higiene'])) {
            $recomendaciones[] = 'Higiene: ' . $recomendacionesData['semanal']['higiene'][0];
        }
        if (!empty($recomendacionesData['mensual']['veterinario'])) {
            $recomendaciones[] = 'Veterinario: ' . $recomendacionesData['mensual']['veterinario'][0];
        }
    }
}


// Obtener plan de salud de la mascota
$planSalud = obtenerPlanSalud($mascotaIdActual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    
    <!-- Sistema de Diseño Unificado -->
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">

    <!-- Override to light theme for calendar -->
    <style>
        :root {
            --bg-principal: #f8fafc !important;
            --bg-card: #ffffff !important;
            --bg-sidebar: #ffffff !important;
            --bg-input: #f8fafc !important;
            --bg-hover: #f1f5f9 !important;
            --texto-principal: #1e293b !important;
            --texto-secundario: #64748b !important;
            --texto-terciario: #94a3b8 !important;
            --texto-muted: #cbd5e1 !important;
            --border-color: #e2e8f0 !important;
            --primary: #3b82f6 !important;
            --primary-light: #60a5fa !important;
            --primary-dark: #2563eb !important;
            --secondary: #10b981 !important;
            --success: #22c55e !important;
            --danger: #ef4444 !important;
            --warning: #f59e0b !important;
            --info: #0ea5e9 !important;
        }

        body.theme-user {
            background-color: var(--bg-principal) !important;
            color: var(--texto-principal) !important;
        }

        .calendar-day {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
        }

        .calendar-day:hover {
            background: var(--bg-hover) !important;
        }

        .calendar-day.empty {
            background: var(--bg-principal) !important;
        }

        .calendar-legend {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
        }

        .calendar-header {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
        }

        .calendar-nav-select {
            background: var(--bg-input) !important;
            border-color: var(--border-color) !important;
            color: var(--texto-principal) !important;
        }

        .calendar-nav-btn {
            background: var(--bg-input) !important;
            border-color: var(--border-color) !important;
            color: var(--texto-principal) !important;
        }

        .calendar-nav-btn:hover {
            background: var(--bg-hover) !important;
        }

        .calendar-nav-btn.today-btn {
            background: var(--primary) !important;
            color: white !important;
        }

        .pet-filter-btn.inactive {
            background: var(--bg-input) !important;
            color: var(--texto-secundario) !important;
            border-color: var(--border-color) !important;
        }

        .pet-filter-btn.active {
            background: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
        }

        .event-badge {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
        }

        .modal-content {
            background: var(--bg-card) !important;
        }

        .form-control {
            background: var(--bg-input) !important;
            border-color: var(--border-color) !important;
            color: var(--texto-principal) !important;
        }

        .btn-cancel {
            background: var(--bg-input) !important;
            color: var(--texto-secundario) !important;
            border-color: var(--border-color) !important;
        }

        /* Pet Info Card */
        .pet-info-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .pet-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
        }

        .pet-info-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
        }

        .pet-info-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .pet-info-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 18px;
        }

        /* Plan de Salud Section */
        .plan-salud-section {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
        }

        .plan-salud-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rutinas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .rutina-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
        }

        .rutina-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rutina-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .rutina-list li {
            padding: 4px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .rutina-list li:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .pet-info-grid,
            .rutinas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Responsive Mobile-First -->
    <link rel="stylesheet" href="css/mobile-first-base.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <!-- JavaScript Mobile Menu -->
    <script src="js/mobile-menu.js" defer></script>
    
    <style>

        .pet-filter-buttons {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
            flex-wrap: wrap;
        }

        .pet-filter-btn {
            padding: 10px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .pet-filter-btn.inactive {
            background: var(--bg-input);
            color: var(--texto-secundario);
            border-color: var(--border-color);
        }

        .pet-filter-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
        }

        .pet-filter-btn:hover {
            transform: translateY(-2px);
        }
        
        .calendar-legend {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--texto-secundario);
        }

        .color-box {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 700;
            color: var(--texto-secundario);
            padding: 15px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            background: var(--bg-card);
        }

        .calendar-day {
            min-height: 120px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px;
            position: relative;
            background: var(--bg-card);
            transition: all 0.2s;
            cursor: pointer;
        }

        .calendar-day:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
            background: var(--bg-hover);
        }

        .calendar-day.empty {
            background: var(--bg-principal);
            border: 1px solid var(--border-color);
            cursor: default;
        }

        .calendar-day.empty:hover {
            border-color: var(--border-color);
            box-shadow: none;
            transform: none;
            background: var(--bg-principal);
        }

        .calendar-day.other-month {
            background: var(--bg-principal);
            opacity: 0.6;
        }

        .day-number {
            font-weight: 700;
            color: var(--texto-principal);
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 14px;
            background: transparent;
        }

        .day-today .day-number {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-glow);
        }

        .day-events {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-height: 80px;
            overflow-y: auto;
        }

        .day-events::-webkit-scrollbar {
            width: 4px;
        }

        .day-events::-webkit-scrollbar-track {
            background: transparent;
        }

        .day-events::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        
        .event-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 4px;
            min-height: 40px;
        }

        .event-badge:hover {
            transform: translateX(3px) scale(1.02);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .event-badge:active {
            transform: translateX(2px) scale(0.98);
        }

        .event-icon {
            font-size: 14px;
            margin-right: 8px;
            flex-shrink: 0;
            opacity: 0.9;
        }

        .event-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
            color: var(--texto-principal);
        }

        .event-time {
            font-size: 11px;
            color: var(--texto-muted);
            margin-left: 6px;
            flex-shrink: 0;
            font-weight: 500;
        }

        .event-actions {
            display: none;
            gap: 6px;
            margin-left: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .event-badge:hover .event-actions {
            display: flex;
            opacity: 1;
        }

        .event-btn {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--texto-secundario);
        }

        .event-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-sm);
        }

        .event-btn.edit {
            color: var(--info);
            border-color: var(--info);
        }

        .event-btn.edit:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }

        .event-btn.delete {
            color: var(--danger);
            border-color: var(--danger);
        }

        .event-btn.delete:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Event types with vibrant colors using CSS variables */
        .event-salud {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
            color: var(--danger);
            border-left: 4px solid var(--danger);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);
        }

        .event-recordatorio {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);
            color: var(--primary);
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        }

        .event-cita {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%);
            color: var(--success);
            border-left: 4px solid var(--success);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
        }

        .event-rutina_diaria {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.04) 100%);
            color: var(--secondary);
            border-left: 4px solid var(--secondary);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.12);
        }

        .event-rutina_semanal {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.05) 100%);
            color: var(--accent);
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
        }

        .event-rutina_mensual {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(124, 58, 237, 0.05) 100%);
            color: #8b5cf6;
            border-left: 4px solid #8b5cf6;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.15);
        }

        /* Modal Mejorado */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s;
        }

        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 35px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-close:hover {
            color: #1e293b;
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn-submit, .btn-cancel {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-submit {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-glow);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        /* Botones Flotantes */
        .floating-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 100;
        }

        .btn-float {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-float:hover {
            transform: scale(1.1);
        }

        .btn-float.success {
            background: var(--gradient-secondary);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Selectors */
        .calendar-nav-selectors {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .calendar-nav-select {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-input);
            color: var(--texto-principal);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }

        .calendar-nav-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .calendar-nav-select:hover {
            border-color: var(--primary);
            background: var(--bg-hover);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .calendar-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .calendar-header-right {
            display: flex;
            align-items: center;
        }

        .calendar-nav-btn {
            padding: 10px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-input);
            color: var(--texto-principal);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .calendar-nav-btn:hover {
            border-color: var(--primary);
            background: var(--bg-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .calendar-nav-btn.today-btn {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
        }

        .calendar-nav-btn.today-btn:hover {
            background: var(--gradient-primary);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
        }

        /* Responsive Design - Mobile First */
        @media (max-width: 768px) {
            .calendar-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }

            .calendar-header-left {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .calendar-nav-selectors {
                order: -1;
                width: 100%;
                justify-content: center;
            }

            .calendar-nav-select {
                flex: 1;
                min-width: 80px;
                max-width: 120px;
            }

            .calendar-header-right {
                width: 100%;
                justify-content: center;
            }

            .calendar-nav-btn {
                padding: 8px 12px;
                font-size: 13px;
                min-width: 80px;
                justify-content: center;
            }

            .calendar-grid {
                gap: 4px;
                grid-template-columns: repeat(7, 1fr);
                padding: 0 8px;
            }

            .calendar-day {
                min-height: 90px;
                padding: 8px;
                font-size: 13px;
                border-radius: 10px;
            }

            .calendar-day-header {
                font-size: 12px;
                padding: 10px 0;
                margin-bottom: 8px;
            }

            .event-badge {
                font-size: 12px;
                padding: 8px 10px;
                min-height: 36px;
                margin-bottom: 3px;
            }

            .event-text {
                font-size: 12px;
            }

            .event-time {
                font-size: 10px;
            }

            .calendar-legend {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 18px;
                font-size: 13px;
                margin-bottom: 20px;
            }

            .pet-filter-buttons {
                width: 100%;
                gap: 8px;
                padding-bottom: 8px;
            }

            .pet-filter-btn {
                padding: 10px 14px;
                font-size: 13px;
                border-radius: 18px;
            }

            .floating-buttons {
                bottom: 25px;
                right: 25px;
            }

            .btn-float {
                width: 55px;
                height: 55px;
                font-size: 22px;
            }
        }

        /* Weekly view styles for mobile */
        .week-view {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .week-day-card {
            background: var(--bg-card);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin: 0 2px;
        }

        .week-day-card.today {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .week-day-header {
            background: var(--bg-hover);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .week-day-name {
            font-weight: 700;
            color: var(--texto-principal);
            font-size: 16px;
            text-transform: capitalize;
        }

        .week-day-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--bg-card);
            color: var(--texto-principal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .week-day-number.today-number {
            background: var(--gradient-primary);
            color: white;
        }

        .week-day-events {
            padding: 16px;
            min-height: 80px;
        }

        .no-events {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--texto-muted);
            font-size: 14px;
            gap: 8px;
            opacity: 0.7;
        }

        .no-events i {
            font-size: 24px;
        }

        .week-event-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .week-event-badge:hover {
            transform: translateX(2px);
            box-shadow: var(--shadow-md);
        }

        .week-event-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .week-event-icon {
            font-size: 16px;
            opacity: 0.9;
            flex-shrink: 0;
        }

        .week-event-text {
            font-weight: 600;
            color: var(--texto-principal);
            font-size: 14px;
            flex: 1;
        }

        .week-event-actions {
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .week-event-badge:hover .week-event-actions {
            opacity: 1;
        }

        .week-event-btn {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .week-event-btn:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-sm);
        }
    </style>
</head>
<body>
    <!-- ==========================================
         SIDEBAR Y CONTENIDO PRINCIPAL
         ========================================== -->

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Contenido Principal -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-calendar-alt"></i>
                    Calendario de Salud
                </h1>
                <p>Organiza y visualiza todas las actividades de salud de tu mascota</p>
            </div>

            <!-- Pet Selector -->
            <div class="pet-info-card">
                <div class="pet-info-grid">
                    <div class="pet-info-item">
                        <div class="pet-info-label">Mascota Seleccionada</div>
                        <div class="pet-info-value">
                            <?php if ($mascotaSeleccionada): ?>
                                <i class="fas fa-<?php echo $mascotaSeleccionada['especie'] === 'perro' ? 'dog' : 'cat'; ?>"></i>
                                <?php echo htmlspecialchars($mascotaSeleccionada['nombre']); ?>
                            <?php else: ?>
                                Ninguna
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pet-info-item">
                        <div class="pet-info-label">Edad</div>
                        <div class="pet-info-value">
                            <?php if ($mascotaSeleccionada): ?>
                                <?php echo $mascotaSeleccionada['edad_anios']; ?>a <?php echo $mascotaSeleccionada['edad_meses']; ?>m
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pet-info-item">
                        <div class="pet-info-label">Peso Actual</div>
                        <div class="pet-info-value">
                            <?php if ($mascotaSeleccionada): ?>
                                <?php echo $mascotaSeleccionada['peso'] ?? 'No registrado'; ?> kg
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estado de Salud -->
                <?php if ($mascotaSeleccionada && $planSaludMensual): ?>
                    <div class="pet-info-item">
                        <div class="pet-info-label">Estado de Salud</div>
                        <div class="pet-info-value">
                            <span style="color: <?php echo $estadoColor === 'verde' ? '#22c55e' : ($estadoColor === 'naranja' ? '#f59e0b' : '#ef4444'); ?>;">
                                <i class="fas fa-circle"></i>
                                <?php echo $estadoMensaje; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pet Filter Buttons -->
            <div class="pet-filter-buttons">
                <?php foreach ($mascotas as $mascota): ?>
                    <a href="?mascota_id=<?php echo $mascota['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>"
                       class="pet-filter-btn <?php echo ($mascotaIdActual == $mascota['id']) ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-<?php echo $mascota['especie'] === 'perro' ? 'dog' : 'cat'; ?>"></i>
                        <?php echo htmlspecialchars($mascota['nombre']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Calendar Header -->
            <div class="calendar-header">
                <div class="calendar-header-left">
                    <div class="calendar-nav-selectors">
                        <select class="calendar-nav-select" id="monthSelect">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $month) ? 'selected' : ''; ?>>
                                    <?php echo $meses[$m]; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select class="calendar-nav-select" id="yearSelect">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="calendar-header-right">
                    <button class="calendar-nav-btn today-btn" onclick="goToToday()">
                        <i class="fas fa-calendar-day"></i> Hoy
                    </button>
                    <button class="calendar-nav-btn" onclick="previousMonth()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="calendar-nav-btn" onclick="nextMonth()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="color-box" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.8), rgba(220, 38, 38, 0.6));"></div>
                    <span>Salud/Veterinario</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.6));"></div>
                    <span>Recordatorios</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.8), rgba(5, 150, 105, 0.6));"></div>
                    <span>Citas Médicas</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.8), rgba(217, 119, 6, 0.6));"></div>
                    <span>Rutinas</span>
                </div>
            </div>

            <?php if ($isMobile): ?>

                <!-- Vista lista para móviles - Semana actual -->
                <div class="week-view">
                    <?php foreach ($weekDays as $weekDay): 
                        $dayEvents = $eventosPorDia[$weekDay['day']] ?? [];
                        $dayClass = getDayClass($weekDay['day'], $weekDay['month'], $weekDay['year']);
                        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        $diaNombre = $diasSemana[date('w', $weekDay['date']) - 1] ?? $diasSemana[6];
                    ?>
                        <div class="week-day-card <?php echo $dayClass; ?>" onclick="showDayDetails(<?php echo $weekDay['day']; ?>, <?php echo $weekDay['month']; ?>, <?php echo $weekDay['year']; ?>)">
                            <div class="week-day-header">
                                <div class="week-day-name">
                                    <?php echo $diaNombre; ?>
                                    <span style="font-size: 12px; color: #64748b; margin-left: 5px;">
                                        <?php echo $meses[$weekDay['month']]; ?>
                                    </span>
                                </div>
                                <div class="week-day-number <?php echo $weekDay['isToday'] ? 'today-number' : ''; ?>">
                                    <?php echo $weekDay['day']; ?>
                                </div>
                            </div>
                            <div class="week-day-events">
                                <?php if (empty($dayEvents)): ?>
                                    <div class="no-events">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Sin eventos programados</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($dayEvents, 0, 3) as $evento): ?>
                                        <div class="week-event-badge event-<?php echo $evento['tipo']; ?>">
                                            <div class="week-event-content">
                                                <div class="week-event-icon">
                                                    <i class="fas <?php echo htmlspecialchars($evento['icono'] ?? 'fa-calendar'); ?>"></i>
                                                </div>
                                                <div class="week-event-text">
                                                    <?php echo htmlspecialchars($evento['titulo']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($dayEvents) > 3): ?>
                                        <div style="text-align: center; font-size: 12px; color: #64748b; padding: 5px;">
                                            +<?php echo count($dayEvents) - 3; ?> eventos más
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Navegación de semanas móvil -->
                <div style="display: flex; justify-content: space-between; margin-top: 15px; padding: 0 10px;">
                    <a href="?mascota_id=<?php echo $mascotaIdActual; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&week_offset=<?php echo $prevWeekOffset; ?>" class="calendar-nav-btn">
                        <i class="fas fa-chevron-left"></i> Semana anterior
                    </a>
                    <a href="?mascota_id=<?php echo $mascotaIdActual; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&week_offset=<?php echo $nextWeekOffset; ?>" class="calendar-nav-btn">
                        Semana siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

            <?php else: ?>
                <!-- Vista mensual completa para desktop -->
                <div class="calendar-30days" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-bottom: 30px;">
                    <!-- Headers de días -->
                    <?php
                    $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                    foreach ($diasSemana as $dia): ?>
                        <div class="calendar-day-header" style="text-align: center; font-weight: 700; color: #64748b; padding: 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; background: white; border-radius: 8px 8px 0 0;">
                            <?php echo $dia; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Días vacíos antes del inicio del mes -->
                    <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                        <div class="calendar-day empty" style="min-height: 100px; border: 2px solid #f1f5f9; border-radius: 12px; padding: 12px; background: #f8fafc; opacity: 0.5;"></div>
                    <?php endfor; ?>

                    <!-- Días del mes -->
                    <?php
                    for ($dayNum = 1; $dayNum <= $daysInMonth; $dayNum++):
                        $dayDateStr = sprintf('%04d-%02d-%02d', $year, $month, $dayNum);
                        $isToday = ($dayDateStr == date('Y-m-d'));
                        $isPast = ($dayDateStr < date('Y-m-d'));
                        $dayEvents = isset($eventosPorDia[$dayNum]) ? $eventosPorDia[$dayNum] : [];
                        $dayClass = getDayClass($dayNum, $month, $year);
                        
                        // Estilos según si es pasado, hoy o futuro
                        $bgColor = $isToday ? 'white' : ($isPast ? '#f8fafc' : 'white');
                        $borderColor = $isToday ? '#3b82f6' : ($isPast ? '#e2e8f0' : '#e2e8f0');
                        $opacity = $isPast ? '0.85' : '1';
                    ?>
                        <div class="calendar-day <?php echo $dayClass; ?>" 
                             style="min-height: 100px; border: 2px solid <?php echo $borderColor; ?>; border-radius: 12px; padding: 12px; background: <?php echo $bgColor; ?>; cursor: pointer; transition: all 0.2s; opacity: <?php echo $opacity; ?>;"
                             onclick="showDayDetails(<?php echo $dayNum; ?>, <?php echo $month; ?>, <?php echo $year; ?>)"
                             onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.opacity='1';"
                             onmouseout="this.style.borderColor='<?php echo $borderColor; ?>'; this.style.transform='none'; this.style.boxShadow='none'; this.style.opacity='<?php echo $opacity; ?>';">
                            
                            <div class="day-number" style="font-weight: 700; color: <?php echo $isToday ? 'white' : ($isPast ? '#64748b' : '#1e293b'); ?>; margin-bottom: 8px; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; background: <?php echo $isToday ? 'linear-gradient(135deg, #3b82f6, #1d4ed8)' : 'transparent'; ?>; box-shadow: <?php echo $isToday ? '0 2px 8px rgba(59, 130, 246, 0.4)' : 'none'; ?>;">
                                <?php echo $dayNum; ?>
                            </div>
                            
                            <?php if ($isPast): ?>
                                <div style="font-size: 10px; color: #94a3b8; margin-bottom: 4px;">
                                    <i class="fas fa-history"></i> Pasado
                                </div>
                            <?php endif; ?>

                            <div class="day-events" style="display: flex; flex-direction: column; gap: 4px;">
                                <?php foreach (array_slice($dayEvents, 0, 2) as $evento): ?>
                                    <div class="event-badge event-<?php echo $evento['tipo']; ?>" style="font-size: 11px; padding: 6px 8px; border-radius: 6px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                                        <i class="fas <?php echo htmlspecialchars($evento['icono'] ?? 'fa-calendar'); ?>" style="margin-right: 4px;"></i>
                                        <?php echo htmlspecialchars($evento['titulo']); ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($dayEvents) > 2): ?>
                                    <div style="text-align: center; font-size: 10px; color: #94a3b8; padding: 2px;">
                                        +<?php echo count($dayEvents) - 2; ?> más
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($dayEvents)): ?>
                                    <div style="text-align: center; color: #cbd5e1; font-size: 11px; padding: 10px 0;">
                                        <i class="fas fa-calendar-day" style="display: block; margin-bottom: 4px; font-size: 16px;"></i>
                                        <?php echo $isPast ? 'Sin eventos' : 'Sin eventos'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

            <?php endif; ?>

            <!-- Sección de Detalles del Día Seleccionado -->
            <div id="dayDetailsSection" class="day-details-section" style="background: white; border: 2px solid #e2e8f0; border-radius: 16px; padding: 25px; margin-top: 30px; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9;">
                    <h3 style="margin: 0; color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-day" style="color: #3b82f6;"></i>
                        <span id="selectedDayTitle">Detalles del Día</span>
                    </h3>
                    <span id="selectedDayBadge" style="padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #dbeafe; color: #1d4ed8;">
                        Día <span id="selectedDayNumber">-</span> del Plan
                    </span>
                </div>

                <div id="dayDetailsContent" style="min-height: 200px;">
                    <!-- Contenido dinámico cargado aquí -->
                </div>
            </div>


            <!-- Estado de Salud y Plan de Salud Section -->
            <?php if ($mascotaSeleccionada && $planSaludMensual): ?>
                <!-- Estado de Salud desde el Plan -->
                <div class="plan-salud-section">
                    <div class="plan-salud-title">
                        <i class="fas fa-heartbeat"></i>
                        Estado de Salud del Plan
                        <span style="float: right; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
                             <?php
                             if ($estadoColor === 'verde') echo 'background: #dcfce7; color: #166534;';
                             elseif ($estadoColor === 'naranja') echo 'background: #fed7aa; color: #9a3412;';
                             elseif ($estadoColor === 'rojo') echo 'background: #fecaca; color: #991b1b;';
                             else echo 'background: #f1f5f9; color: #64748b;';
                             ?>">
                            <i class="fas fa-circle" style="margin-right: 5px;"></i><?php echo $estadoMensaje; ?>
                        </span>
                    </div>

                    <?php if (!empty($recomendaciones)): ?>
                        <div style="background: rgba(255,255,255,0.8); border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0 0 10px 0; color: #1e293b; font-size: 14px;">
                                <i class="fas fa-lightbulb"></i> Recomendaciones del Plan
                            </h4>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #64748b;">
                                <?php foreach ($recomendaciones as $rec): ?>
                                    <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($rec); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 15px;">
                        <p style="margin: 0; font-size: 13px; color: #64748b;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: #3b82f6;"></i>
                            Plan creado el <?php echo date('d/m/Y', strtotime($planSaludMensual['created_at'])); ?>. 
                            <a href="plan-salud-mensual.php?mascota_id=<?php echo $mascotaIdActual; ?>" style="color: #3b82f6; font-weight: 600;">Ver detalles completos</a>
                        </p>
                    </div>
                </div>


                <!-- Plan de Salud Activo -->
                <?php if ($planSaludMensual || $planSalud): 
                        $createdDate = $planSaludMensual ? $planSaludMensual['created_at'] : $planSalud['created_at'];
                ?>
                    <div class="plan-salud-section">
                        <div class="plan-salud-title">
                            <i class="fas fa-calendar-check"></i>
                            Plan de Salud Activo
                            <span style="float: right; font-size: 12px; color: #64748b;">
                                Día <?php echo ceil((strtotime('today') - strtotime($createdDate)) / (60*60*24)) + 1; ?> de 30
                            </span>
                        </div>

                        <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 15px; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 13px; color: #64748b;">
                                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                                Plan creado el <?php echo date('d/m/Y', strtotime($createdDate)); ?>. Se ejecuta por 30 días consecutivos.
                            </p>
                        </div>

                        <!-- Plan Organizado por Categorías -->
                        <div class="plan-organizado">
                            <?php
                            // Obtener todas las actividades del plan
                            $todasActividades = [];
                            if ($planSaludMensual) {
                                // Plan Mensual Nuevo
                                $stmt = $pdo->prepare("SELECT * FROM plan_salud_mensual_tareas WHERE plan_id = ? ORDER BY categoria, fecha, hora");
                                $stmt->execute([$planSaludMensual['id']]);
                                $rawActividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Map fields for compatibility
                                foreach ($rawActividades as $act) {
                                    $act['tipo'] = $act['categoria'];
                                    $act['fecha_programada'] = $act['fecha'];
                                    $todasActividades[] = $act;
                                }
                            } elseif ($planSalud) {
                                // Plan Antiguo
                                $stmt = $pdo->prepare("SELECT * FROM recordatorios_plan WHERE plan_id = ? ORDER BY tipo, fecha_programada, hora");
                                $stmt->execute([$planSalud['id']]);
                                $todasActividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }

                            // Organizar por categorías
                            // Organizar por categorías
                            $categorias = [
                                'alimentacion' => ['titulo' => 'Alimentación', 'icono' => 'utensils', 'actividades' => []],
                                'ejercicio' => ['titulo' => 'Ejercicio', 'icono' => 'running', 'actividades' => []],
                                'higiene' => ['titulo' => 'Higiene', 'icono' => 'shower', 'actividades' => []],
                                'salud' => ['titulo' => 'Salud', 'icono' => 'heartbeat', 'actividades' => []],
                                'veterinario' => ['titulo' => 'Veterinario', 'icono' => 'user-md', 'actividades' => []],
                                // Legacy support
                                'rutina_diaria' => ['titulo' => 'Rutina Diaria', 'icono' => 'sun', 'actividades' => []],
                                'rutina_semanal' => ['titulo' => 'Rutina Semanal', 'icono' => 'calendar-week', 'actividades' => []],
                                'rutina_mensual' => ['titulo' => 'Rutina Mensual', 'icono' => 'calendar-alt', 'actividades' => []]
                            ];

                            foreach ($todasActividades as $actividad) {
                                $tipo = $actividad['tipo'];
                                if (isset($categorias[$tipo])) {
                                    $categorias[$tipo]['actividades'][] = $actividad;
                                }
                            }

                            foreach ($categorias as $key => $categoria):
                                if (!empty($categoria['actividades'])):
                            ?>
                                <div class="categoria-plan" style="margin-bottom: 25px;">
                                    <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-<?php echo $categoria['icono']; ?>" style="color: #3b82f6;"></i>
                                        <?php echo $categoria['titulo']; ?>
                                    </h4>

                                    <div style="display: grid; gap: 10px;">
                                        <?php foreach ($categoria['actividades'] as $actividad): ?>
                                            <div style="background: white; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                                    <i class="fas fa-<?php echo $categoria['icono']; ?>" style="font-size: 14px;"></i>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 2px;">
                                                        <?php echo htmlspecialchars($actividad['titulo']); ?>
                                                    </div>
                                                    <div style="font-size: 13px; color: #64748b; line-height: 1.3;">
                                                        <?php echo htmlspecialchars($actividad['descripcion']); ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                                        <i class="fas fa-calendar"></i> Día <?php 
                                                            $baseDate = $planSaludMensual ? $planSaludMensual['created_at'] : ($planSalud['created_at'] ?? date('Y-m-d'));
                                                            echo ceil((strtotime($actividad['fecha_programada']) - strtotime($baseDate)) / (60*60*24)) + 1; 
                                                        ?>
                                                        <?php if ($actividad['hora']): ?>
                                                            <span style="margin-left: 10px;"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($actividad['hora']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <!-- Actividades de Hoy -->
                        <div style="margin-top: 30px; padding: 15px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
                            <?php
                            $createdDate = isset($createdDate) ? $createdDate : date('Y-m-d'); // Fallback
                            $daysPassed = 1;
                            if ($createdDate) {
                                $daysPassed = ceil((strtotime('today') - strtotime($createdDate)) / (60*60*24)) + 1;
                            }
                            ?>
                            <h4 style="margin: 0 0 15px 0; color: #0f766e; font-size: 14px;">
                                <i class="fas fa-clock"></i> Actividades para Hoy (Día <?php echo $daysPassed; ?>)
                            </h4>

                            <?php
                            $actividadesHoy = [];
                            
                            if ($planSaludMensual) {
                                // Nuevo Plan
                                $stmt = $pdo->prepare("SELECT * FROM plan_salud_mensual_tareas WHERE plan_id = ? AND fecha = CURDATE() ORDER BY hora ASC");
                                $stmt->execute([$planSaludMensual['id']]);
                                $rawActividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Map fields
                                foreach ($rawActividades as $act) {
                                    $act['tipo'] = $act['categoria']; // Map categoria -> tipo
                                    $actividadesHoy[] = $act;
                                }
                            } elseif ($planSalud) {
                                // Legacy Plan
                                // Check if 'hora' exists before ordering by it, or just ignore order if it fails.
                                // Safer to just select by date.
                                try {
                                    $stmt = $pdo->prepare("SELECT * FROM recordatorios_plan WHERE plan_id = ? AND fecha_programada = CURDATE()");
                                    $stmt->execute([$planSalud['id']]);
                                    $actividadesHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    // Fallback if strict mode or column issues
                                    $actividadesHoy = [];
                                }
                            }

                            if (!empty($actividadesHoy)):
                            ?>
                                <div style="display: grid; gap: 12px;">
                                    <?php foreach ($actividadesHoy as $actividad): ?>
                                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white;">
                                                <i class="fas fa-<?php
                                                    $iconMap = [
                                                        'alimentacion' => 'utensils',
                                                        'ejercicio' => 'running',
                                                        'higiene' => 'shower',
                                                        'salud' => 'heartbeat',
                                                        'veterinario' => 'user-md',
                                                        'rutina_diaria' => 'sun',
                                                        'rutina_semanal' => 'calendar-week',
                                                        'rutina_mensual' => 'calendar-alt'
                                                    ];
                                                    echo $iconMap[$actividad['tipo']] ?? 'check';
                                                ?>"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                                    <?php echo htmlspecialchars($actividad['titulo'] ?? $actividad['actividad'] ?? 'Actividad'); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #64748b;">
                                                    <?php if (!empty($actividad['hora'])): ?>
                                                        <i class="fas fa-clock" style="margin-right: 4px;"></i><?php echo date('H:i', strtotime($actividad['hora'])); ?>
                                                    <?php else: ?>
                                                        Día Completo
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 13px; color: #64748b; text-align: center; margin: 0;">
                                    No hay actividades programadas para hoy.
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php else: ?>
                    <!-- No hay plan - mostrar opción para crear -->
                    <div class="plan-salud-section">
                        <div class="plan-salud-title">
                            <i class="fas fa-plus-circle"></i>
                            Crear Plan de Salud
                        </div>
                        <div style="text-align: center; padding: 40px 20px;">
                            <i class="fas fa-heartbeat" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px; color: #3b82f6;"></i>
                            <h4 style="margin-bottom: 10px; color: #1e293b;">No tienes un plan de salud activo</h4>
                            <p style="margin-bottom: 25px; color: #64748b; font-size: 14px;">
                                Crea un plan personalizado de 30 días basado en las características de tu mascota.
                            </p>
                            <a href="plan-salud-mensual.php?mascota_id=<?php echo $mascotaIdActual; ?>" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s;">
                                <i class="fas fa-plus"></i> Crear Plan de Salud
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <div class="floating-buttons">
        <button class="btn-float" onclick="openAddEventModal()" title="Agregar Evento">
            <i class="fas fa-plus"></i>
        </button>
        <button class="btn-float success" onclick="openQuickActionModal()" title="Acciones Rápidas">
            <i class="fas fa-bolt"></i>
        </button>
    </div>

    <!-- Modal para Día -->
    <div class="modal" id="dayModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="dayModalTitle">Eventos del Día</h3>
                <button class="modal-close" onclick="closeDayModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="dayModalContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Modal para Agregar Evento -->
    <div class="modal" id="addEventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Agregar Nuevo Evento</h3>
                <button class="modal-close" onclick="closeAddEventModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addEventForm">
                <div class="form-group">
                    <label class="form-label">Tipo de Evento <span class="required">*</span></label>
                    <select class="form-control" name="tipo_evento" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="cita">Cita Veterinaria</option>
                        <option value="recordatorio">Recordatorio</option>
                        <option value="rutina_diaria">Rutina Diaria</option>
                        <option value="rutina_semanal">Rutina Semanal</option>
                        <option value="rutina_mensual">Rutina Mensual</option>
                        <option value="salud">Evento de Salud</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Título <span class="required">*</span></label>
                    <input type="text" class="form-control" name="titulo" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha <span class="required">*</span></label>
                    <input type="date" class="form-control" name="fecha" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Hora</label>
                    <input type="time" class="form-control" name="hora">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3"></textarea>
                </div>
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeAddEventModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Variables globales
        let currentMonth = <?php echo $month; ?>;
        let currentYear = <?php echo $year; ?>;
        let selectedPetId = <?php echo $mascotaIdActual; ?>;

        // Navegación del calendario
        function previousMonth() {
            let newMonth = currentMonth - 1;
            let newYear = currentYear;
            if (newMonth < 1) {
                newMonth = 12;
                newYear--;
            }
            navigateToMonth(newMonth, newYear);
        }

        function nextMonth() {
            let newMonth = currentMonth + 1;
            let newYear = currentYear;
            if (newMonth > 12) {
                newMonth = 1;
                newYear++;
            }
            navigateToMonth(newMonth, newYear);
        }

        function goToToday() {
            const today = new Date();
            navigateToMonth(today.getMonth() + 1, today.getFullYear());
        }

        function navigateToMonth(month, year) {
            const url = `?mascota_id=${selectedPetId}&month=${month}&year=${year}`;
            window.location.href = url;
        }

        // Selectores de navegación
        document.getElementById('monthSelect').addEventListener('change', function() {
            navigateToMonth(parseInt(this.value), currentYear);
        });

        document.getElementById('yearSelect').addEventListener('change', function() {
            navigateToMonth(currentMonth, parseInt(this.value));
        });


        // Modal del día
        function openDayModal(day, month, year) {
            const modal = document.getElementById('dayModal');
            const title = document.getElementById('dayModalTitle');
            const content = document.getElementById('dayModalContent');

            title.textContent = `Eventos del ${day}/${month}/${year}`;

            // Cargar eventos del día seleccionado
            loadDayEvents(day, month, year);

            modal.classList.add('active');
        }

        function closeDayModal() {
            document.getElementById('dayModal').classList.remove('active');
        }

        // Modal agregar evento
        function openAddEventModal() {
            document.getElementById('addEventModal').classList.add('active');
        }

        function closeAddEventModal() {
            document.getElementById('addEventModal').classList.remove('active');
        }

        function openQuickActionModal() {
            // Implementar acciones rápidas
            const quickActions = `
                <div style="text-align: center; padding: 20px;">
                    <h3>Acciones Rápidas</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px;">
                        <button onclick="quickAddEvent('cita')" class="btn-submit" style="padding: 15px; border-radius: 10px;">
                            <i class="fas fa-calendar-check"></i><br>Agendar Cita
                        </button>
                        <button onclick="quickAddEvent('recordatorio')" class="btn-submit" style="padding: 15px; border-radius: 10px;">
                            <i class="fas fa-bell"></i><br>Recordatorio
                        </button>
                        <button onclick="quickAddEvent('rutina_diaria')" class="btn-submit" style="padding: 15px; border-radius: 10px;">
                            <i class="fas fa-sun"></i><br>Rutina Diaria
                        </button>
                        <button onclick="quickAddEvent('salud')" class="btn-submit" style="padding: 15px; border-radius: 10px;">
                            <i class="fas fa-heartbeat"></i><br>Evento Salud
                        </button>
                    </div>
                </div>
            `;

            // Crear modal temporal para acciones rápidas
            const quickModal = document.createElement('div');
            quickModal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.6); z-index: 2000; display: flex;
                align-items: center; justify-content: center;
            `;
            quickModal.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 20px; max-width: 400px; width: 90%;">
                    ${quickActions}
                    <button onclick="this.parentElement.parentElement.remove()" style="margin-top: 20px; width: 100%; padding: 10px; background: #f1f5f9; border: none; border-radius: 10px; cursor: pointer;">Cerrar</button>
                </div>
            `;
            document.body.appendChild(quickModal);
        }

        function quickAddEvent(tipo) {
            // Cerrar modal de acciones rápidas
            document.querySelector('[style*="z-index: 2000"]').remove();

            // Abrir modal de agregar evento con tipo pre-seleccionado
            openAddEventModal();

            // Pre-seleccionar el tipo
            setTimeout(() => {
                document.getElementById('addEventForm').querySelector('select[name="tipo_evento"]').value = tipo;
            }, 100);
        }

        // Funciones de eventos
        function editEvent(eventId) {
            // Implementar edición de evento
            alert('Funcionalidad de edición próximamente. Evento ID: ' + eventId);
        }

        function deleteEvent(eventId) {
            if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
                // Implementar eliminación de evento
                alert('Funcionalidad de eliminación próximamente. Evento ID: ' + eventId);
            }
        }

        // Mostrar detalles del día en la sección inferior
        function showDayDetails(day, month, year) {
            const section = document.getElementById('dayDetailsSection');
            const title = document.getElementById('selectedDayTitle');
            const dayNumber = document.getElementById('selectedDayNumber');
            const content = document.getElementById('dayDetailsContent');
            
            // Calcular día del plan (1-31)
            const today = new Date();
            const selectedDate = new Date(year, month - 1, day);
            const diffTime = Math.abs(selectedDate - today);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const planDay = diffDays + 1;
            
            // Mostrar sección
            section.style.display = 'block';
            title.textContent = `Detalles del ${day}/${month}/${year}`;
            dayNumber.textContent = planDay;
            
            // Scroll a la sección
            section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Cargar contenido
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3b82f6; margin-bottom: 15px;"></i>
                    <p style="color: #64748b;">Cargando detalles del día...</p>
                </div>
            `;
            
            // Cargar eventos vía AJAX
            loadDayDetailsAjax(day, month, year, planDay);
        }
        
        // Cargar detalles del día vía AJAX
        function loadDayDetailsAjax(day, month, year, planDay) {
            const content = document.getElementById('dayDetailsContent');
            const mascotaId = <?php echo $mascotaIdActual; ?>;
            
            // Crear form data
            const formData = new FormData();
            formData.append('mascota_id', mascotaId);
            formData.append('day', day);
            formData.append('month', month);
            formData.append('year', year);
            
            fetch('ajax-obtener-tarea.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tareas && data.tareas.length > 0) {
                    let html = `
                        <div style="display: grid; gap: 12px; margin-bottom: 20px;">
                    `;
                    
                    data.tareas.forEach(tarea => {
                        const tipoClass = tarea.tipo || 'recordatorio';
                        const icono = tarea.icono || 'fa-calendar';
                        const hora = tarea.hora ? `<span style="color: #3b82f6; font-weight: 600;"><i class="fas fa-clock" style="margin-right: 5px;"></i>${tarea.hora}</span>` : '';
                        
                        html += `
                            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px; transition: all 0.2s;" onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='translateX(5px)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='none';">
                                <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                    <i class="fas ${icono}"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: #1e293b; margin-bottom: 4px; font-size: 15px;">
                                        ${tarea.titulo}
                                    </div>
                                    <div style="font-size: 13px; color: #64748b; line-height: 1.4;">
                                        ${tarea.descripcion || 'Sin descripción'}
                                    </div>
                                    <div style="margin-top: 8px; font-size: 12px;">
                                        ${hora}
                                        <span style="margin-left: 15px; padding: 3px 10px; border-radius: 12px; background: #dbeafe; color: #1d4ed8; text-transform: uppercase; font-weight: 600;">
                                            ${tarea.tipo || 'Tarea'}
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="completarTarea(${tarea.id})" style="padding: 8px 12px; border: none; border-radius: 8px; background: #dcfce7; color: #166534; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#bbf7d0';" onmouseout="this.style.background='#dcfce7';">
                                        <i class="fas fa-check"></i> Completar
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `</div>`;
                    content.innerHTML = html;
                } else {
                    // No hay tareas programadas
                    content.innerHTML = `
                        <div style="text-align: center; padding: 50px 20px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; border: 2px dashed #e2e8f0;">
                            <i class="fas fa-calendar-day" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <h4 style="color: #64748b; margin-bottom: 10px;">No hay tareas programadas para este día</h4>
                            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">Este día está libre en tu plan de salud</p>
                            <button onclick="openAddEventModal()" style="padding: 12px 24px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                                <i class="fas fa-plus"></i> Agregar Tarea
                            </button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-circle" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <p>Error al cargar los detalles. Intenta nuevamente.</p>
                    </div>
                `;
            });
        }

        // Función para completar tarea
        function completarTarea(tareaId) {
            if (!confirm('¿Marcar esta tarea como completada?')) return;
            
            const formData = new FormData();
            formData.append('tarea_id', tareaId);
            
            fetch('completar-tarea.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Tarea completada! +10 puntos');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo completar la tarea'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al completar la tarea');
            });
        }

        // Cargar eventos del día (mantener para compatibilidad)
        function loadDayEvents(day, month, year) {
            const content = document.getElementById('dayModalContent');
            content.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-info-circle" style="font-size: 24px; color: #3b82f6; margin-bottom: 15px;"></i>
                    <p>Usa la nueva sección de detalles debajo del calendario</p>
                </div>
            `;
        }


        // Formulario agregar evento
        document.getElementById('addEventForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('mascota_id', selectedPetId);

            // Enviar datos al servidor
            fetch('ajax-agregar-evento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Evento guardado exitosamente');
                    closeAddEventModal();
                    this.reset();
                    // Recargar la página para mostrar el nuevo evento
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar el evento'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el evento');
            });
        });
    </script>
</body>
</html>
