<?php
/**
 * RUGAL - Dashboard Data Functions
 * Funciones para obtener datos reales de salud y estadísticas
 */

require_once __DIR__ . '/../db.php';

/**
 * Obtener estadísticas completas de una mascota
 */
function obtenerEstadisticasMascotaReal($mascotaId) {
    global $pdo;
    
    $stats = [
        'next_vaccine' => 'Sin fecha',
        'weight_change' => '0.0 kg',
        'health_score' => '100%'
    ];
    
    if (!$mascotaId) return $stats;

    // 1. Próxima Vacuna
    $stmt = $pdo->prepare("
        SELECT proxima_fecha, nombre_evento 
        FROM mascotas_salud 
        WHERE mascota_id = ? AND tipo = 'vacuna' AND proxima_fecha >= CURDATE()
        ORDER BY proxima_fecha ASC LIMIT 1
    ");
    $stmt->execute([$mascotaId]);
    $nextVac = $stmt->fetch();
    
    if ($nextVac) {
        $fecha = new DateTime($nextVac['proxima_fecha']);
        $hoy = new DateTime();
        $diff = $hoy->diff($fecha)->days;
        
        if ($diff == 0) $stats['next_vaccine'] = 'Hoy';
        elseif ($diff == 1) $stats['next_vaccine'] = 'Mañana';
        else $stats['next_vaccine'] = $diff . ' días';
    }

    // 2. Cambio de Peso
    $stmt = $pdo->prepare("
        SELECT peso FROM peso_historial 
        WHERE mascota_id = ? 
        ORDER BY fecha DESC LIMIT 2
    ");
    $stmt->execute([$mascotaId]);
    $pesos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($pesos) >= 2) {
        $cambio = $pesos[0] - $pesos[1];
        $stats['weight_change'] = ($cambio >= 0 ? '+' : '') . number_format($cambio, 1) . ' kg';
    }

    // 3. Health Score (Basado en cumplimiento del Plan de Salud)
    // Buscamos el plan activo
    $stmt = $pdo->prepare("SELECT id FROM planes_salud WHERE mascota_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$mascotaId]);
    $plan = $stmt->fetch();
    
    if ($plan) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN completado = 1 THEN 1 ELSE 0 END) as completados
            FROM recordatorios_plan 
            WHERE plan_id = ?
        ");
        $stmt->execute([$plan['id']]);
        $row = $stmt->fetch();
        
        if ($row['total'] > 0) {
            $score = round(($row['completados'] / $row['total']) * 100);
            $stats['health_score'] = $score . '%';
        }
    } else {
        // Si no tiene plan, revisamos tareas completadas del día o chequeo
        $stats['health_score'] = 'N/A';
    }

    return $stats;
}
