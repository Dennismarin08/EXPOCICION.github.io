<?php
require_once __DIR__ . '/../db.php';

/**
 * Obtener recomendación diaria
 * Si hay en BD las usa, si no, usa un array fallback
 */
function obtenerRecomendacionDiaria() {
    global $pdo;
    
    // Intentar obtener de BD
    try {
        $stmt = $pdo->query("SELECT * FROM recomendaciones WHERE activo = 1 ORDER BY RAND() LIMIT 1");
        $tip = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tip) return $tip;
    } catch (Exception $e) {}
    
    // Fallback
    $fallbacks = [
        ['titulo' => 'Hidratación', 'contenido' => 'Asegúrate de que tu mascota tenga agua fresca siempre.', 'categoria' => 'salud'],
        ['titulo' => 'Juegos', 'contenido' => 'Jugar 15 minutos al día refuerza vuestro vínculo.', 'categoria' => 'ejercicio']
    ];
    
    return $fallbacks[array_rand($fallbacks)];
}

/**
 * Verificar estado del chequeo diario (si ya respondió hoy)
 */
function obtenerEstadoDiario($userId, $mascotaId) {
    global $pdo;
    
    $hoy = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM estado_diario WHERE user_id = ? AND mascota_id = ? AND fecha = ?");
    $stmt->execute([$userId, $mascotaId, $hoy]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Guardar respuesta de estado diario
 */
function guardarEstadoDiario($userId, $mascotaId, $respuesta) {
    global $pdo;
    
    $hoy = date('Y-m-d');
    
    // Verificar si ya existe para no duplicar (aunque la UI debería prevenirlo)
    $existente = obtenerEstadoDiario($userId, $mascotaId);
    if ($existente) return false;
    
    $stmt = $pdo->prepare("INSERT INTO estado_diario (user_id, mascota_id, fecha, respuesta) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $mascotaId, $hoy, $respuesta]);
}

/**
 * Obtener próximos recordatorios de salud
 */
function obtenerProximosRecordatorios($userId, $limit = 5) {
    global $pdo;
    
    $sql = "
        SELECT r.*, m.nombre as mascota_nombre 
        FROM recordatorios r
        JOIN mascotas m ON r.mascota_id = m.id
        WHERE r.user_id = ? AND r.estado = 'pendiente' AND r.fecha_programada >= NOW()
        ORDER BY r.fecha_programada ASC
        LIMIT ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcular el estado de salud mostrable de una mascota.
 * Prioridad: Plan mensual activo > Análisis de peso vs raza > Campo BD
 * Retorna: ['text' => string, 'color' => string, 'icon' => string]
 */
function calcularEstadoSaludDisplay($mascota, $planSalud = null) {
    $estadoText  = ucfirst($mascota['estado_salud'] ?: 'Excelente');
    $estadoColor = '#10b981';
    $estadoIcon  = 'fa-check-circle';

    if ($planSalud) {
        $nivel   = $planSalud['nivel_alerta'] ?? 'verde';
        $alertas = $planSalud['alertas'] ?? [];
        $titulo  = $alertas['titulo'] ?? null;

        if ($nivel === 'rojo') {
            $estadoColor = '#ef4444';
            $estadoIcon  = 'fa-times-circle';
            $estadoText  = $titulo ?: 'Atención Requerida';
        } elseif ($nivel === 'amarillo') {
            $estadoColor = '#f59e0b';
            $estadoIcon  = 'fa-exclamation-circle';
            $estadoText  = $titulo ?: 'Precaución';
        } else {
            $estadoText = ($titulo && $titulo !== 'Estado Saludable') ? $titulo : 'Excelente';
        }
    } else {
        // Sin plan: analizar peso vs raza
        require_once __DIR__ . '/razas_data.php';
        $datosRaza  = obtenerDatosRaza($mascota['raza'] ?? '');
        $pesoActual = floatval($mascota['peso'] ?? 0);
        $pesoMin    = floatval($datosRaza['peso_min'] ?? 0);
        $pesoMax    = floatval($datosRaza['peso_max'] ?? 0);

        if ($pesoActual > 0 && $pesoMax > 0 && $pesoActual > $pesoMax) {
            $pct = (($pesoActual - $pesoMax) / $pesoMax) * 100;
            if ($pct > 40) {
                $estadoText = 'Obesidad Severa'; $estadoColor = '#ef4444'; $estadoIcon = 'fa-times-circle';
            } elseif ($pct > 20) {
                $estadoText = 'Sobrepeso'; $estadoColor = '#f59e0b'; $estadoIcon = 'fa-exclamation-circle';
            } elseif ($pct > 0) {
                $estadoText = 'Ligero Sobrepeso'; $estadoColor = '#f59e0b'; $estadoIcon = 'fa-exclamation-circle';
            }
        } elseif ($pesoActual > 0 && $pesoMin > 0 && $pesoActual < $pesoMin) {
            $pct = (($pesoMin - $pesoActual) / $pesoMin) * 100;
            if ($pct > 30) {
                $estadoText = 'Desnutrición Severa'; $estadoColor = '#ef4444'; $estadoIcon = 'fa-times-circle';
            } elseif ($pct > 15) {
                $estadoText = 'Bajo Peso'; $estadoColor = '#f59e0b'; $estadoIcon = 'fa-exclamation-circle';
            } elseif ($pct > 0) {
                $estadoText = 'Peso Bajo Promedio'; $estadoColor = '#f59e0b'; $estadoIcon = 'fa-exclamation-circle';
            }
        }
    }

    return ['text' => $estadoText, 'color' => $estadoColor, 'icon' => $estadoIcon];
}

