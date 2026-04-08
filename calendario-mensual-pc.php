<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/salud_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user = getUsuario($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$puntosInfo = obtenerPuntosUsuario($userId);
$nivelInfo = obtenerInfoNivel($puntosInfo['nivel']);

// Obtener mascotas para el selector
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$mascotas = $stmt->fetchAll();

// Lógica del Calendario Mensual para PC
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Calcular navegación del mes
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear = $year - 1;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear = $year + 1;
}

// Calcular días del mes
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('N', strtotime("$year-$month-01")); // 1 (Lunes) - 7 (Domingo)

// Obtener eventos del calendario unificado para el mes completo
require_once 'includes/calendario_functions.php';

// Obtener mascota seleccionada
$mascotaIdActual = isset($_GET['mascota_id']) ? (int)$_GET['mascota_id'] : ($mascotas[0]['id'] ?? 0);

// Obtener eventos para el mes completo
$todosEventos = [];
if ($mascotaIdActual > 0) {
    $startOfMonth = new DateTime("$year-$month-01");
    $endOfMonth = new DateTime("$year-$month-$daysInMonth");

    // Obtener eventos para el mes completo (sin límite de semanas)
    $todosEventos = obtenerEventosCalendarioMensual($mascotaIdActual, $month, $year);
}

// Organizar eventos por día
$eventosPorDia = [];
foreach ($todosEventos as $ev) {
    $eventDate = new DateTime($ev['fecha']);
    if ($eventDate->format('n') == $month && $eventDate->format('Y') == $year) {
        $day = intval($eventDate->format('j'));
        $eventosPorDia[$day][] = $ev;
    }
}

// Función auxiliar para obtener eventos del mes completo
function obtenerEventosCalendarioMensual($mascotaId, $month, $year) {
    global $pdo;
    $eventos = [];

    $startDateStr = sprintf('%04d-%02d-01', $year, $month);
    $endDateStr = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));

    // 1. Citas
    $stmt = $pdo->prepare("
        SELECT id, 'cita' as tipo, 'calendar-check' as icono, motivo as titulo, fecha_hora as fecha
        FROM citas
        WHERE mascota_id = ? AND DATE(fecha_hora) BETWEEN ? AND ? AND estado != 'cancelada'
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $eventos = array_merge($eventos, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 2. Recordatorios de Usuario
    $stmt = $pdo->prepare("
        SELECT id, 'recordatorio' as tipo, 'bell' as icono, titulo, fecha_programada as fecha
        FROM recordatorios
        WHERE mascota_id = ? AND DATE(fecha_programada) BETWEEN ? AND ? AND estado = 'pendiente'
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $eventos = array_merge($eventos, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 3. Recordatorios del Plan de Salud
    $stmt = $pdo->prepare("
        SELECT rp.id, rp.tipo, rp.descripcion as titulo, rp.fecha_programada as fecha
        FROM recordatorios_plan rp
        JOIN planes_salud ps ON rp.plan_id = ps.id
        WHERE ps.mascota_id = ? AND rp.fecha_programada BETWEEN ? AND ? AND ps.activo = 1
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $planEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($planEvents as $pe) {
        $icono = 'utensils';
        if ($pe['tipo'] === 'rutina_semanal') $icono = 'broom';
        if ($pe['tipo'] === 'rutina_mensual') $icono = 'stethoscope';

        $pe['icono'] = $icono;
        $eventos[] = $pe;
    }

    return $eventos;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Mensual - PC - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
