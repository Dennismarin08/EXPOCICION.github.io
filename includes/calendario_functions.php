<?php
/**
 * RUGAL - Calendar Functions
 * Fetching unified events for the calendar
 */

function obtenerEventosCalendario($mascotaId, $month, $year, $weekOffset = 0) {
    global $pdo;
    $eventos = [];

    // Calcular el rango de fechas para TODO el mes (31 días)
    $startDate = new DateTime("$year-$month-01");
    $endDate = new DateTime("$year-$month-" . date('t', strtotime("$year-$month-01"))); // Último día del mes

    $startDateStr = $startDate->format('Y-m-d');
    $endDateStr = $endDate->format('Y-m-d');


    // 1. Citas (Appointments)
    $stmt = $pdo->prepare("
        SELECT id, 'cita' as tipo, 'calendar-check' as icono, motivo as titulo, fecha_hora as fecha
        FROM citas
        WHERE mascota_id = ? AND DATE(fecha_hora) BETWEEN ? AND ? AND estado != 'cancelada'
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $eventos = array_merge($eventos, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 2. Recordatorios de Usuario (User Reminders)
    $stmt = $pdo->prepare("
        SELECT id, 'recordatorio' as tipo, 'bell' as icono, titulo, fecha_programada as fecha
        FROM recordatorios
        WHERE mascota_id = ? AND DATE(fecha_programada) BETWEEN ? AND ? AND estado = 'pendiente'
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $eventos = array_merge($eventos, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 4. Tareas del Plan de Salud Mensual (Monthly Health Plan)
    $stmt = $pdo->prepare("
        SELECT t.id, t.categoria, t.titulo, t.descripcion, t.fecha, t.hora
        FROM plan_salud_mensual_tareas t
        JOIN planes_salud_mensual p ON t.plan_id = p.id
        WHERE p.mascota_id = ? AND t.fecha BETWEEN ? AND ?
        ORDER BY t.fecha, t.hora
    ");
    $stmt->execute([$mascotaId, $startDateStr, $endDateStr]);
    $tareasPlan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tareasPlan as $tarea) {
        // Map category to icon
        $iconMap = [
            'alimentacion' => 'utensils',
            'ejercicio' => 'running',
            'higiene' => 'pump-soap',
            'salud' => 'heart-pulse',
            'veterinario' => 'user-md'
        ];
        $tipoMap = [
            'alimentacion' => 'comida',
            'ejercicio' => 'actividad',
            'higiene' => 'higiene',
            'salud' => 'salud',
            'veterinario' => 'cita'
        ];

        $icono = $iconMap[$tarea['categoria']] ?? 'check-circle';
        
        $eventos[] = [
            'id' => 'plan_' . $tarea['id'],
            'tipo' => $tipoMap[$tarea['categoria']] ?? 'tarea', // Para clases CSS
            'titulo' => $tarea['titulo'],
            'fecha' => $tarea['fecha'] . ' ' . $tarea['hora'],
            'icono' => $icono
        ];
    }

    return $eventos;

}

function obtenerProximosEventos($userId, $limit = 5) {
    global $pdo;
    $today = date('Y-m-d');
    
    // Fetch from all sources and sort by date
    $sql = "(SELECT 'cita' COLLATE utf8mb4_general_ci as tipo, 'calendar-check' COLLATE utf8mb4_general_ci as icono, motivo COLLATE utf8mb4_general_ci as titulo, fecha_hora as fecha, m.nombre COLLATE utf8mb4_general_ci as mascota 
             FROM citas c JOIN mascotas m ON c.mascota_id = m.id 
             WHERE c.user_id = ? AND DATE(c.fecha_hora) >= ? AND c.estado != 'cancelada')
            UNION
            (SELECT 'recordatorio' COLLATE utf8mb4_general_ci as tipo, 'bell' COLLATE utf8mb4_general_ci as icono, titulo COLLATE utf8mb4_general_ci as titulo, fecha_programada as fecha, m.nombre COLLATE utf8mb4_general_ci as mascota 
             FROM recordatorios r JOIN mascotas m ON r.mascota_id = m.id 
             WHERE r.user_id = ? AND DATE(r.fecha_programada) >= ? AND r.estado = 'pendiente')
            UNION
            (SELECT rp.tipo COLLATE utf8mb4_general_ci, 
                    CASE 
                        WHEN rp.tipo = 'rutina_diaria' THEN 'utensils'
                        WHEN rp.tipo = 'rutina_semanal' THEN 'broom'
                        ELSE 'stethoscope'
                    END COLLATE utf8mb4_general_ci as icono,
                    rp.descripcion COLLATE utf8mb4_general_ci as titulo, rp.fecha_programada as fecha, m.nombre COLLATE utf8mb4_general_ci as mascota 
             FROM recordatorios_plan rp 
             JOIN planes_salud ps ON rp.plan_id = ps.id 
             JOIN mascotas m ON ps.mascota_id = m.id 
             WHERE m.user_id = ? AND rp.fecha_programada >= ? AND ps.activo = 1)
            UNION
            (SELECT 
                    CASE 
                        WHEN t.categoria = 'alimentacion' THEN 'comida'
                        WHEN t.categoria = 'ejercicio' THEN 'actividad'
                        WHEN t.categoria = 'higiene' THEN 'higiene'
                        WHEN t.categoria = 'salud' THEN 'salud'
                        WHEN t.categoria = 'veterinario' THEN 'cita'
                        ELSE 'tarea'
                    END COLLATE utf8mb4_general_ci as tipo,
                    CASE 
                        WHEN t.categoria = 'alimentacion' THEN 'utensils'
                        WHEN t.categoria = 'ejercicio' THEN 'running'
                        WHEN t.categoria = 'higiene' THEN 'pump-soap'
                        WHEN t.categoria = 'salud' THEN 'heart-pulse'
                        WHEN t.categoria = 'veterinario' THEN 'user-md'
                        ELSE 'check-circle'
                    END COLLATE utf8mb4_general_ci as icono,
                    t.titulo COLLATE utf8mb4_general_ci as titulo, 
                    CONCAT(t.fecha, ' ', t.hora) as fecha, 
                    m.nombre COLLATE utf8mb4_general_ci as mascota 
             FROM plan_salud_mensual_tareas t
             JOIN planes_salud_mensual p ON t.plan_id = p.id
             JOIN mascotas m ON p.mascota_id = m.id
             WHERE m.user_id = ? AND t.fecha >= ?)
            ORDER BY fecha ASC
            LIMIT $limit";

            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $today, $userId, $today, $userId, $today, $userId, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener tareas del plan de salud mensual para una mascota
 */
function obtenerTareasPlanSaludMensual($mascotaId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, p.mes, p.anio
            FROM plan_salud_mensual_tareas t
            JOIN planes_salud_mensual p ON t.plan_id = p.id
            WHERE p.mascota_id = ? 
            AND t.fecha >= CURDATE()
            AND t.completada = 0
            ORDER BY t.fecha, t.hora
            LIMIT ?
        ");
        $stmt->execute([$mascotaId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error obteniendo tareas del plan: ' . $e->getMessage());
        return [];
    }
}
